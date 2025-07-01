<?php

/**
 * @file
 * Block plugin for displaying customer form submissions overview.
 */

namespace Drupal\netlify_forms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Forms Overview' Block.
 *
 * @Block(
 *   id = "forms_overview_block",
 *   admin_label = @Translation("Forms Overview"),
 *   category = @Translation("Netlify Forms"),
 * )
 */
class FormsOverviewBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new FormsOverviewBlock instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Only show for logged-in users
    if ($this->currentUser->isAnonymous()) {
      return [];
    }

    // Get customer for current user
    $customer = $this->getCustomerForUser($this->currentUser->id());
    if (!$customer) {
      return [];
    }

    $site_id = $customer->getSiteId();
    $selected_forms = $customer->getSelectedForms();

    if (empty($selected_forms)) {
      return [
        '#markup' => '<div class="no-forms-message">' .
          $this->t('No forms configured. Contact support to get started.') .
          '</div>',
        '#attached' => [
          'library' => ['netlify_forms/submissions_styling'],
        ],
      ];
    }

    // Get form info from API
    $netlify_api = \Drupal::service('netlify_forms.api');
    $all_forms = $netlify_api->getForms($site_id);
    $forms_by_id = [];
    foreach ($all_forms as $form) {
      $forms_by_id[$form['id']] = $form;
    }

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['forms-overview-block']],
      '#attached' => [
        'library' => ['netlify_forms/submissions_styling'],
      ],
    ];

    $build['header'] = [
      '#markup' => '<h3 class="block-title">' . $this->t('Your Forms') . '</h3>',
    ];

    $build['forms_grid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['forms-grid', 'forms-grid--block']],
    ];

    foreach ($selected_forms as $form_id) {
      if (!isset($forms_by_id[$form_id])) {
        continue;
      }

      $form = $forms_by_id[$form_id];

      // Get submission count from local database
      $submission_count = \Drupal::database()->select('netlify_submissions', 'ns')
        ->condition('customer_id', $customer->id())
        ->condition('form_id', $form_id)
        ->countQuery()
        ->execute()
        ->fetchField();

      // Get last submission
      $last_submission = \Drupal::database()->select('netlify_submissions', 'ns')
        ->fields('ns', ['created_at'])
        ->condition('customer_id', $customer->id())
        ->condition('form_id', $form_id)
        ->orderBy('created_at', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      $last_submission_text = $this->t('No submissions yet');
      if ($last_submission) {
        $last_date = new \DateTime();
        $last_date->setTimestamp($last_submission);
        $now = new \DateTime();
        $interval = $now->diff($last_date);

        if ($interval->days == 0) {
          if ($interval->h == 0) {
            $last_submission_text = $this->t('@minutes minutes ago', ['@minutes' => $interval->i]);
          } else {
            $last_submission_text = $this->t('@hours hours ago', ['@hours' => $interval->h]);
          }
        } elseif ($interval->days == 1) {
          $last_submission_text = $this->t('Yesterday');
        } else {
          $last_submission_text = $this->t('@days days ago', ['@days' => $interval->days]);
        }
      }

      // Create form card
      $detail_url = Url::fromRoute('netlify_forms.form_submissions', [
        'form_id' => $form_id,
      ]);

      $build['forms_grid'][$form_id] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['form-card']],
      ];

      $build['forms_grid'][$form_id]['link'] = [
        '#type' => 'link',
        '#title' => '',
        '#url' => $detail_url,
        '#attributes' => ['class' => ['form-card-link']],
      ];

      $build['forms_grid'][$form_id]['content'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['form-card-content']],
      ];
      $build['forms_grid'][$form_id]['content']['content-container'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['form-card-content--container']],
      ];
      $build['forms_grid'][$form_id]['content']['content-container']['name'] = [
        '#markup' => '<h3 class="form-name">' . htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') . '</h3>',
      ];

      $build['forms_grid'][$form_id]['content']['content-container']['last_submission'] = [
        '#markup' => '<p class="last-submission">' . $last_submission_text . '</p>',
      ];

      $build['forms_grid'][$form_id]['content']['count'] = [
        '#markup' => '<div class="submission-count">' .
          $this->formatPlural($submission_count, '1 submission', '@count submissions') .
          '<span class="arrow">›</span>' .
          '</div>',
      ];
    }

    $build['view_all'] = [
      '#type' => 'link',
      '#title' => $this->t('View All Forms →'),
      '#url' => Url::fromRoute('netlify_forms.user_submissions'),
      '#attributes' => ['class' => ['view-all-link']],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'view own netlify submissions');
  }

  /**
   * Get customer entity for a user.
   */
  protected function getCustomerForUser($user_id) {
    $storage = \Drupal::entityTypeManager()->getStorage('netlify_customer');
    $customers = $storage->loadByProperties(['user_id' => $user_id]);
    return !empty($customers) ? reset($customers) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Cache for 5 minutes
    return 300;
  }

}

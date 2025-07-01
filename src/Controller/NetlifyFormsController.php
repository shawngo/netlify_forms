<?php

/**
 * @file
 * Controller for customer-facing form submission management and display.
 */

namespace Drupal\netlify_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\netlify_forms\Service\NetlifyApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Netlify Forms pages.
 */
class NetlifyFormsController extends ControllerBase {

  /**
   * The Netlify API service.
   *
   * @var \Drupal\netlify_forms\Service\NetlifyApiService
   */
  protected $netlifyApi;

  /**
   * Constructs a NetlifyFormsController object.
   */
  public function __construct(NetlifyApiService $netlify_api) {
    $this->netlifyApi = $netlify_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('netlify_forms.api')
    );
  }

  /**
   * Display user's form submissions.
   */
  public function userSubmissions(Request $request) {
    $current_user = $this->currentUser();

    // Get customer entity for current user
    $customer = $this->getCustomerForUser($current_user->id());

    if (!$customer) {
      return [
        '#markup' => $this->t('No customer profile found. Please contact an administrator to set up your account.'),
      ];
    }

    $site_id = $customer->getSiteId();
    $selected_forms = $customer->getSelectedForms();

    if (empty($selected_forms)) {
      return [
        '#markup' =>
        '<div id="netlify-forms--no-forms-configured">' .
        $this->t('No forms have been configured for your account. Please contact an administrator.')
        . '</div>',
      ];
    }

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['netlify-forms-submissions']],
      '#attached' => [
        'library' => ['netlify_forms/submissions_styling'],
      ],
    ];

    // Page header
    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['submissions-header']],
    ];

    $build['header']['title'] = [
      '#markup' => '<h1 class="page-title">' . $this->t('Active forms') . '</h1>',
    ];

    // Forms grid
    $build['forms_grid'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['forms-grid']],
    ];

    // Get all forms for the site to get form names
    $all_forms = $this->netlifyApi->getForms($site_id);
    $forms_by_id = [];
    foreach ($all_forms as $form) {
      $forms_by_id[$form['id']] = $form;
    }

    foreach ($selected_forms as $form_id) {
      if (!isset($forms_by_id[$form_id])) {
        continue;
      }

      $form = $forms_by_id[$form_id];
      // $submissions = $this->netlifyApi->getSubmissions($site_id, $form_id);
      $submissions = $this->getLocalSubmissions($customer->id(), $form_id);
      $submission_count = count($submissions);

      // Get last submission date
      $last_submission_text = $this->t('No submissions yet');
      if (!empty($submissions)) {
        $last_submission = reset($submissions); // Get first (most recent) submission
        $last_date = new \DateTime($last_submission['created_at']);
        $now = new \DateTime();
        $interval = $now->diff($last_date);

        if ($interval->days == 0) {
          if ($interval->h == 0) {
            $last_submission_text = $this->t('Last submission @minutes minutes ago', ['@minutes' => $interval->i]);
          } else {
            $last_submission_text = $this->t('Last submission @hours hours ago', ['@hours' => $interval->h]);
          }
        } elseif ($interval->days == 1) {
          $last_submission_text = $this->t('Last submission yesterday');
        } else {
          $last_submission_text = $this->t('Last submission on @date (@days days ago)', [
            '@date' => $last_date->format('M j'),
            '@days' => $interval->days,
          ]);
        }
      }

      // Create the form card
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

    return $build;
  }

  /**
   * Display submissions for a specific form.
   */
  public function formSubmissions($form_id, Request $request) {
    $current_user = $this->currentUser();

    // Get customer entity for current user
    $customer = $this->getCustomerForUser($current_user->id());

    if (!$customer) {
      return [
        '#markup' => $this->t('No customer profile found.'),
      ];
    }

    $site_id = $customer->getSiteId();
    $selected_forms = $customer->getSelectedForms();

    // Check if user has access to this form
    if (!in_array($form_id, $selected_forms)) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    // Get form info and submissions
    $all_forms = $this->netlifyApi->getForms($site_id);
    $form_info = null;
    foreach ($all_forms as $form) {
      if ($form['id'] === $form_id) {
        $form_info = $form;
        break;
      }
    }

    if (!$form_info) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // $submissions = $this->netlifyApi->getSubmissions($site_id, $form_id);
    $submissions = $this->getLocalSubmissions($customer->id(), $form_id);
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-submissions-page']],
      '#attached' => [
        'library' => ['netlify_forms/submissions_styling'],
      ],
    ];

    // Breadcrumb-style back link
    $build['back_link'] = [
      '#type' => 'link',
      '#title' => $this->t('← Back to forms'),
      '#url' => Url::fromRoute('netlify_forms.user_submissions'),
      '#attributes' => ['class' => ['back-link']],
    ];

    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['submissions-header']],
    ];

    $build['header']['title'] = [
      '#markup' => '<h1 class="page-title">' . $this->t('Submissions for @form', ['@form' => $form_info['name']]) . '</h1>',
    ];

    $build['export_links'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['submissions-export']],
    ];

    $build['export_links']['export_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Download as CSV'),
      '#url' => Url::fromRoute('netlify_forms.export_csv', ['form_id' => $form_id]),
      '#attributes' => ['class' => ['button', 'export-button']],
    ];

    // Page header
    if (!empty($submissions)) {
      $rows = [];
      foreach ($submissions as $submission) {
        $created = date('M j, Y g:i A', strtotime($submission['created_at']));
        $detail_url = Url::fromRoute('netlify_forms.submission_detail', [
          'form_id' => $form_id,
          'submission_id' => $submission['id'],
        ]);

        $rows[] = [
          'created' => $created,
          'title' => $submission['name'] ?? $this->t('Untitled submission'),
          'email' => $submission['email'] ?? $this->t('No email'),
          'actions' => Link::fromTextAndUrl($this->t('View Details'), $detail_url)->toString(),
        ];
      }

      $build['submissions_table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Date'),
          $this->t('Title'),
          $this->t('Email'),
          $this->t('Actions'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No submissions found.'),
        '#attributes' => ['class' => ['submissions-table']],
      ];
    }
    else {
      $build['empty'] = [
        '#markup' => '<div class="empty-state">' . $this->t('No submissions found for this form.') . '</div>',
      ];
    }

    return $build;
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
   * Display customer forms management page.
   */
  public function customerForms($netlify_customer) {
    // Load the customer entity if we received an ID string
    if (is_string($netlify_customer) || is_numeric($netlify_customer)) {
      $storage = \Drupal::entityTypeManager()->getStorage('netlify_customer');
      $customer = $storage->load($netlify_customer);

      if (!$customer) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      }
    } else {
      $customer = $netlify_customer;
    }

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['netlify-customer-forms']],
      '#attached' => [
        'library' => ['netlify_forms/webhook_admin'],
      ],
    ];

    $build['info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['customer-info']],
    ];

    $build['info']['name'] = [
      '#markup' => '<h2>' . $this->t('Forms for: @name', ['@name' => $customer->getName()]) . '</h2>',
    ];

    $build['info']['site_id'] = [
      '#markup' => '<p>' . $this->t('Site ID: @site_id', ['@site_id' => $customer->getSiteId()]) . '</p>',
    ];

    // Add webhook URL helper
    $webhook_url = \Drupal::request()->getSchemeAndHttpHost() . '/webhooks/netlify/' . $customer->getSiteId();
    $build['webhook_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Webhook Configuration'),
      '#open' => FALSE,
    ];

    $build['webhook_info']['url_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['webhook-url-container']],
    ];

    $build['webhook_info']['url_container']['label'] = [
      '#markup' => '<label><strong>' . $this->t('Webhook URL:') . '</strong></label><br>',
    ];

    $build['webhook_info']['url_container']['url'] = [
      '#markup' => '<code class="webhook-url">' . $webhook_url . '</code>',
    ];

    $build['webhook_info']['url_container']['copy_button'] = [
      '#type' => 'button',
      '#value' => $this->t('📋 Copy'),
      '#attributes' => [
        'class' => ['copy-webhook-url', 'copy-button'],
        'data-webhook-url' => $webhook_url,
        'type' => 'button',
      ],
    ];

    $build['webhook_info']['instructions'] = [
      '#markup' => '<div class="webhook-instructions">' .
        '<p><strong>' . $this->t('Setup Instructions:') . '</strong></p>' .
        '<ol>' .
        '<li>' . $this->t('Go to your Netlify site dashboard') . '</li>' .
        '<li>' . $this->t('Navigate to Settings → Forms → Form notifications') . '</li>' .
        '<li>' . $this->t('Add a webhook with the URL above') . '</li>' .
        '<li>' . $this->t('Set event to "Form submission"') . '</li>' .
        '</ol>' .
        '</div>',
    ];

    // Get all available forms
    $all_forms = $this->netlifyApi->getForms($customer->getSiteId());
    $selected_forms = $customer->getSelectedForms();

    if (empty($all_forms)) {
      $build['no_forms'] = [
        '#markup' => $this->t('No forms found for this site. Please check the site ID and API configuration.'),
      ];
      return $build;
    }

    $build['forms_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Form Name'),
        $this->t('Form ID'),
        $this->t('Selected'),
        $this->t('Submission Count'),
        $this->t('Created'),
      ],
    ];

    foreach ($all_forms as $form) {
      // $submissions = $this->netlifyApi->getSubmissions($customer->getSiteId(), $form['id']);
      $submissions = $this->getLocalSubmissions($customer->id(), $form['id']);
      $is_selected = in_array($form['id'], $selected_forms);

      $build['forms_table'][] = [
        'name' => ['#markup' => $form['name']],
        'id' => ['#markup' => $form['id']],
        'selected' => ['#markup' => $is_selected ? $this->t('Yes') : $this->t('No')],
        'count' => ['#markup' => count($submissions)],
        'created' => ['#markup' => isset($form['created_at']) ? date('Y-m-d', strtotime($form['created_at'])) : ''],
      ];
    }

    $build['edit_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Edit Customer & Form Selection'),
      '#url' => $customer->toUrl('edit-form'),
      '#attributes' => ['class' => ['button']],
    ];

    $build['sync_button'] = [
      '#type' => 'link',
      '#title' => $this->t('🔄 Sync All Submissions'),
      '#url' => Url::fromRoute('netlify_forms.sync_submissions', ['customer_id' => $customer->id()]),
      '#attributes' => ['class' => ['button', 'button--primary', 'sync-button']],
    ];

    return $build;
  }

  /**
   * Display user's customer profile.
   */
  public function myCustomerProfile() {
    $current_user = $this->currentUser();
    $customer = $this->getCustomerForUser($current_user->id());

    if (!$customer) {
      return [
        '#markup' => $this->t('No customer profile found. Please contact an administrator to set up your account.'),
      ];
    }

    // Redirect to the customer edit form
    $url = $customer->toUrl('edit-form');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

  /**
   * Display submission details.
   */
  public function submissionDetail($form_id, $submission_id) {
    $current_user = $this->currentUser();

    // Get customer entity for current user
    $customer = $this->getCustomerForUser($current_user->id());

    if (!$customer) {
      return [
        '#markup' => $this->t('No customer profile found.'),
      ];
    }

    $submission = $this->netlifyApi->getSubmission($customer->getSiteId(), $submission_id);

    if (!$submission) {
      return [
        '#markup' => $this->t('Submission not found.'),
      ];
    }

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['netlify-submission-detail']],
    ];

    $all_forms = $this->netlifyApi->getForms($customer->getSiteId());
    $form_name = 'form';
    foreach ($all_forms as $form) {
      if ($form['id'] === $form_id) {
        $form_name = $form['name'];
        break;
      }
    }

    // Add back link to the form submissions page
    $build['back_link'] = [
      '#type' => 'link',
      '#title' => $this->t('← Back to @form submissions', ['@form' => $form_name]),
      '#url' => Url::fromRoute('netlify_forms.form_submissions', [
        'form_id' => $form_id,
      ]),
      '#attributes' => ['class' => ['back-link']],
    ];

    $build['info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['submission-info']],
    ];

    $build['info']['created'] = [
      '#type' => 'item',
      '#title' => $this->t('Created'),
      '#markup' => date('Y-m-d H:i:s', strtotime($submission['created_at'])),
    ];

    if (isset($submission['email'])) {
      $build['info']['email'] = [
        '#type' => 'item',
        '#title' => $this->t('Email'),
        '#markup' => $submission['email'],
      ];
    }

    $build['data'] = [
      '#type' => 'details',
      '#title' => $this->t('Form Data'),
      '#open' => TRUE,
    ];

    if (isset($submission['data']) && is_array($submission['data'])) {
      $rows = [];
      foreach ($submission['data'] as $key => $value) {
        $rows[] = [
          'field' => $key,
          'value' => is_array($value) ? implode(', ', $value) : $value,
        ];
      }

      $build['data']['table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Field'),
          $this->t('Value'),
        ],
        '#rows' => $rows,
      ];
    }

    return $build;
  }

/**
   * Export submissions as CSV.
   */
  public function exportSubmissions($form_id, Request $request) {
    $current_user = $this->currentUser();

    // Get customer entity for current user
    $customer = $this->getCustomerForUser($current_user->id());

    if (!$customer) {
      $this->messenger()->addError($this->t('No customer profile found.'));
      return $this->redirect('netlify_forms.user_submissions');
    }

    $site_id = $customer->getSiteId();
    $selected_forms = $customer->getSelectedForms();

    // Check if user has access to this form
    if (!in_array($form_id, $selected_forms)) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    // Get form info and submissions
    $all_forms = $this->netlifyApi->getForms($site_id);
    $form_info = null;
    foreach ($all_forms as $form) {
      if ($form['id'] === $form_id) {
        $form_info = $form;
        break;
      }
    }

    if (!$form_info) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // $submissions = $this->netlifyApi->getSubmissions($site_id, $form_id);
    $submissions = $this->getLocalSubmissions($customer->id(), $form_id);
    $submission_count = count($submissions);

    // If large dataset, use batch processing
    if ($submission_count > 500) {
      return $this->batchExport($form_id, $form_info, $submissions);
    }

    // Direct export for smaller datasets
    return $this->directExport($form_id, $form_info, $submissions);
  }

  /**
   * Direct CSV export for smaller datasets.
   */
  protected function directExport($form_id, $form_info, $submissions) {
    $csv_data = $this->prepareCSVData($submissions);

    $response = new \Symfony\Component\HttpFoundation\Response();
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="' .
      $this->sanitizeFilename($form_info['name']) . '_submissions_' . date('Y-m-d') . '.csv"');

    $response->setContent($csv_data);

    return $response;
  }

  /**
   * Batch export for large datasets.
   */
  protected function batchExport($form_id, $form_info, $submissions) {
    $batch_size = 500;
    $total_submissions = count($submissions);
    $batches = array_chunk($submissions, $batch_size);

    $batch = [
      'title' => $this->t('Exporting @count submissions...', ['@count' => $total_submissions]),
      'operations' => [],
      'finished' => '\Drupal\netlify_forms\Controller\NetlifyFormsController::batchExportFinished',
    ];

    foreach ($batches as $batch_index => $batch_submissions) {
      $batch['operations'][] = [
        '\Drupal\netlify_forms\Controller\NetlifyFormsController::batchExportProcess',
        [$batch_submissions, $form_info, $batch_index, count($batches)],
      ];
    }

    batch_set($batch);

    return batch_process(Url::fromRoute('netlify_forms.form_submissions', ['form_id' => $form_id]));
  }

  /**
   * Batch export process callback.
   */
  public static function batchExportProcess($submissions, $form_info, $batch_index, $total_batches, &$context) {
    if (!isset($context['sandbox']['csv_data'])) {
      $context['sandbox']['csv_data'] = '';
      $context['sandbox']['headers_added'] = FALSE;
    }

    $csv_data = self::prepareCSVDataStatic($submissions, !$context['sandbox']['headers_added']);
    $context['sandbox']['csv_data'] .= $csv_data;
    $context['sandbox']['headers_added'] = TRUE;

    $context['message'] = t('Processing batch @current of @total...', [
      '@current' => $batch_index + 1,
      '@total' => $total_batches,
    ]);

    $context['finished'] = ($batch_index + 1) / $total_batches;

    // Store form info for the finished callback
    $context['sandbox']['form_info'] = $form_info;
  }

  /**
   * Batch export finished callback.
   */
  public static function batchExportFinished($success, $results, $operations, &$context) {
    if ($success) {
      $form_info = $context['sandbox']['form_info'];
      $csv_data = $context['sandbox']['csv_data'];

      // Store CSV data in temporary storage for download
      $temp_store = \Drupal::service('tempstore.private')->get('netlify_forms');
      $download_key = 'csv_export_' . \Drupal::currentUser()->id() . '_' . time();
      $temp_store->set($download_key, [
        'data' => $csv_data,
        'filename' => self::sanitizeFilenameStatic($form_info['name']) . '_submissions_' . date('Y-m-d') . '.csv',
      ]);

      \Drupal::messenger()->addMessage(t('Export completed! <a href="@download_url">Download your CSV file</a>.', [
        '@download_url' => Url::fromRoute('netlify_forms.download_csv', ['key' => $download_key])->toString(),
      ]));
    } else {
      \Drupal::messenger()->addError(t('Export failed. Please try again.'));
    }
  }

  /**
   * Download CSV file from temporary storage.
   */
  public function downloadCsv($key) {
    $temp_store = \Drupal::service('tempstore.private')->get('netlify_forms');
    $export_data = $temp_store->get($key);

    if (!$export_data) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // Clean up temporary data
    $temp_store->delete($key);

    $response = new \Symfony\Component\HttpFoundation\Response();
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $export_data['filename'] . '"');
    $response->setContent($export_data['data']);

    return $response;
  }

  /**
   * Prepare CSV data from submissions.
   */
  protected function prepareCSVData($submissions, $include_headers = TRUE) {
    return self::prepareCSVDataStatic($submissions, $include_headers);
  }

  /**
   * Static version for batch processing.
   */
  public static function prepareCSVDataStatic($submissions, $include_headers = TRUE) {
    if (empty($submissions)) {
      return '';
    }

    // Get all possible field names from all submissions
    $all_fields = ['id', 'created_at', 'email', 'name'];
    foreach ($submissions as $submission) {
      if (isset($submission['data']) && is_array($submission['data'])) {
        $all_fields = array_merge($all_fields, array_keys($submission['data']));
      }
    }
    $all_fields = array_unique($all_fields);

    $csv_output = '';

    // Add headers
    if ($include_headers) {
      $csv_output .= '"' . implode('","', $all_fields) . '"' . "\n";
    }

    // Add data rows
    foreach ($submissions as $submission) {
      $row = [];
      foreach ($all_fields as $field) {
        if ($field === 'created_at') {
          $row[] = isset($submission['created_at']) ? date('Y-m-d H:i:s', strtotime($submission['created_at'])) : '';
        } elseif (in_array($field, ['id', 'email', 'name'])) {
          $row[] = $submission[$field] ?? '';
        } else {
          // Field from data array
          $value = '';
          if (isset($submission['data'][$field])) {
            $value = is_array($submission['data'][$field])
              ? implode('; ', $submission['data'][$field])
              : $submission['data'][$field];
          }
          $row[] = $value;
        }
      }

      // Escape and quote each field
      $escaped_row = array_map(function($field) {
        return '"' . str_replace('"', '""', $field) . '"';
      }, $row);

      $csv_output .= implode(',', $escaped_row) . "\n";
    }

    return $csv_output;
  }

  /**
   * Sanitize filename for download.
   */
  protected function sanitizeFilename($filename) {
    return self::sanitizeFilenameStatic($filename);
  }

  /**
   * Static version for batch processing.
   */
  public static function sanitizeFilenameStatic($filename) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
  }

  /**
   * Sync all submissions for a customer from Netlify API.
   */
  public function syncSubmissions($customer_id) {
    $storage = \Drupal::entityTypeManager()->getStorage('netlify_customer');
    $customer = $storage->load($customer_id);

    if (!$customer) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $site_id = $customer->getSiteId();
    $selected_forms = $customer->getSelectedForms();

    if (empty($selected_forms)) {
      $this->messenger()->addWarning($this->t('No forms selected for this customer.'));
      return $this->redirect('entity.netlify_customer.canonical', ['netlify_customer' => $customer_id]);
    }

    $total_synced = 0;
    $total_skipped = 0;

    foreach ($selected_forms as $form_id) {
      // $submissions = $this->netlifyApi->getSubmissions($site_id, $form_id);
      $submissions = $this->getLocalSubmissions($customer->id(), $form_id);

      foreach ($submissions as $submission) {
        // Check if already exists
        $exists = \Drupal::database()->select('netlify_submissions', 'ns')
          ->fields('ns', ['id'])
          ->condition('netlify_submission_id', $submission['id'])
          ->execute()
          ->fetchField();

        if (!$exists) {
          // Store new submission
          $created_at = isset($submission['created_at']) ? strtotime($submission['created_at']) : time();

          \Drupal::database()->insert('netlify_submissions')
            ->fields([
              'customer_id' => $customer->id(),
              'site_id' => $site_id,
              'form_id' => $form_id,
              'form_name' => $submission['form_name'] ?? '',
              'netlify_submission_id' => $submission['id'],
              'submission_data' => json_encode($submission),
              'email' => $submission['email'] ?? '',
              'submission_name' => $submission['name'] ?? ($submission['summary'] ?? ''),
              'created_at' => $created_at,
              'received_at' => time(),
            ])
            ->execute();

          $total_synced++;
        } else {
          $total_skipped++;
        }
      }
    }

    $this->messenger()->addMessage($this->t('Sync complete! @synced new submissions added, @skipped already existed.', [
      '@synced' => $total_synced,
      '@skipped' => $total_skipped,
    ]));

    return $this->redirect('entity.netlify_customer.canonical', ['netlify_customer' => $customer_id]);
  }

  /**
   * Get local submissions from database.
   */
  protected function getLocalSubmissions($customer_id, $form_id = NULL) {
    $query = \Drupal::database()->select('netlify_submissions', 'ns')
      ->fields('ns')
      ->condition('customer_id', $customer_id)
      ->orderBy('created_at', 'DESC');

    if ($form_id) {
      $query->condition('form_id', $form_id);
    }

    $results = $query->execute()->fetchAll();

    // Convert to Netlify API format
    $submissions = [];
    foreach ($results as $row) {
      $data = json_decode($row->submission_data, TRUE);
      $submissions[] = [
        'id' => $row->netlify_submission_id,
        'created_at' => date('c', $row->created_at),
        'email' => $row->email,
        'name' => $row->submission_name,
        'data' => $data['data'] ?? [],
      ];
    }

    return $submissions;
  }

}

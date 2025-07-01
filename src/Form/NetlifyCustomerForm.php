<?php

/**
 * @file
 * Contains \Drupal\netlify_forms\Form\NetlifyCustomerForm.
 *
 * LOCATION: modules/custom/netlify_forms/src/Form/NetlifyCustomerForm.php
 */

namespace Drupal\netlify_forms\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\netlify_forms\Service\NetlifyApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Netlify Customer edit forms.
 */
class NetlifyCustomerForm extends ContentEntityForm {

  /**
   * The Netlify API service.
   *
   * @var \Drupal\netlify_forms\Service\NetlifyApiService
   */
  protected $netlifyApi;

  /**
   * Constructs a NetlifyCustomerForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\netlify_forms\Service\NetlifyApiService $netlify_api
   *   The Netlify API service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    NetlifyApiService $netlify_api
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->netlifyApi = $netlify_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('netlify_forms.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\netlify_forms\Entity\NetlifyCustomerInterface $entity */
    $entity = $this->entity;

    // If we have a site ID, fetch available forms
    if ($entity->getSiteId()) {
      $this->addFormsSelection($form, $form_state, $entity);
    }

    return $form;
  }

  /**
   * Add forms selection to the form.
   */
  protected function addFormsSelection(array &$form, FormStateInterface $form_state, $entity) {
    $forms = $this->netlifyApi->getForms($entity->getSiteId());

    if (!empty($forms)) {
      $options = [];
      foreach ($forms as $form_data) {
        $options[$form_data['id']] = $form_data['name'] . ' (' . $form_data['id'] . ')';
      }

      $form['forms_selection'] = [
        '#type' => 'details',
        '#title' => $this->t('Available Forms'),
        '#open' => TRUE,
        '#weight' => 10,
      ];

      $form['forms_selection']['available_forms'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select Forms'),
        '#description' => $this->t('Choose which forms this customer can view submissions for.'),
        '#options' => $options,
        '#default_value' => $entity->getSelectedForms(),
      ];
    }
    else {
      $form['forms_selection'] = [
        '#type' => 'details',
        '#title' => $this->t('Forms'),
        '#open' => TRUE,
        '#weight' => 10,
      ];

      $form['forms_selection']['no_forms'] = [
        '#markup' => $this->t('No forms found for this site ID. Please check the site ID and API configuration.'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle forms selection if present
    if ($form_state->hasValue('available_forms')) {
      $selected_forms = array_filter($form_state->getValue('available_forms'));
      $this->entity->setSelectedForms(array_keys($selected_forms));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Netlify Customer.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Netlify Customer.', [
          '%label' => $entity->label(),
        ]));
    }

    $form_state->setRedirect('entity.netlify_customer.canonical', ['netlify_customer' => $entity->id()]);

    return $status;
  }

}

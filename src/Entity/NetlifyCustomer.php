<?php

/**
 * @file
 * Defines the Netlify Customer entity for managing customer-site relationships.
 */

namespace Drupal\netlify_forms\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Netlify Customer entity.
 *
 * @ContentEntityType(
 *   id = "netlify_customer",
 *   label = @Translation("Netlify Customer"),
 *   label_collection = @Translation("Netlify Customers"),
 *   label_singular = @Translation("netlify customer"),
 *   label_plural = @Translation("netlify customers"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\netlify_forms\Entity\NetlifyCustomerListBuilder",
 *     "form" = {
 *       "default" = "Drupal\netlify_forms\Form\NetlifyCustomerForm",
 *       "add" = "Drupal\netlify_forms\Form\NetlifyCustomerForm",
 *       "edit" = "Drupal\netlify_forms\Form\NetlifyCustomerForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\netlify_forms\Access\NetlifyCustomerAccessControlHandler",
 *   },
 *   base_table = "netlify_customer",
 *   translatable = FALSE,
 *   admin_permission = "manage netlify customers",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/netlify-customers/{netlify_customer}",
 *     "add-form" = "/admin/content/netlify-customers/add",
 *     "edit-form" = "/admin/content/netlify-customers/{netlify_customer}/edit",
 *     "delete-form" = "/admin/content/netlify-customers/{netlify_customer}/delete",
 *     "collection" = "/admin/content/netlify-customers",
 *   },
 *   field_ui_base_route = "netlify_forms.admin_settings",
 * )
 */
class NetlifyCustomer extends ContentEntityBase implements NetlifyCustomerInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteId() {
    return $this->get('site_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSiteId($site_id) {
    $this->set('site_id', $site_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser(UserInterface $user) {
    $this->set('user_id', $user->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedForms() {
    $forms = [];
    foreach ($this->get('selected_forms') as $item) {
      $forms[] = $item->value;
    }
    return $forms;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectedForms(array $forms) {
    $this->set('selected_forms', $forms);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Set created time for new entities.
    if ($this->isNew()) {
      $this->setCreatedTime(\Drupal::time()->getRequestTime());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer Name'))
      ->setDescription(t('The name of the customer.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Account'))
      ->setDescription(t('The user account associated with this customer.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['site_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Netlify Site ID'))
      ->setDescription(t('The Netlify site ID for this customer.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['selected_forms'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Selected Forms'))
      ->setDescription(t('The selected forms for this customer.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}

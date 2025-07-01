<?php

/**
 * @file
 * Contains \Drupal\netlify_forms\Entity\NetlifyCustomerListBuilder.
 *
 * LOCATION: modules/custom/netlify_forms/src/Entity/NetlifyCustomerListBuilder.php
 */

namespace Drupal\netlify_forms\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Netlify Customer entities.
 */
class NetlifyCustomerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Customer Name');
    $header['user'] = $this->t('User Account');
    $header['site_id'] = $this->t('Site ID');
    $header['forms_count'] = $this->t('Selected Forms');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\netlify_forms\Entity\NetlifyCustomerInterface $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->getName(),
      'entity.netlify_customer.edit_form',
      ['netlify_customer' => $entity->id()]
    );
    $row['user'] = $entity->getUser() ? $entity->getUser()->getDisplayName() : $this->t('No user');
    $row['site_id'] = $entity->getSiteId();
    $row['forms_count'] = count($entity->getSelectedForms());
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    $operations['forms'] = [
      'title' => $this->t('Manage Forms'),
      'weight' => 10,
      'url' => Url::fromRoute('netlify_forms.customer.forms', [
        'netlify_customer' => $entity->id(),
      ]),
    ];

    return $operations;
  }

}

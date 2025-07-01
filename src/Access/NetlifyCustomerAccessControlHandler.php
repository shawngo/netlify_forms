<?php

/**
 * @file
 * Contains \Drupal\netlify_forms\Access\NetlifyCustomerAccessControlHandler.
 *
 * LOCATION: modules/custom/netlify_forms/src/Access/NetlifyCustomerAccessControlHandler.php
 */

namespace Drupal\netlify_forms\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Netlify Customer entity.
 */
class NetlifyCustomerAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\netlify_forms\Entity\NetlifyCustomerInterface $entity */

    switch ($operation) {
      case 'view':
        // Admins can view all, users can view their own
        if ($account->hasPermission('manage netlify customers')) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('edit own netlify customer') &&
            $entity->getUser() && $entity->getUser()->id() == $account->id()) {
          return AccessResult::allowed();
        }
        return AccessResult::forbidden();

      case 'update':
        // Admins can edit all, users can edit their own
        if ($account->hasPermission('manage netlify customers')) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('edit own netlify customer') &&
            $entity->getUser() && $entity->getUser()->id() == $account->id()) {
          return AccessResult::allowed();
        }
        return AccessResult::forbidden();

      case 'delete':
        // Only admins can delete
        return AccessResult::allowedIfHasPermission($account, 'manage netlify customers');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage netlify customers');
  }

}

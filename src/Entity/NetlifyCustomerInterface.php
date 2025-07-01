<?php

/**
 * @file
 * Interface definition for Netlify Customer entity methods.
 */

namespace Drupal\netlify_forms\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining Netlify Customer entities.
 */
interface NetlifyCustomerInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the customer name.
   *
   * @return string
   *   Name of the customer.
   */
  public function getName();

  /**
   * Sets the customer name.
   *
   * @param string $name
   *   The customer name.
   *
   * @return \Drupal\netlify_forms\Entity\NetlifyCustomerInterface
   *   The called customer entity.
   */
  public function setName($name);

  /**
   * Gets the site ID.
   *
   * @return string
   *   The Netlify site ID.
   */
  public function getSiteId();

  /**
   * Sets the site ID.
   *
   * @param string $site_id
   *   The Netlify site ID.
   *
   * @return \Drupal\netlify_forms\Entity\NetlifyCustomerInterface
   *   The called customer entity.
   */
  public function setSiteId($site_id);

  /**
   * Gets the associated user.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  public function getUser();

  /**
   * Sets the associated user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return \Drupal\netlify_forms\Entity\NetlifyCustomerInterface
   *   The called customer entity.
   */
  public function setUser(UserInterface $user);

  /**
   * Gets the selected forms.
   *
   * @return array
   *   Array of selected form IDs.
   */
  public function getSelectedForms();

  /**
   * Sets the selected forms.
   *
   * @param array $forms
   *   Array of form IDs.
   *
   * @return \Drupal\netlify_forms\Entity\NetlifyCustomerInterface
   *   The called customer entity.
   */
  public function setSelectedForms(array $forms);

  /**
   * Gets the entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the entity.
   */
  public function getCreatedTime();

  /**
   * Sets the entity creation timestamp.
   *
   * @param int $timestamp
   *   The entity creation timestamp.
   *
   * @return \Drupal\netlify_forms\Entity\NetlifyCustomerInterface
   *   The called customer entity.
   */
  public function setCreatedTime($timestamp);

}

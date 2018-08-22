<?php

namespace Drupal\civicrm;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Migrates permissions from CiviCRM to Drupal.
 */
class CivicrmPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Returns an array of CiviCRM's basic permissions.
   *
   * @return array
   *   An array of all permissions, keyed by the machine name.
   */
  public function permissions() {
    // Initialize civicrm.
    // @Todo: Inject this via container injection instead.
    \Drupal::service('civicrm')->initialize();

    $permissions = [];
    foreach (\CRM_Core_Permission::basicPermissions() as $permission => $title) {
      // @codingStandardsIgnoreStart
      $permissions[$permission] = ['title' => $this->t($title)];
      // @codingStandardsIgnoreEnd
    }
    return $permissions;
  }

}

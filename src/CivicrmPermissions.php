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
   * Class constructor.
   */
  public function __construct(Civicrm $civicrm) {
    $civicrm->initialize();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civicrm')
    );
  }

  /**
   * Returns an array of CiviCRM's basic permissions.
   *
   * @return array
   *   An array of all permissions, keyed by the machine name.
   */
  public function permissions() {
    $permissions = [];
    foreach (\CRM_Core_Permission::basicPermissions() as $permission => $title) {
      // @codingStandardsIgnoreStart
      $permissions[$permission] = ['title' => $this->t($title)];
      // @codingStandardsIgnoreEnd
    }
    return $permissions;
  }

}

<?php

namespace Drupal\civicrm\Commands;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
class CivicrmCommands extends DrushCommands {

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * TokenCommands constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module_handler service.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Adds a cache clear option for civicrm.
   *
   * @param array $types
   *   The Drush clear types to make available.
   * @param bool $includeBootstrappedTypes
   *   Whether to include types only available in a bootstrapped Drupal or not.
   *
   * @hook on-event cache-clear
   */
  public function cacheClear(array &$types, $includeBootstrappedTypes) {
    if ($includeBootstrappedTypes && $this->moduleHandler->moduleExists('civicrm')) {
      $types['civicrm'] = 'civicrm_clear_cache';
    }
  }
}

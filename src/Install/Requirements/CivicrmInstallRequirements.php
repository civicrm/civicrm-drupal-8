<?php

declare(strict_types=1);

namespace Drupal\civicrm\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;

class CivicrmInstallRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements = [];

    // modified version of _civicrm_find_civicrm() from civicrm.install
    // Move to a service?
    $possible_paths = [];
    if ($path = \Drupal::service('extension.list.module')->getPath('civicrm')) {
      $possible_paths[] = $path;
    }
    $possible_paths[] = 'vendor/civicrm/civicrm-core';
    $possible_paths[] = '../vendor/civicrm/civicrm-core';

    foreach ($possible_paths as $path) {
      if (file_exists($path . '/CRM/Core/ClassLoader.php')) {
        $civicrm_base = \Drupal::service('file_system')->realpath($path);
        break;
      }
    }

    // Introduced in 11.2, and then compatibility layer removed in 11.3, so we
    // have to support both.
    $severityMap = [
      'info' => RequirementSeverity::OK,
      'warning' => RequirementSeverity::Warning,
      'error' => RequirementSeverity::Error,
    ];

    if ($civicrm_base) {
      $requirements['civicrm.location'] = [
        'title' => 'CiviCRM location',
        'severity' => $severityMap['info'],
        'description' => 'CiviCRM core directory',
      ];
    }
    else {
      $requirements['civicrm.location'] = [
        'title' => 'CiviCRM location',
        'severity' => $severityMap['error'],
        'description' => 'CiviCRM must be installed via composer.',
      ];
      return $requirements;
    }

    // Modification of _civicrm_setup()
    // Move to a service?
    if (defined('CIVI_SETUP')) {
      /** @var \Civi\Setup $setup */
      $setup = \Civi\Setup::instance();
    }
    else {
      if ($civicrm_base) {
        require_once $civicrm_base . '/CRM/Core/ClassLoader.php';
        \CRM_Core_ClassLoader::singleton()->register();

        \Civi\Setup::assertProtocolCompatibility(1.0);
        \Civi\Setup::init([
          'cms' => 'Drupal8',
          'srcPath' => $civicrm_base,
        ]);
        /** @var \Civi\Setup $setup */
        $setup = \Civi\Setup::instance();
      }
    }

    if (!$setup->checkAuthorized()->isAuthorized()) {
      $requirements['civicrm.checkAuthorized'] = [
        'title' => 'CiviCRM Installation Not Authorized',
        'description' => 'The current user does not have sufficient permissions to perform installation.',
        'severity' => $severityMap['warning'],
      ];
      return $requirements;
    }

    $sections = [
      'system' => 'CiviCRM: System',
      'database' => 'CiviCRM: Database',
      'other' => 'CiviCRM: Other',
    ];


    foreach ($setup->checkRequirements()->getMessages() as $msg) {
      $section = isset($sections[$msg['section']]) ? $sections[$msg['section']] : $sections['other'];
      $key = 'civicrm.' . $msg['section'] . '.' . $msg['name'];
      $requirements[$key] = [
        'title' => $section . ': ' . $msg['message'],
        'description' => $section . ': ' . $msg['message'],
        'severity' => $severityMap[$msg['severity']],
      ];
    }


    ksort($requirements);

    return $requirements;
  }

}

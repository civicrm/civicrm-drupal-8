<?php

namespace Drupal\civicrm;

use Drupal\civicrm\Exception\CiviCRMConfigException;
use Drupal\Core\Session\AccountInterface;

/**
 * Connects the Drupal instance to CiviCRM.
 */
class Civicrm {

  /**
   * Static cache.
   *
   * @var bool
   */
  protected static $initialized = FALSE;

  /**
   * Initialize CiviCRM.
   *
   * Call this function from other modules too if they use the CiviCRM API.
   */
  public function initialize() {
    if ($this->isInitialized()) {
      return;
    }

    $settingsPath = \Drupal::service('kernel')->getSitePath() . '/civicrm.settings.php';
    \Civi\Core\LegacyClassLoader::register();
    \Civi\Core\SettingsManager::bootSettings($settingsPath);

    // Initialize the system by creating a config object.
    \CRM_Core_Config::singleton();

    // Set timezone - TODO: can we use a CRM_Util_System hook for this?
    \CRM_Core_Config::singleton()->userSystem->setMySQLTimeZone();

    // Mark CiviCRM as initialized.
    static::$initialized = TRUE;
  }

  /**
   * Checks if the CiviCRM link is already initialized.
   */
  public function isInitialized() {
    return static::$initialized;
  }

  /**
   * Invoke a CiviCRM method from Drupal.
   *
   * Wraps around \CRM_Core_Invoke::invoke.
   */
  public function invoke($args) {
    $this->initialize();

    // Add CSS, JS, etc. that is required for this page.
    \CRM_Core_Resources::singleton()->addCoreResources();

    // CiviCRM will echo/print directly to stdout. We need to capture it so that
    // we can return the output as a renderable array.
    ob_start();
    $content = \CRM_Core_Invoke::invoke($args);
    $output = ob_get_clean();
    return !empty($content) ? $content : $output;
  }

  /**
   * Synchronize a Drupal account with CiviCRM.
   *
   * This is a wrapper for CRM_Core_BAO_UFMatch::synchronize().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The drupal user.
   * @param string $contact_type
   *   The user contact type.
   */
  public function synchronizeUser(AccountInterface $account, $contact_type = 'Individual') {
    $this->initialize();
    \CRM_Core_BAO_UFMatch::synchronize($account, FALSE, 'Drupal', $this->getCtype($contact_type));
  }

  /**
   * Function to get the contact type.
   *
   * @param string $default
   *   Default contact type.
   *
   * @return string
   *   The contact type.
   *
   * @Todo: Document what this function is doing and why.
   */
  public function getCtype($default = 'Individual') {
    if (!empty($_REQUEST['ctype'])) {
      $ctype = $_REQUEST['ctype'];
    }
    elseif (!empty($_REQUEST['edit']['ctype'])) {
      $ctype = $_REQUEST['edit']['ctype'];
    }
    else {
      $ctype = $default;
    }

    if (!in_array($ctype, ['Individual', 'Organization', 'Household'])) {
      $ctype = $default;
    }
    return $ctype;
  }

}

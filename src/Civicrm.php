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

    // Get ready for problems.
    $docLinkInstall = "http://wiki.civicrm.org/confluence/display/CRMDOC/Drupal+Installation+Guide";
    $docLinkTrouble = "http://wiki.civicrm.org/confluence/display/CRMDOC/Installation+and+Configuration+Trouble-shooting";
    $forumLink = "http://forum.civicrm.org/index.php/board,6.0.html";

    $errorMsgAdd = t("Please review the <a href='!1'>Drupal Installation Guide</a> and the <a href='!2'>Trouble-shooting page</a> for assistance. If you still need help installing, you can often find solutions to your issue by searching for the error message in the <a href='!3'>installation support section of the community forum</a>.</strong></p>",
      ['!1' => $docLinkInstall, '!2' => $docLinkTrouble, '!3' => $forumLink]
    );

    $settingsFile = $this->getSettingsFile();
    if (!defined('CIVICRM_SETTINGS_PATH')) {
      define('CIVICRM_SETTINGS_PATH', $settingsFile);
    }

    $output = include_once $settingsFile;
    if ($output == FALSE) {
      $msg = t("The CiviCRM settings file (civicrm.settings.php) was not found in the expected location: %location", ['%location' => $settingsFile]) . ' ' . $errorMsgAdd;
      throw new CiviCRMConfigException($msg);
    }

    // This does pretty much all of the civicrm initialization.
    $output = include_once 'CRM/Core/Config.php';
    if ($output == FALSE) {
      $msg = t("The path for including CiviCRM code files is not set properly. Most likely there is an error in the <em>civicrm_root</em> setting in your CiviCRM settings file (!1).",
          ['!1' => $settingsFile]
        ) . t("civicrm_root is currently set to: <em>!1</em>.", ['!1' => $civicrm_root]) . $errorMsgAdd;
      throw new CiviCRMConfigException($msg);
    }

    // Initialize the system by creating a config object.
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
   * Checks whether CiviCRM's settings file is in place.
   *
   * CiviCRM cannot be initialized without it, and civicrm_install() writes it.
   * Drupal installs a site's modules in groups, and on Drupal 12 a test's
   * modules share a group with user: their roles and users are saved before
   * civicrm_install() runs. Code reached from those saves, such as the
   * permission callback and the user hooks, finds no CiviCRM to ask yet.
   *
   * A settings file written before the module is installed passes this check
   * while CiviCRM's database is still empty; initialize() fails then as it
   * did before.
   *
   * @return bool
   *   TRUE if CiviCRM is initialized or its settings file exists.
   */
  public function hasSettingsFile(): bool {
    return $this->isInitialized() || stream_resolve_include_path($this->getSettingsFile()) !== FALSE;
  }

  /**
   * Returns the path of the settings file, as initialize() includes it.
   *
   * @return string
   *   The path, relative to the Drupal root.
   */
  protected function getSettingsFile(): string {
    return \Drupal::service('kernel')->getSitePath() . '/civicrm.settings.php';
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

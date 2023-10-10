<?php

namespace Drupal\civicrm\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\civicrm\Civicrm;

/**
 * A path processor to ensure we're using the correct routes in CiviCRM.
 */
class CivicrmPathProcessor implements InboundPathProcessorInterface {

  private static $knownPaths;
  private static $routesInitialized = FALSE;

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // If the path is a civicrm path.
    if (strpos($path, '/civicrm/') === 0) {
      // We've already looked up this path on this page request.
      if (isset(self::$knownPaths[$path])) {
        return self::$knownPaths[$path];
      }
      // Initialize civicrm.
      $civicrm = new Civicrm();
      $civicrm->initialize();
      $routesAreInDb = \CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) from civicrm_menu');
      if (!$routesAreInDb && !self::$routesInitialized) {
        self::$routesInitialized = TRUE;
        \CRM_Core_Menu::store();
      }
      $pathArray = \CRM_Core_Menu::get(ltrim($path, '/'));
      // Restore the leading slash.
      if ($pathArray['path'] ?? FALSE) {
        $newPath = '/' . $pathArray['path'];
        // Parse url component parameters from path.
        $params = str_replace($newPath, '', $path);
        // Replace slashes with colons and the controller will piece it back
        // together.
        if (strlen($params)) {
          $params = str_replace('/', ':', $params);
          $params = ltrim($params, ':');
          $newPath .= "/$params";
        }
        self::$knownPaths[$path] = $newPath;
        return $newPath;
      }
    }
    return $path;
  }

}

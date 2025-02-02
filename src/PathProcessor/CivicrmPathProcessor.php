<?php

namespace Drupal\civicrm\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\civicrm\Civicrm;

/**
 * A path processor to ensure we're using the correct routes in CiviCRM.
 */
class CivicrmPathProcessor implements InboundPathProcessorInterface {

  /**
   * If it's not a civi path, just return it as-is.
   * If we've looked up the path already, return that.
   * If there's a longest matching path in the list of defined urls, then fudge any parts of the url after that so that we don't get page not found. This is for weirdo urls like civicrm/report/instance/14, where for some reason the 14 is not a standard url parameter like ?id=14.
   *   If we could eliminate all those weird ones, then this function shouldn't be needed.
   * We also separately cache the list of defined urls, since it's expensive to build.
   * If there is no matching path in the list, just return as-is.
   */
  public function processInbound($path, Request $request) {
    // If the path is a civicrm path.
    if (strpos($path, '/civicrm/') === 0) {
      if (class_exists('Civi', FALSE) && isset(\Civi::$statics[__CLASS__]['knownPaths'][$path])) {
        return \Civi::$statics[__CLASS__]['knownPaths'][$path];
      }
      // Initialize civicrm.
      $civicrm = new Civicrm();
      $civicrm->initialize();
      // Fetch civicrm menu items.
      $items = \Civi::cache('long')->get('CivicrmPathProcessor-Core_Menu_items');
      if (empty($items)) {
        $items = \CRM_Core_Menu::items();
        \Civi::cache('long')->set('CivicrmPathProcessor-Core_Menu_items', $items);
      }
      \Civi::$statics[__CLASS__]['knownPaths'][$path] = $path;
      $longest = '';
      foreach (array_keys($items) as $item) {
        $item = '/' . $item;
        if ($path === $item) {
          $longest = $item;
          break;
        }
        if (str_starts_with($path, "$item/") && strlen($item) > strlen($longest)) {
          $longest = $item;
        }
      }
      if (!empty($longest)) {
        // Parse url component parameters from path.
        $params = str_replace($longest, '', $path);
        // Replace slashes with colons and the controller will piece it back
        // together.
        if (strlen($params)) {
          $params = str_replace('/', ':', $params);
          if (substr($params, 0, 1) == ':') {
            $params = substr($params, 1);
          }
          \Civi::$statics[__CLASS__]['knownPaths'][$path] = "$longest/$params";
        }
        else {
          \Civi::$statics[__CLASS__]['knownPaths'][$path] = $longest;
        }
      }
      return \Civi::$statics[__CLASS__]['knownPaths'][$path];
    }
    return $path;
  }

}

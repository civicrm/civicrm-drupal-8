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
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // If the path is a civicrm path.
    if (strpos($path, '/civicrm/') === 0) {
      // Initialize civicrm.
      $civicrm = new Civicrm();
      $civicrm->initialize();
      // Fetch civicrm menu items.
      $items = \CRM_Core_Menu::items();
      $longest = '';
      foreach (array_keys($items) as $item) {
        $item = '/' . $item;
        // If he current path is a civicrm path.
        if ((strpos($path, $item) === 0)) {
          // Discover longest matching civicrm path in the request path.
          if (strlen($item) > strlen($longest)) {
            $longest = $item;
          }
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
          return "$longest/$params";
        }
        else {
          return $longest;
        }
      }
    }
    return $path;
  }

}

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
   * The CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civiCRM;

  /**
   * CivicrmPathProcessor constructor.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   */
  public function __construct(Civicrm $civicrm) {
    $this->civiCRM = $civicrm;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/civicrm/') !== 0) {
      return $path;
    }

    // Fetch civicrm menu items.
    $this->civiCRM->initialize();
    $items = \CRM_Core_Menu::items();

    $longest = '';
    foreach (array_keys($items) as $item) {
      $item = '/' . $item;
      // If the current path is a civicrm path.
      if (strpos($path, $item) === 0) {
        // Discover longest matching civicrm path in the request path.
        if (strlen($item) > strlen($longest)) {
          $longest = $item;
        }
      }
    }

    if ($longest === '') {
      return $path;
    }

    // Parse url component parameters from path.
    $params = str_replace($longest, '', $path);
    // Replace slashes with colons and the controller will piece it back
    // together.
    if (strlen($params)) {
      $params = str_replace('/', ':', $params);
      // Params is a string, but we get the character at the first index and
      // check if it is a colon. If it is, we should remove that.
      if ($params[0] === ':') {
        $params = substr($params, 1);
      }
      return "$longest/$params";
    }

    return $longest;
  }

}

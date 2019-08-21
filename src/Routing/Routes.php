<?php

namespace Drupal\civicrm\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Contains all dynamic routes.
 */
class Routes {

  /**
   * Returns a new RouteCollection containing all menu items as routes.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   All CiviCRM menu items as routes.
   */
  public function listRoutes() {
    $collection = new RouteCollection();

    // Initialize CiviCRM.
    \Drupal::service('civicrm')->initialize();
    $d8System = \CRM_Core_Config::singleton()->userSystem;

    $items = \CRM_Core_Menu::items();

    // CiviCRM doesn't list optional path components. So we include 5 optional
    // components for each route, and let each default to empty string.
    foreach ($items as $path => $item) {
      $route = new Route(
        '/' . $path . '/{extra}',
        [
          '_title' => isset($item['title']) ? $item['title'] : 'CiviCRM',
          '_controller' => 'Drupal\civicrm\Controller\CivicrmController::main',
          'args' => explode('/', $path),
          'extra' => '',
        ],
        [
          '_access' => 'TRUE',
          'extra' => '.+',
        ]
      );
      $route_name = $d8System->parseUrl($path)['route_name'];
      $collection->add($route_name, $route);
    }

    return $collection;
  }

}

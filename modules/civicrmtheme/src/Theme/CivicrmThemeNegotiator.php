<?php

namespace Drupal\civicrmtheme\Theme;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Theme negotiator for CiviCRM pages.
 */
class CivicrmThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Civicrm service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * Constructs a CivicrmThemeNegotiator.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   */
  public function __construct(AccountInterface $user, ConfigFactoryInterface $config_factory, Civicrm $civicrm) {
    $this->user = $user;
    $this->configFactory = $config_factory;
    $this->civicrm = $civicrm;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();

    // Some pages, like 404 pages, don't have a route objet.
    if (!$route) {
      return FALSE;
    }

    $parts = explode('/', ltrim($route->getPath(), '/'));

    if ($parts[0] != 'civicrm') {
      return FALSE;
    }

    if (count($parts) > 1 && $parts[1] == 'upgrade') {
      return FALSE;
    }

    $config = $this->configFactory->get('civicrmtheme.settings');
    $admin_theme = $config->get('admin_theme');
    $public_theme = $config->get('public_theme');

    if (!$admin_theme && !$public_theme) {
      return FALSE;
    }

    // Attempt to initialize CiviCRM.
    try {
      $this->civicrm->initialize();
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $path = ltrim($route_match->getRouteObject()->getPath(), '/');

    $config = $this->configFactory->get('civicrmtheme.settings');
    $admin_theme = $config->get('admin_theme');
    $public_theme = $config->get('public_theme');

    // If neither the admin_theme or public theme have been set, we return NULL
    // to let Drupal choose the correct active theme.
    if (!$admin_theme && !$public_theme) {
      return NULL;
    }

    // If the public theme is configured and the user does not have permission
    // to access CiviCRM pages, use the public theme.
    if (!$this->user->hasPermission('access CiviCRM')) {
      if ($public_theme) {
        return $public_theme;
      }
      return NULL;
    }

    // Initialize CiviCRM and get the CiviCRM menu item definition for this
    // path.
    $this->civicrm->initialize();
    $item = \CRM_Core_Menu::get($path);

    // If the current menu item is public, we use the public theme, in other
    // cases the admin_theme is used.
    if (\CRM_Utils_Array::value('is_public', $item) && $public_theme) {
      return $public_theme;
    }

    // If the current menu item is not public apply civicrm admin theme.
    if (!\CRM_Utils_Array::value('is_public', $item) && ($admin_theme)) {
      return $admin_theme;
    }

    return NULL;
  }

}

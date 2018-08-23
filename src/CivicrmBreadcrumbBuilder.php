<?php

namespace Drupal\civicrm;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Provides a custom taxonomy breadcrumb builder that uses the term hierarchy.
 */
class CivicrmBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  protected $civicrmPageState;

  /**
   * Class constructor.
   */
  public function __construct(TranslationInterface $stringTranslation, CivicrmPageState $civicrmPageState) {
    $this->stringTranslation = $stringTranslation;
    $this->civicrmPageState = $civicrmPageState;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_object = $route_match->getRouteObject();
    if ($route_object) {
      $controller = $route_object->getDefault('_controller');
      if (isset($controller) && $controller == 'Drupal\civicrm\Controller\CivicrmController::main') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    foreach ($this->civicrmPageState->getBreadcrumbs() as $name => $url) {
      // All urls here have been passed trough CRM_Utils_System::url, so we have
      // to parse and decode them before creating a drupal Url object.
      $url = Url::fromUserInput(html_entity_decode($url));
      $breadcrumb->addLink(new Link($name, $url));
    }
    return $breadcrumb;
  }

}

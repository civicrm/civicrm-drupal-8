<?php

namespace Drupal\civicrm_views\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\civicrm\Civicrm;

/**
 * Wraps the In operator, to select the correct database first.
 *
 * @ingroup views_filter_handlers
 * @ViewsFilter("civicrm_in_operator")
 */
class CivicrmInOperator extends InOperator {

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Civicrm $civicrm) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $civicrm->initialize();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('civicrm')
    );
  }

}

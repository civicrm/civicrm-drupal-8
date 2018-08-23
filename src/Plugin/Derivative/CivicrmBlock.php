<?php

namespace Drupal\civicrm\Plugin\Derivative;

use Drupal\civicrm\Civicrm;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives CiviCRM blocks to Drupal blocks.
 */
class CivicrmBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Class constructor.
   */
  public function __construct(Civicrm $civicrm) {
    $civicrm->initialize();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('civicrm')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $blocks = \CRM_Core_Block::getInfo();
    foreach ($blocks as $block_id => $block) {
      // There's no need to prefix each block label with 'CiviCRM', because we
      // are already our blocks in the Civicrm category.
      $label = str_replace('CiviCRM ', '', $block['info']);

      $this->derivatives[$block_id] = $base_plugin_definition;
      $this->derivatives[$block_id]['admin_label'] = $label;
      // @Todo Ensure blocks aren't cached.
    }
    return $this->derivatives;
  }

}

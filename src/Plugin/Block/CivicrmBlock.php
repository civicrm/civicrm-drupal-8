<?php

namespace Drupal\civicrm\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\civicrm\Civicrm;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the core Civicrm blocks.
 *
 * @Block(
 *   id = "civicrm_block",
 *   admin_label = @Translation("Civicrm block"),
 *   category = @Translation("Civicrm"),
 *   deriver = "Drupal\civicrm\Plugin\Derivative\CivicrmBlock",
 * )
 */
class CivicrmBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Class constructor.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(Civicrm $civicrm, array $configuration, $plugin_id, $plugin_definition) {
    // Mark all CiviCRM blocks as uncachable.
    $configuration['cache']['max_age'] = 0;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $civicrm->initialize();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('civicrm'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_id = $this->getDerivativeId();
    $content = \CRM_Core_Block::getContent($block_id)['content'];

    // Bypass Drupal SafeString escaping by setting output as already escaped.
    if ($content) {
      return [
        '#markup' => Markup::create($content),
      ];
    }
    return [];
  }

}

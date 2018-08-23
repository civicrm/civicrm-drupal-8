<?php

namespace Drupal\civicrm_views\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\civicrm\Civicrm;
use Drupal\core\form\FormStateInterface;

/**
 * Provides a relationship to the CiviCRM location.
 *
 * @ingroup views_relationship_handlers
 * @ViewsRelationship("civicrm_location")
 */
class CivicrmLocation extends RelationshipPluginBase {

  /**
   * An array of CiviCRM location.
   *
   * @var array
   */
  protected $locations = [];

  /**
   * The default CiviCRM location.
   *
   * @var int|null
   */
  protected $defaultLocation = NULL;

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

    $this->defaultLocation = \CRM_Core_BAO_LocationType::getDefault()->id;
    $this->locations = \CRM_Core_BAO_Address::buildOptions('location_type_id');
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

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->definition['extra'] = [];
    if (!empty($this->options['location_type'])) {
      $this->definition['extra'][] = [
        'field' => 'location_type_id',
        'value' => (int) ($this->options['location_type'] == 'default' ? $this->defaultLocation : $this->options['location_type']),
        'numeric' => TRUE,
      ];
    }
    if (!empty($this->options['is_primary'])) {
      $this->definition['extra'][] = [
        'field' => 'is_primary',
        'value' => $this->options['is_primary'],
        'numeric' => TRUE,
      ];
    }
    if (!empty($this->options['is_billing'])) {
      $this->definition['extra'][] = [
        'field' => 'is_billing',
        'value' => $this->options['is_billing'],
        'numeric' => TRUE,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['location_type'] = ['default' => 0];
    $options['is_billing'] = ['default' => FALSE, 'bool' => TRUE];
    $options['is_primary'] = ['default' => FALSE, 'bool' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['is_primary'] = [
      '#type' => 'checkbox',
      '#title' => t('Is primary?'),
      '#default_value' => isset($this->options['is_primary']) ? $this->options['is_primary'] : FALSE,
    ];
    $form['is_billing'] = [
      '#type' => 'checkbox',
      '#title' => t('Is billing?'),
      '#default_value' => isset($this->options['is_billing']) ? $this->options['is_billing'] : FALSE,
    ];
    $form['location_type'] = [
      '#type' => 'radios',
      '#title' => t('Location type'),
      '#options' => [
        0 => t('Any'),
        'default' => t('Default location (!default)', ['!default' => $this->locations[$this->defaultLocation]]),
      ],
      '#default_value' => isset($this->options['location_type']) ? (int) $this->options['location_type'] : 0,
    ];

    foreach ($this->locations as $id => $location) {
      $form['location_type']['#options'][$id] = $location;
    }

    parent::buildOptionsForm($form, $form_state);
  }

}

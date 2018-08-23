<?php

namespace Drupal\civicrm_views\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\civicrm\Civicrm;
use Drupal\core\form\FormStateInterface;

/**
 * Provides a relationship to the CiviCRM domain.
 *
 * @ingroup views_relationship_handlers
 * @ViewsRelationship("civicrm_uf_match")
 */
class CivicrmUFMatch extends RelationshipPluginBase {

  /**
   * An array of available CiviCRM domains.
   *
   * @var array
   */
  protected $civicrmDomains = [];

  /**
   * The current CiviCRM domain.
   *
   * @var int
   */
  protected $civicrmCurrentDomain = 1;

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

    $this->civicrmCurrentDomain = \CRM_Core_Config::domainID();

    $this->civicrmDomains['current'] = t('Current domain');
    $this->civicrmDomains[0] = t('All domains');
    $result = civicrm_api('domain', 'get', ['version' => 3]);
    if (empty($result['is_error'])) {
      foreach ($result['values'] as $value) {
        $this->civicrmDomains[$value['id']] = $value['name'];
      }
    }
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

    if (!empty($this->options['civicrm_domain'])) {
      $this->definition['extra'] = [
        [
          'field' => 'domain_id',
          'value' => $this->options['civicrm_domain'] == 'current' ? $this->civicrmCurrentDomain : $this->options['civicrm_domain'],
          'numeric' => TRUE,
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['civicrm_domain'] = ['default' => 'current'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['civicrm_domain'] = [
      '#type' => 'select',
      '#title' => 'Which domain of Drupal users do you want to join to?',
      '#description' => "CiviCRM can be run across multiple domains. Normally, leave this to 'current domain'.",
      '#options' => $this->civicrmDomains,
      '#default_value' => isset($this->options['civicrm_domain']) ? $this->options['civicrm_domain'] : 'current',
      '#required' => TRUE,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

}

<?php

namespace Drupal\civicrm_group_roles\Batch;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class Sync.
 */
class Sync {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  protected $messenger;

  /**
   * Sync constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   */
  public function __construct(
    TranslationInterface $stringTranslation,
    MessengerInterface $messenger
  ) {
    $this->stringTranslation = $stringTranslation;
    $this->messenger = $messenger;
  }

  /**
   * Get the batch.
   *
   * @return array
   *   A batch API array for syncing user groups and roles.
   */
  public function getBatch() {
    $batch = [
      'title' => $this->t('Updating Users...'),
      'operations' => [],
      'init_message' => $this->t('Starting Update'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('An error occurred during processing'),
      'finished' => [$this, 'finished'],
    ];

    $batch['operations'][] = [[$this, 'process'], []];

    return $batch;
  }

  /**
   * Batch API process callback.
   *
   * @param mixed $context
   *   Batch API context data.
   */
  public function process(&$context) {
    $civicrmGroupRoles = $this->getcivicrmGroupRoles();

    if (!isset($context['sandbox']['cids'])) {
      $context['sandbox']['cids'] = $civicrmGroupRoles->getSyncContactIds();
      $context['sandbox']['max'] = count($context['sandbox']['cids']);
      $context['results']['processed'] = 0;
    }

    $cid = array_shift($context['sandbox']['cids']);
    if ($account = $civicrmGroupRoles->getContactAccount($cid)) {
      $civicrmGroupRoles->syncContact($cid, $account);
    }
    $context['results']['processed']++;

    if (count($context['sandbox']['cids']) > 0) {
      $context['finished'] = 1 - (count($context['sandbox']['cids']) / $context['sandbox']['max']);
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Batch API success indicator.
   * @param array $results
   *   Batch API results array.
   */
  public function finished($success, array $results) {
    if ($success) {
      $message = $this->stringTranslation->formatPlural($results['processed'], 'One user processed.', '@count users processed.');
      $this->messenger->addMessage($message);
    }
    else {
      $message = $this->t('Encountered errors while performing sync.');
      $this->messenger->addMessage($message, 'error');
    }

  }

  /**
   * Get CiviCRM group roles service.
   *
   * This is called directly from the Drupal object to avoid dealing with
   * serialization.
   *
   * @return \Drupal\civicrm_group_roles\civicrmGroupRoles
   *   The CiviCRM group roles service.
   */
  protected function getcivicrmGroupRoles() {
    return \Drupal::service('civicrm_group_roles');
  }

  /**
   * Get the database connection.
   *
   * This is called directly from the Drupal object to avoid dealing with
   * serialization.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  protected function getDatabase() {
    return \Drupal::database();
  }

}

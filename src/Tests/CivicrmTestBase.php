<?php

namespace Drupal\civicrm\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Database\Database;

/**
 * Class CivicrmTestBase.
 */
abstract class CivicrmTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['civicrm'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // If the civicrm_test database already exists, first drop it.
    try {
      $conn = Database::getConnection('default', 'civicrm_test');
      $database = $conn->getConnectionOptions()['database'];
      // Todo: get this working when db name passed in as an argument.
      $conn->query("DROP DATABASE $database");
      $conn->destroy();
    }
    catch (\Exception $e) {
      // Pass.
    }

    // Now attempt to create the database.
    // This method is taken from
    // \Drupal\Core\Database\Driver\mysql\Install\Tasks.
    // Remove the database string from connection info.
    $connection_info = Database::getConnectionInfo('civicrm_test');
    $database = $connection_info['default']['database'];
    unset($connection_info['default']['database']);

    // In order to change the Database::$databaseInfo array, need to remove
    // the active connection, then re-add it with the new info.
    Database::removeConnection('civicrm_test');
    Database::addConnectionInfo('civicrm_test', 'default', $connection_info['default']);

    // Now, attempt the connection again; if it's successful, attempt to
    // create the database.
    Database::getConnection('civicrm_test')->createDatabase($database);
    if (!$connection_info) {
      throw new \RuntimeException("No default connection info!");
    }

    // Now add the civicrm_test connection info back *with* the database key
    // present.
    $connection_info['default']['database'] = $database;
    Database::removeConnection('civicrm_test');
    Database::addconnectionInfo('civicrm_test', 'default', $connection_info['default']);

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $conn = Database::getConnection('default', 'civicrm_test');
    $database = $conn->getConnectionOptions()['database'];
    // Todo: get this working when db name passed in as an argument.
    $conn->query("DROP DATABASE $database");
    $conn->destroy();
  }

}

<?php

namespace Drupal\civicrm\Commands;

use Drush\Commands\sql\SqlCommands;
use Symfony\Component\Console\Input\InputInterface;

/**
 * A Drush command file.
 */
class CivicrmSqlCommands extends SqlCommands {

  /**
   * An array of options that can be passed to SqlBase::create to reference the CiviCRM database.
   *
   * @var array
   */
  protected $civiDbOptions;

  /**
   * Print CiviCRM database connection details.
   *
   * @command civicrm:sql-conf
   *
   */
  public function drush_civicrm_sqlconf() {
    $this->civicrm_dsn_init();
    $options = array_merge([
      'format' => 'yaml',
      'all' => false,
      'show-passwords' => false
    ], $this->civiDbOptions);

    return print_r($this->conf($options));
  }

  /**
   * A string for connecting to the CiviCRM DB.
   *
   * @command civicrm:sql-connect
   */
  public function drush_civicrm_sqlconnect() {
    $this->civicrm_dsn_init();
    return $this->connect($this->civiDbOptions);
  }

  /**
   * Exports the CiviCRM DB as SQL using mysqldump.
   *
   * @command civicrm:sql-dump
   * @optionset_sql
   * @optionset_table_selection
   * @option result-file Save to a file. The file should be relative to Drupal root. If --result-file is provided with the value 'auto', a date-based filename will be created under ~/drush-backups directory.
   * @option create-db Omit DROP TABLE statements. Used by Postgres and Oracle only.
   * @option data-only Dump data without statements to create any of the schema.
   * @option ordered-dump Order by primary key and add line breaks for efficient diffs. Slows down the dump. Mysql only.
   * @option gzip Compress the dump using the gzip program which must be in your $PATH.
   * @option extra Add custom arguments/options when connecting to database (used internally to list tables).
   * @option extra-dump Add custom arguments/options to the dumping of the database (e.g. mysqldump command).
   * @usage drush civicrm:sql-dump --result-file=../CiviCRM.sql
   *   Save SQL dump to the directory above Drupal root.
   * @hidden-options create-db
   */
  public function drush_civicrm_sqldump(array $options = ['result-file' => self::REQ, 'create-db' => false, 'data-only' => false, 'ordered-dump' => false, 'gzip' => false, 'extra' => self::REQ, 'extra-dump' => self::REQ, 'format' => 'null']) {
    $this->civicrm_dsn_init();
    $this->dump(array_merge($options, $this->civiDbOptions));
  }

  /**
   * Execute a query against the CiviCRM database.
   *
   * @command civicrm:sql-query
   * @usage drush civicrm:sql-query "SELECT * FROM civicrm_contact WHERE id=1"
   *   Browse user record.
   */
  public function drush_civicrm_sqlquery(string $query) {
    $this->civicrm_dsn_init();
    $this->query($query, $this->civiDbOptions);
  }

  /**
   * Open a SQL command-line interface using CiviCRM's credentials.
   *
   * @command civicrm:sql-cli
   * @aliases cvsqlc
   */
  public function drush_civicrm_sqlcli(InputInterface $input) {
    $this->civicrm_dsn_init();
    $this->cli($input, $this->civiDbOptions);
  }

  /**
   * Initialise CiviCRM.
   */
  private function civicrm_init() {
    \Drupal::service('civicrm')->initialize();
  }

  /**
   * Initialise CiviCRM.
   */
  private function civicrm_dsn_init() {
    $this->civicrm_init();
    $this->civiDbOptions = [
      'db-url' => CIVICRM_DSN,
    ];
  }
}

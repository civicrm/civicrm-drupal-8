<?php

namespace Drupal\civicrm\Commands;

use CiviCRM_API3_Exception;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use CRM_Core_BAO_ConfigSetting;
use CRM_Core_JobManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;
use Drush\Sql\SqlBase;

/**
 * A Drush command file.
 */
class CivicrmCommands extends DrushCommands {

  /**
   * An array of options that can be passed to SqlBase::create to reference the CiviCRM database.
   *
   * @var array
   */
  protected $civiDbOptions;

  /**
   * A SqlBase object pointing to the CiviCRM database.
   *
   * @var |Drush\Sql\SqlBase
   */
  private $dbObject;

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * CivicrmCommands constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module_handler service.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    parent::__construct();
    $this->moduleHandler = $moduleHandler;
    $this->root = dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))));
  }

  /**
   * Adds a cache clear option for CiviCRM.
   *
   * Warning: do not name drush_civicrm_cache_clear() otherwise it will
   * conflict with hook_drush_cache_clear() and be called systematically
   * when "drush cc" is called.
   *
   * @param array $types
   *   The Drush clear types to make available.
   * @param bool $includeBootstrappedTypes
   *   Whether to include types only available in a bootstrapped Drupal or not.
   *
   * @hook on-event cache-clear
   */
  public function drush_civicrm_cacheclear(array &$types, $includeBootstrappedTypes) {
    if ($includeBootstrappedTypes && $this->moduleHandler->moduleExists('civicrm')) {
      $types['civicrm'] = 'civicrm_clear_cache';
    }
  }

  /**
   * Adds a route rebuild option for CiviCRM.
   *
   * @command civicrm:route-rebuild
   */
  public function drush_civicrm_route_rebuild() {
    \Drupal::service("router.builder")->rebuild();

    drush_log(dt('Route rebuild complete.'), 'ok');
  }

  /**
   * Enable CiviCRM Debugging.
   *
   * @todo Do we need to validate?
   *
   * @command civicrm:enable-debug
   */
  public function drush_civicrm_enable_debug() {
    $settings = [
      'debug_enabled' => 1,
      'backtrace' => 1,
    ];

    $this->civicrm_enable_settings($settings);
    drush_log(dt('CiviCRM debug setting enabled.'), 'success');
  }

  /**
   * Disable CiviCRM Debugging.
   *
   * @todo Do we need to validate?
   *
   * @command civicrm:disable-debug
   */
  public function drush_civicrm_disable_debug() {
    $settings = [
      'debug_enabled' => 0,
      'backtrace' => 0,
    ];

    $this->civicrm_enable_settings($settings);
    drush_log(dt('CiviCRM debug setting disabled.'), 'success');
  }

  /**
   * Process pending CiviMail mailing jobs.
   *
   * @command civicrm:process-mail-queue
   * @usage civicrm:process-mail-queue -u admin
   */
  public function drush_civicrm_process_mail_queue() {
    $this->civicrm_init();
    $facility = new CRM_Core_JobManager();
    $facility->setSingleRunParams('Job', 'process_mailing', [], 'Started by drush');
    $facility->executeJobByAction('Job', 'process_mailing');
  }

  /**
   * Run the CiviMember UpdateMembershipRecord cron (civicrm-member-records).
   *
   * @command civicrm:member-records
   */
  public function drush_civicrm_member_records() {
    // @todo Write functionality.
    drush_log(dt('Not implemented yet.'), 'error');
  }

  /**
   * CLI access to CiviCRM APIs. It can return pretty-printor json formatted data.
   *
   * @command civicrm:api
   * @option in Input type: "args" (command-line), "json" (STDIN).
   * @option out Output type: "pretty" (STDOUT), "json" (STDOUT).
   * @usage drush civicrm:api contact.create first_name=John last_name=Doe contact_type=Individual
   *   Create a new contact named John Doe.
   * @usage drush civicrm:api contact.create id=1 --out=json
   *   Find/display a contact in JSON format.
   * @aliases cvapi
   */
  public function drush_civicrm_api(array $options = ['in' => 'args', 'out' => 'pretty']) {
    // @todo Write functionality.
    drush_log(dt('Not implemented yet.'), 'error');
  }

  /**
   * List of CiviCRM extensions enabled.
   *
   * @command civicrm:ext-list
   * @aliases cel
   * @usage drush civicrm:ext-list
   *   List of CiviCRM extensions in table format.
   * @field-labels
   *   key: App name
   *   status: Status
   * @default-fields key,status
   */
  public function drush_civicrm_ext_list($options = ['format' => 'table']) {
    $this->civicrm_init();
    try {
      $result = civicrm_api3('extension', 'get', [
        'options' => [
          'limit' => 0,
        ],
      ]);
      foreach ($result['values'] as $k => $extension_data) {
        $rows[] = [
          'key' => $extension_data['key'],
          'status' => $extension_data['status'],
        ];
      }
      return new RowsOfFields($rows);
    }
    catch (CiviCRM_API3_Exception $e) {
      // Handle error here.
      $errorMessage = $e->getMessage();
      $errorCode = $e->getErrorCode();
      $errorData = $e->getExtraParams();
      drush_set_error(dt("!error", ['!error' => $errorMessage]));
    }
  }

  /**
   * Install a CiviCRM extension.
   *
   * @param string $name
   *   Argument provided to the drush command.
   *
   * @command civicrm:ext-install
   * @usage drush civicrm:ext-install civimobile
   *   Install the civimobile extension.
   * @aliases cei
   */
  public function drush_civicrm_ext_install($name) {
    $this->civicrm_extension_action($name, 'install', dt('installed'));
  }

  /**
   * Disable a CiviCRM extension.
   *
   * @param string $name
   *   Argument provided to the drush command.
   *
   * @command civicrm:ext-disable
   * @usage drush civicrm:ext-disable civimobile
   *   Disable the civimobile extension.
   * @aliases ced
   */
  public function drush_civicrm_ext_disable($name) {
    $this->civicrm_extension_action($name, 'disable', dt('disabled'));
  }

  /**
   * Uninstall a CiviCRM extension.
   *
   * @param string $name
   *   Argument provided to the drush command.
   *
   * @command civicrm:ext-uninstall
   * @usage drush civicrm:ext-uninstall civimobile
   *   Uninstall the civimobile extension.
   * @aliases ceui
   */
  public function drush_civicrm_ext_uninstall($name) {
    $this->civicrm_extension_action($name, 'uninstall', dt('uninstalled'));
  }

  /**
   * Update config_backend to correct config settings, especially when the CiviCRM site has been cloned / migrated.
   *
   * @todo Do we need to validate?
   *
   * @param string $url
   *   The site url.
   *
   * @command civicrm:update-cfg
   * @usage drush civicrm:update-cfg http://example.com/civicrm
   *   Update config_backend to correct config settings for civicrm installation on example.com site.
   * @aliases cvupcfg
   */
  public function drush_civicrm_update_cfg(string $url) {
    $this->civicrm_init();
    $defaultValues = [];
    $states = ['old', 'new'];
    for ($i = 1; $i <= 3; $i++) {
      foreach ($states as $state) {
        $name = "{$state}Val_{$i}";
        $value = $url;
        if ($value) {
          $defaultValues[$name] = $value;
        }
      }
    }

    // @todo: Refactor to not use BAO?
    $result = CRM_Core_BAO_ConfigSetting::doSiteMove($defaultValues);

    if ($result) {
      drush_log(dt('Config successfully updated.'), 'completed');
    }
    else {
      drush_log(dt('Config update failed.'), 'failed');
    }

  }

  /**
   * Execute the civicrm/upgrade?reset=1 process from the command line.
   *
   * @todo Do we need to validate?
   *
   * @command civicrm:upgrade-db
   * @aliases cvupdb
   */
  public function drush_civicrm_upgrade_db() {
    // @todo Write functionality.
    drush_log(dt('Not implemented yet.'), 'error');
  }

  /**
   * Replace CiviCRM codebase with new specified tarfile and upgrade database by executing the CiviCRM upgrade process - civicrm/upgrade?reset=1.
   *
   * @todo Do we need to validate?
   *
   * @command civicrm:upgrade
   * @option tarfile Path of new CiviCRM tarfile, with which current CiviCRM codebase is to be replaced.
   * @option backup-dir Specify a directory to backup current CiviCRM codebase and database into, defaults to a backup directory above your Drupal root.
   * @usage drush civicrm:upgrade --tarfile=~/tarballs/civicrm-4.1.2-drupal.tar.gz
   *   Replace old CiviCRM codebase with new v4.1.2 and run upgrade process.
   * @aliases cvup
   */
  public function drush_civicrm_upgrade(array $options = ['tarfile' => NULL, 'backup-dir' => NULL]) {
    // @todo Write functionality.
    drush_log(dt('Not implemented yet.'), 'error');
  }

  /**
   * Restore CiviCRM codebase and database back from the specified backup directory.
   *
   * @todo Do we need to validate?
   *
   * @command civicrm:restore
   * @option restore-dir Path of directory having backed up CiviCRM codebase and database.
   * @option backup-dir Specify a directory to backup current CiviCRM codebase and database into, defaults to a backup directory above your Drupal root.
   * @usage drush civicrm:restore --restore-dir=../backup/modules/20100309200249
   *   Replace current civicrm codebase with the $restore-dir/civicrm codebase, and reload the database with $restore-dir/civicrm.sql file.
   */
  public function drush_civicrm_restore(array $options = ['restore-dir' => NULL, 'backup-dir' => NULL]) {

    // @todo: Can we place the validation in a drush_hook_COMMAND_validate?
    $restore_dir = $options['restore-dir'];
    $restore_dir = rtrim($restore_dir, '/');
//    if (!$restore_dir) {
//      return drush_set_error('CIVICRM_RESTORE_NOT_SPECIFIED', dt('Restore-dir not specified.'));
//    }
//    $sql_file = $restore_dir . '/civicrm.sql';
//    if (!file_exists($sql_file)) {
//      return drush_set_error('CIVICRM_RESTORE_CIVICRM_SQL_NOT_FOUND', dt('Could not locate civicrm.sql file in the restore directory.'));
//    }
//    $code_dir = $restore_dir . '/civicrm';
//    if (!is_dir($code_dir)) {
//      return drush_set_error('CIVICRM_RESTORE_DIR_NOT_FOUND', dt('Could not locate civicrm directory inside restore-dir.'));
//    }
//    elseif (!file_exists("$code_dir/civicrm-version.php")) {
//      return drush_set_error('CIVICRM_RESTORE_DIR_NOT_VALID', dt('civicrm directory inside restore-dir, doesn\'t look to be a valid civicrm codebase.'));
//    }

    // @todo: WIP
    $date = date('YmdHis');
    $drupal_root = $this->root;
    $civicrm_root_base = '';
    $this->civicrm_dsn_init();
    $dbSpec = $this->dbObject->getDbSpec();
    $restore_backup_dir = isset($options['backup-dir']) ? $options['backup-dir'] : $drupal_root . '/backup';
    $restore_backup_dir = rtrim($restore_backup_dir, '/');

    $this->output->write([
      '',
      dt("Process involves:"),
      dt("1. Restoring '!restoreDir/civicrm' directory to '!toDir'.",
        ['!restoreDir' => $restore_dir, '!toDir' => $civicrm_root_base]
      ),
      dt("2. Dropping and creating '!db' database.",
        ['!db' => $dbSpec['database']]
      ),
      dt("3. Loading '!restoreDir/civicrm.sql' file into the database.",
        ['!restoreDir' => $restore_dir]
      ),
      '',
      dt("Note: Before restoring a backup will be taken in '!path' directory.",
        ['!path' => "$restore_backup_dir/modules/restore"]
      ),
      ''
    ], TRUE);

//    if (!drush_confirm(dt('Do you really want to continue?'))) {
//      return drush_user_abort();
//    }

    drush_log(dt('Not implemented yet.'), 'error');

  }

  /**
   * Install a new instance of CiviCRM.
   *
   * @todo Do we need to validate?
   *
   * @command civicrm:install
   * @option dbuser MySQL username for your Drupal/CiviCRM database.
   * @option dbpass MySQL password for your Drupal/CiviCRM database.
   * @option dbhost MySQL host for your Drupal/CiviCRM database. Defaults to localhost.
   * @option dbname MySQL database name of your Drupal/CiviCRM database.
   * @option tarfile Path to your CiviCRM tar.gz file.
   * @option destination Destination modules path to extract CiviCRM (eg : sites/all/modules ).
   * @option lang Default language to use for installation.
   * @option langtarfile Path to your l10n tar.gz file.
   * @option site_url Base Url for your drupal/CiviCRM website without http (e.g. mysite.com).
   * @option ssl Using ssl for your drupal/CiviCRM website if set to on (e.g. --ssl=on).
   * @aliases cvi
   */
  public function drush_civicrm_install(array $options = ['dbuser' => FALSE, 'dbpass' => FALSE, 'dbhost' => FALSE, 'dbname' => FALSE, 'tarfile' => FALSE, 'destination' => FALSE, 'lang' => FALSE, 'langtarfile' => FALSE, 'site_url' => FALSE, 'ssl' => FALSE]) {
    // @todo Write functionality.
    drush_log(dt('Not implemented yet.'), 'error');
  }

  /**
   * Rest interface for accessing CiviCRM APIs. It can return xml or json formatted data.
   *
   * @todo Do we need to validate?
   *
   * @command civicrm:rest
   * @option query Query part of url. Refer CiviCRM wiki doc for more details.
   * @usage drush civicrm:rest --query='civicrm/contact/search&json=1&key=7decb879f28ac4a0c6a92f0f7889a0c9&api_key=7decb879f28ac4a0c6a92f0f7889a0c9'
   *   Use contact search api to return data in json format.
   * @aliases cvr
   */
  public function drush_civicrm_rest(array $options = ['query' => NULL]) {
    // @todo Write functionality.
    drush_log(dt('Not implemented yet.'), 'error');
  }

  /**
   * Initialise CiviCRM.
   */
  private function civicrm_init() {
    \Drupal::service('civicrm')->initialize();
  }

  /**
   * Clear civicrm caches using the API.
   */
  private function civicrm_clear_cache() {
    $this->civicrm_init();

    if (drush_get_option('triggers', FALSE)) {
      $params['triggers'] = 1;
    }

    if (drush_get_option('sessions', FALSE)) {
      $params['session'] = 1;
    }

    // Need to set API version or drush cc civicrm fails.
    $params['version'] = 3;
    $result = civicrm_api('System', 'flush', $params);

    if ($result['is_error']) {
      drush_set_error(dt('An error occurred: !message', ['!message' => $result['error_message']]));
    }

    drush_log(dt('The CiviCRM cache has been cleared.'), 'ok');
  }

  /**
   * Enable settings for CiviCRM.
   *
   * @param array $settings
   *   An array containing the keys and values.
   */
  private function civicrm_enable_settings(array $settings) {
    $this->civicrm_init();
    foreach ($settings as $key => $val) {
      $result = civicrm_api('Setting', 'create', ['version' => 3, $key => $val]);

      if ($result['is_error']) {
        drush_set_error(dt('An error occurred: !message', ['!message' => $result['error_message']]));
      }
    }
  }

  /**
   * Execute an action on an extension.
   *
   * @param string $name
   *   The name of the extension.
   * @param string $action
   *   The action.
   * @param string $message
   *   The message for the action.
   *
   * @throws \Exception
   */
  private function civicrm_extension_action($name, $action, $message) {
    $this->civicrm_init();

    try {
      $result = civicrm_api('extension', $action, ['key' => $name, 'version' => 3]);
      if ($result['values'] && $result['values'] == 1) {
        $this->output->writeln(dt("Extension !ename !message.", ['!ename' => $name, '!message' => $message]));
      }
      else {
        drush_set_error(t('Extension !ename could not be !message.', ['!ename' => $name, '!message' => $message]));
      }
    }
    catch (CiviCRM_API3_Exception $e) {
      $errorMessage = $e->getMessage();
      $errorCode = $e->getErrorCode();
      $errorData = $e->getExtraParams();
      drush_set_error(dt("!error", ['!error' => $errorMessage]));
    }
  }

  /**
   * Initialise CiviCRM.
   */
  private function civicrm_dsn_init() {
    $this->civicrm_init();
    $this->civiDbOptions = [
      'db-url' => CIVICRM_DSN,
    ];
    $this->dbObject = SqlBase::create($this->civiDbOptions);
  }
}

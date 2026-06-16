<?php

namespace Drupal\civicrm\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands as BaseDrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Drush 12+ commands for CiviCRM.
 *
 * Each command inlines its own logic using Drush 12 APIs. The legacy
 * procedural file drush/civicrm.drush.inc is intentionally left intact for
 * older Drush installations.
 */
class DrushCommands extends BaseDrushCommands {

  // ---------------------------------------------------------------------------
  // API
  // ---------------------------------------------------------------------------

  /**
   * Call a CiviCRM APIv3 action.
   *
   * @param string $entityAction
   *   API target in Entity.Action format (example: System.flush).
   * @param string[] $pairs
   *   Any key=value parameters, e.g. status=installed options[limit]=0.
   *
   * @option in
   *   Input type: "args" (command-line), "json" (STDIN). Default: args.
   * @option out
   *   Output type: "pretty" (STDOUT), "json" (STDOUT). Default: pretty.
   *
   * @command civicrm:api
   * @aliases cvapi,civicrm-api
   *
   * @usage drush cvapi System.flush
   *   Flush CiviCRM caches.
   * @usage drush cvapi extension.get status=installed options[limit]=0
   *   Query installed extensions.
   */
  public function api(string $entityAction, array $pairs = []): void {
    $this->initializeCivi();

    if (strpos($entityAction, '.') === FALSE) {
      throw new \InvalidArgumentException('Expected Entity.Action format, e.g. System.flush');
    }

    [$entity, $action] = explode('.', $entityAction, 2);

    $inFormat = $this->input()->getOption('in') ?: 'args';
    $outFormat = $this->input()->getOption('out') ?: 'pretty';

    $params = ['version' => 3];

    switch ($inFormat) {
      case 'args':
        foreach ($pairs as $pair) {
          if (!str_contains((string) $pair, '=')) {
            throw new \InvalidArgumentException(sprintf('Invalid parameter "%s". Expected key=value.', $pair));
          }
          [$key, $value] = explode('=', (string) $pair, 2);
          $params[$key] = $value;
        }
        break;

      case 'json':
        $json = stream_get_contents(STDIN);
        if (!empty($json)) {
          $decoded = json_decode($json, TRUE);
          if (!is_array($decoded)) {
            throw new \InvalidArgumentException('Could not decode JSON from STDIN.');
          }
          $params = array_merge($params, $decoded);
        }
        break;

      default:
        throw new \InvalidArgumentException(sprintf('Unknown --in format: %s', $inFormat));
    }

    $result = civicrm_api3($entity, $action, $params);

    switch ($outFormat) {
      case 'pretty':
        $this->output()->writeln(print_r($result, TRUE));
        break;

      case 'json':
        $this->output()->writeln(json_encode($result, JSON_PRETTY_PRINT));
        break;

      default:
        throw new \InvalidArgumentException(sprintf('Unknown --out format: %s', $outFormat));
    }
  }


  // ---------------------------------------------------------------------------
  // Cache / flush
  // ---------------------------------------------------------------------------

  /**
   * Flush CiviCRM caches.
   *
   * @option triggers
   *   Rebuild triggers (pass 1 to enable).
   * @option sessions
   *   Reset sessions (pass 1 to enable).
   *
   * @command civicrm:flush
   * @aliases cvflush,civicrm-flush
   */
  public function flush(): void {
    $this->initializeCivi();

    $params = ['version' => 3];
    if ($this->input()->getOption('triggers')) {
      $params['triggers'] = 1;
    }
    if ($this->input()->getOption('sessions')) {
      $params['session'] = 1;
    }

    $result = civicrm_api3('System', 'flush', $params);
    if (!empty($result['is_error'])) {
      throw new \RuntimeException('CiviCRM cache flush failed: ' . $result['error_message']);
    }
    $this->logger()->success('CiviCRM cache flush complete.');
  }

  // ---------------------------------------------------------------------------
  // Extensions
  // ---------------------------------------------------------------------------

  /**
   * List CiviCRM extensions.
   *
   * @option status
   *   Filter by status: installed, uninstalled, disabled.
   * @option format
   *   Output format: table (default), json.
   *
   * @command civicrm:ext-list
   * @aliases cel,civicrm-ext-list
   *
   * @usage drush cel
   *   List all extensions.
   * @usage drush cel --status=installed
   *   List installed extensions.
   */
  public function extList(): void {
    $this->initializeCivi();

    $status = $this->input()->getOption('status');
    $params = ['options' => ['limit' => 0], 'version' => 3];

    if ($status) {
      if (!in_array($status, ['installed', 'uninstalled', 'disabled'], TRUE)) {
        throw new \InvalidArgumentException(sprintf('Invalid status "%s". Use: installed, uninstalled, disabled.', $status));
      }
      $params['status'] = $status;
    }

    $result = civicrm_api3('Extension', 'get', $params);

    $format = $this->input()->getOption('format') ?: 'table';
    if ($format === 'json') {
      $this->output()->writeln(json_encode(array_values($result['values']), JSON_PRETTY_PRINT));
      return;
    }

    $rows = [];
    foreach ($result['values'] as $ext) {
      $rows[] = [
        'key'     => $ext['key'],
        'status'  => $ext['status'],
        'version' => $ext['version'] ?? '',
      ];
    }
    $this->io()->table(['App name', 'Status', 'Version'], $rows);
  }

  /**
   * Install a CiviCRM extension.
   *
   * @param string $ename
   *   Extension key.
   *
   * @command civicrm:ext-install
   * @aliases cei,civicrm-ext-install
   */
  public function extInstall(string $ename): void {
    $this->initializeCivi();

    $result = civicrm_api3('Extension', 'install', ['key' => $ename, 'version' => 3]);
    if (!empty($result['is_error'])) {
      throw new \RuntimeException(sprintf('Could not install extension "%s": %s', $ename, $result['error_message']));
    }
    $this->logger()->success(sprintf('Extension "%s" installed.', $ename));
  }

  /**
   * Disable a CiviCRM extension.
   *
   * @param string $ename
   *   Extension key.
   *
   * @command civicrm:ext-disable
   * @aliases ced,civicrm-ext-disable
   */
  public function extDisable(string $ename): void {
    $this->initializeCivi();

    $result = civicrm_api3('Extension', 'disable', ['key' => $ename, 'version' => 3]);
    if (!empty($result['is_error'])) {
      throw new \RuntimeException(sprintf('Could not disable extension "%s": %s', $ename, $result['error_message']));
    }
    $this->logger()->success(sprintf('Extension "%s" disabled.', $ename));
  }

  /**
   * Uninstall a CiviCRM extension.
   *
   * @param string $ename
   *   Extension key.
   *
   * @command civicrm:ext-uninstall
   * @aliases ceui,civicrm-ext-uninstall
   */
  public function extUninstall(string $ename): void {
    $this->initializeCivi();

    $result = civicrm_api3('Extension', 'uninstall', ['key' => $ename, 'version' => 3]);
    if (!empty($result['is_error'])) {
      throw new \RuntimeException(sprintf('Could not uninstall extension "%s": %s', $ename, $result['error_message']));
    }
    $this->logger()->success(sprintf('Extension "%s" uninstalled.', $ename));
  }

  // ---------------------------------------------------------------------------
  // Database upgrade / config
  // ---------------------------------------------------------------------------

  /**
   * Run the CiviCRM database upgrade.
   *
   * @command civicrm:upgrade-db
   * @aliases cvupdb,civicrm-upgrade-db
   */
  public function upgradeDb(): void {
    $this->initializeCivi();

    if (!defined('CIVICRM_UPGRADE_ACTIVE')) {
      define('CIVICRM_UPGRADE_ACTIVE', 1);
    }
    $_GET['q'] = 'civicrm/upgrade';

    $codeVer = \CRM_Utils_System::version();
    $dbVer   = \CRM_Core_BAO_Domain::version();

    if (!$dbVer) {
      throw new \RuntimeException('Version information missing in CiviCRM database.');
    }
    if (stripos($dbVer, 'upgrade') !== FALSE) {
      throw new \RuntimeException('Database looks partially upgraded. Reload from backup and retry.');
    }
    if (!$codeVer) {
      throw new \RuntimeException('Version information missing in CiviCRM codebase.');
    }
    if (version_compare($codeVer, $dbVer) < 0) {
      throw new \RuntimeException(sprintf("DB version '%s' is higher than codebase version '%s'.", $dbVer, $codeVer));
    }
    if (version_compare($codeVer, $dbVer) === 0) {
      $this->logger()->notice(sprintf('Database already at v%s, nothing to upgrade.', $dbVer));
      return;
    }

    $this->logger()->notice(sprintf('Upgrading v%s → v%s …', $dbVer, $codeVer));

    $upgradeHeadless = new \CRM_Upgrade_Headless();
    $result = $upgradeHeadless->run();
    $this->logger()->success('Upgrade complete: ' . $result['message']);
  }

  /**
   * Update CiviCRM config_backend (useful after site clone / migration).
   *
   * @option oldVal_1
   * @option newVal_1
   * @option oldVal_2
   * @option newVal_2
   * @option oldVal_3
   * @option newVal_3
   *
   * @command civicrm:update-cfg
   * @aliases cvupcfg,civicrm-update-cfg
   */
  public function updateCfg(): void {
    $this->initializeCivi();

    $defaultValues = [];
    foreach (['old', 'new'] as $state) {
      for ($i = 1; $i <= 3; $i++) {
        $name  = "{$state}Val_{$i}";
        $value = $this->input()->getOption($name);
        if ($value !== NULL) {
          $defaultValues[$name] = $value;
        }
      }
    }

    $result = \CRM_Core_BAO_ConfigSetting::doSiteMove($defaultValues);
    if ($result) {
      $this->logger()->success('CiviCRM config updated successfully.');
    }
    else {
      throw new \RuntimeException('CiviCRM config update failed.');
    }
  }

  // ---------------------------------------------------------------------------
  // Debug settings
  // ---------------------------------------------------------------------------

  /**
   * Enable CiviCRM debugging.
   *
   * @command civicrm:enable-debug
   * @aliases civicrm-enable-debug
   */
  public function enableDebug(): void {
    $this->setDebug(TRUE);
  }

  /**
   * Disable CiviCRM debugging.
   *
   * @command civicrm:disable-debug
   * @aliases civicrm-disable-debug
   */
  public function disableDebug(): void {
    $this->setDebug(FALSE);
  }

  // ---------------------------------------------------------------------------
  // Pipe
  // ---------------------------------------------------------------------------

  /**
   * Start a Civi::pipe session (JSON-RPC 2.0).
   *
   * @param string|null $connectionFlags
   *   Optional connection flags (see https://docs.civicrm.org/dev/en/latest/framework/pipe#flags).
   *
   * @command civicrm:pipe
   * @aliases cvpipe,civicrm-pipe
   *
   * @usage drush civicrm:pipe vt
   *   Start a trusted session with version flag.
   */
  public function pipe(?string $connectionFlags = NULL): void {
    $this->initializeCivi();

    if (!is_callable(['Civi', 'pipe'])) {
      throw new \RuntimeException('This version of CiviCRM does not include Civi::pipe() support.');
    }

    if (!empty($connectionFlags)) {
      \Civi::pipe($connectionFlags);
    }
    else {
      \Civi::pipe();
    }
  }

  // ---------------------------------------------------------------------------
  // Mail queue / member records
  // ---------------------------------------------------------------------------

  /**
   * Process pending CiviMail mailing jobs.
   *
   * @command civicrm:process-mail-queue
   * @aliases civicrm-process-mail-queue
   */
  public function processMailQueue(): void {
    $this->initializeCivi();

    $facility = new \CRM_Core_JobManager();
    $facility->setSingleRunParams('Job', 'process_mailing', [], 'Started by drush');
    $facility->executeJobByAction('Job', 'process_mailing');
    $this->logger()->success('CiviMail queue processed.');
  }

  /**
   * Run the CiviMember UpdateMembershipRecord cron.
   *
   * @command civicrm:member-records
   * @aliases civicrm-member-records
   */
  public function memberRecords(): void {
    $this->initializeCivi();

    $facility = new \CRM_Core_JobManager();
    $facility->setSingleRunParams('Job', 'process_membership', [], 'Started by drush');
    $facility->executeJobByAction('Job', 'process_membership');
    $this->logger()->success('CiviMember records updated.');
  }

  // ---------------------------------------------------------------------------
  // REST
  // ---------------------------------------------------------------------------

  /**
   * REST interface for accessing CiviCRM APIs (returns XML or JSON).
   *
   * @option query
   *   Query part of the URL (e.g. "civicrm/contact/search&json=1&key=…").
   *
   * @command civicrm:rest
   * @aliases cvr,civicrm-rest
   */
  public function rest(): void {
    $this->initializeCivi();

    $query = $this->input()->getOption('query');
    if (empty($query)) {
      throw new \InvalidArgumentException('--query is required. Example: civicrm/contact/search&json=1&key=…');
    }

    $parts      = explode('&', $query);
    $_GET['q']  = array_shift($parts);

    foreach ($parts as $keyVal) {
      [$key, $val]     = explode('=', $keyVal, 2);
      $_REQUEST[$key]  = $val;
      $_GET[$key]      = $val;
    }

    $config = \CRM_Core_Config::singleton();
    $rest   = new \CRM_Utils_REST();

    global $civicrm_root;
    $_SERVER['SCRIPT_FILENAME'] = $civicrm_root . '/extern/rest.php';

    if (!empty($_GET['json'])) {
      header('Content-Type: text/javascript');
    }
    else {
      header('Content-Type: text/xml');
    }
    echo $rest->run($config);
  }

  // ---------------------------------------------------------------------------
  // SQL helpers
  // ---------------------------------------------------------------------------

  /**
   * Print CiviCRM database connection details.
   *
   * @command civicrm:sql-conf
   * @aliases civicrm-sql-conf
   */
  public function sqlConf(): void {
    $dsn = $this->getCivicrmDsn();
    $parsed = $this->parseDsn($dsn);
    $this->io()->table(array_keys($parsed), [array_values($parsed)]);
  }

  /**
   * Print a MySQL connection command string for the CiviCRM DB.
   *
   * @command civicrm:sql-connect
   * @aliases civicrm-sql-connect
   */
  public function sqlConnect(): void {
    $this->output()->writeln($this->buildMysqlCmd($this->parseDsn($this->getCivicrmDsn())));
  }

  /**
   * Export the CiviCRM DB as SQL using mysqldump.
   *
   * @option data-only
   *   Dump data without schema.
   * @option gzip
   *   Compress with gzip.
   * @option result-file
   *   Save to a file path.
   * @option tables-list
   *   Comma-separated list of tables to include.
   *
   * @command civicrm:sql-dump
   * @aliases civicrm-sql-dump
   */
  public function sqlDump(): void {
    $parsed  = $this->parseDsn($this->getCivicrmDsn());
    $cmd     = $this->buildMysqldumpCmd($parsed);
    $outFile = $this->input()->getOption('result-file');

    if ($outFile) {
      $cmd .= ' > ' . escapeshellarg($outFile);
      if ($this->input()->getOption('gzip')) {
        $cmd .= ' && gzip -f ' . escapeshellarg($outFile);
      }
    }
    elseif ($this->input()->getOption('gzip')) {
      $cmd .= ' | gzip';
    }

    $this->runShell($cmd);
  }

  /**
   * Execute a query against the CiviCRM database.
   *
   * @param string $query
   *   SQL query string.
   *
   * @command civicrm:sql-query
   * @aliases civicrm-sql-query
   */
  public function sqlQuery(string $query): void {
    $parsed = $this->parseDsn($this->getCivicrmDsn());
    $cmd    = $this->buildMysqlCmd($parsed) . ' -e ' . escapeshellarg($query);
    $this->runShell($cmd);
  }

  /**
   * Open an interactive MySQL CLI using CiviCRM credentials.
   *
   * @command civicrm:sql-cli
   * @aliases cvsqlc,civicrm-sql-cli
   */
  public function sqlCli(): void {
    $parsed = $this->parseDsn($this->getCivicrmDsn());
    $cmd    = $this->buildMysqlCmd($parsed);
    // Replace current process image to keep stdin/stdout interactive.
    pcntl_exec('/bin/sh', ['-c', $cmd]);
  }

  // ---------------------------------------------------------------------------
  // Upgrade / restore (codebase operations)
  // ---------------------------------------------------------------------------

  /**
   * Back up, replace codebase, and run DB upgrade.
   *
   * @option tarfile
   *   Path to the new CiviCRM tarfile (required).
   * @option backup-dir
   *   Directory to write backups into.
   *
   * @command civicrm:upgrade
   * @aliases cvup,civicrm-upgrade
   */
  public function upgrade(): void {
    $this->initializeCivi();

    $tarfile = $this->input()->getOption('tarfile');
    if (empty($tarfile)) {
      throw new \InvalidArgumentException('--tarfile is required.');
    }
    if (!file_exists($tarfile)) {
      throw new \InvalidArgumentException(sprintf('Tarfile not found: %s', $tarfile));
    }

    global $civicrm_root;
    $date       = date('YmdHis');
    $basePath   = dirname($civicrm_root);
    $drupalRoot = \Drush\Drush::bootstrapManager()->getRoot();
    $backupDir  = rtrim($this->input()->getOption('backup-dir') ?: $drupalRoot . '/../backup', '/');
    $backupDir .= '/modules/' . $date;

    $this->io()->section('CiviCRM Upgrade');
    $this->io()->listing([
      'Backup codebase → ' . $backupDir . '/civicrm',
      'Backup database → ' . $backupDir . '/civicrm.sql',
      'Unpack tarfile  → ' . $basePath,
      'Run DB upgrade',
    ]);

    if (!$this->io()->confirm('Continue?')) {
      throw new UserAbortException();
    }

    @mkdir($backupDir, 0777, TRUE);

    // 1. Backup codebase.
    if (!rename($civicrm_root, $backupDir . '/civicrm')) {
      throw new \RuntimeException('Failed to back up CiviCRM codebase.');
    }
    $this->logger()->notice('Codebase backed up.');

    // 2. Backup database.
    $parsed  = $this->parseDsn($this->getCivicrmDsn());
    $dumpCmd = $this->buildMysqldumpCmd($parsed) . ' > ' . escapeshellarg($backupDir . '/civicrm.sql');
    $this->runShell($dumpCmd);
    $this->logger()->notice('Database backed up.');

    // 3. Unpack tarfile.
    $tarpath = $tarfile;
    if (str_ends_with($tarpath, '.gz')) {
      $this->runShell('gzip -d ' . escapeshellarg($tarpath));
      $tarpath = substr($tarpath, 0, -3);
    }
    $this->runShell('tar -xf ' . escapeshellarg($tarpath) . ' -C ' . escapeshellarg($basePath));
    $this->logger()->notice('Tarfile unpacked.');

    // 4. DB upgrade.
    $this->upgradeDb();
    $this->logger()->success('Upgrade complete.');
  }

  /**
   * Restore CiviCRM codebase and database from a backup directory.
   *
   * @option restore-dir
   *   Path to the backup directory (required).
   * @option backup-dir
   *   Directory to write the pre-restore safety backup into.
   *
   * @command civicrm:restore
   * @aliases civicrm-restore
   */
  public function restore(): void {
    $restoreDir = rtrim($this->input()->getOption('restore-dir') ?: '', '/');
    if (empty($restoreDir)) {
      throw new \InvalidArgumentException('--restore-dir is required.');
    }
    if (!file_exists($restoreDir . '/civicrm.sql')) {
      throw new \InvalidArgumentException('Could not find civicrm.sql in restore-dir.');
    }
    $codeDir = $restoreDir . '/civicrm';
    if (!is_dir($codeDir) || !file_exists($codeDir . '/civicrm-version.php')) {
      throw new \InvalidArgumentException('restore-dir does not contain a valid CiviCRM codebase.');
    }

    $this->initializeCivi();

    global $civicrm_root;
    $drupalRoot       = \Drush\Drush::bootstrapManager()->getRoot();
    $restoreBackupDir = rtrim($this->input()->getOption('backup-dir') ?: $drupalRoot . '/../backup', '/');
    $restoreBackupDir .= '/modules/restore/' . date('YmdHis');

    $parsed = $this->parseDsn($this->getCivicrmDsn());

    $this->io()->section('CiviCRM Restore');
    $this->io()->listing([
      'Restore codebase → ' . dirname($civicrm_root),
      'Drop & recreate DB: ' . $parsed['database'],
      'Load ' . $restoreDir . '/civicrm.sql',
      'Safety backup → ' . $restoreBackupDir,
    ]);

    if (!$this->io()->confirm('Continue?')) {
      throw new UserAbortException();
    }

    @mkdir($restoreBackupDir, 0777, TRUE);

    // 1. Backup & restore codebase.
    if (is_dir($civicrm_root)) {
      rename($civicrm_root, $restoreBackupDir . '/civicrm');
    }
    if (!rename($codeDir, $civicrm_root)) {
      throw new \RuntimeException('Failed to restore CiviCRM codebase.');
    }
    $this->logger()->notice('Codebase restored.');

    // 2. Backup current DB.
    $dumpCmd = $this->buildMysqldumpCmd($parsed) . ' > ' . escapeshellarg($restoreBackupDir . '/civicrm.sql');
    $this->runShell($dumpCmd);
    $this->logger()->notice('Current database backed up.');

    // 3. Drop & recreate.
    $credStr = $this->buildMysqlCredStr($parsed);
    $this->runShell(sprintf('mysql %s -e %s', $credStr, escapeshellarg('DROP DATABASE IF EXISTS `' . $parsed['database'] . '`')));
    $this->runShell(sprintf('mysql %s -e %s', $credStr, escapeshellarg('CREATE DATABASE `' . $parsed['database'] . '`')));
    $this->logger()->notice('Database recreated.');

    // 4. Restore.
    $this->runShell(sprintf('mysql %s < %s', $credStr, escapeshellarg($restoreDir . '/civicrm.sql')));
    $this->logger()->success('Restore complete.');
  }

  // ---------------------------------------------------------------------------
  // Internal helpers
  // ---------------------------------------------------------------------------

  /**
   * Bootstrap CiviCRM via the Drupal service.
   */
  protected function initializeCivi(): void {
    \Drupal::service('civicrm')->initialize();
  }

  /**
   * Toggle CiviCRM debug_enabled / backtrace settings.
   */
  protected function setDebug(bool $enable): void {
    $this->initializeCivi();

    $val      = $enable ? 1 : 0;
    $settings = ['debug_enabled' => $val, 'backtrace' => $val];

    foreach ($settings as $key => $settingVal) {
      $result = civicrm_api3('Setting', 'create', ['version' => 3, $key => $settingVal]);
      if (!empty($result['is_error'])) {
        throw new \RuntimeException(sprintf('Failed to set %s: %s', $key, $result['error_message']));
      }
    }
    $this->logger()->success(sprintf('CiviCRM debug %s.', $enable ? 'enabled' : 'disabled'));
  }

  /**
   * Return the CiviCRM DSN string.
   */
  protected function getCivicrmDsn(): string {
    $this->initializeCivi();

    if (!defined('CIVICRM_DSN') || empty(CIVICRM_DSN)) {
      throw new \RuntimeException('CIVICRM_DSN is not defined. Has CiviCRM been initialised?');
    }
    return CIVICRM_DSN;
  }

  /**
   * Parse a CiviCRM DSN into its components.
   *
   * @return array{driver: string, username: string, password: string, host: string, port: string, database: string}
   */
  protected function parseDsn(string $dsn): array {
    $parsed = parse_url($dsn);
    if ($parsed === FALSE) {
      throw new \RuntimeException('Could not parse CIVICRM_DSN: ' . $dsn);
    }
    return [
      'driver'   => $parsed['scheme'] ?? 'mysql',
      'username' => urldecode($parsed['user'] ?? ''),
      'password' => urldecode($parsed['pass'] ?? ''),
      'host'     => $parsed['host'] ?? 'localhost',
      'port'     => (string) ($parsed['port'] ?? '3306'),
      'database' => ltrim($parsed['path'] ?? '', '/'),
    ];
  }

  /**
   * Build a mysql credential string (flags only, no command name).
   */
  protected function buildMysqlCredStr(array $parsed): string {
    $cred  = '-u ' . escapeshellarg($parsed['username']);
    if (!empty($parsed['password'])) {
      $cred .= ' -p' . escapeshellarg($parsed['password']);
    }
    $cred .= ' -h ' . escapeshellarg($parsed['host']);
    if (!empty($parsed['port']) && $parsed['port'] !== '3306') {
      $cred .= ' -P ' . escapeshellarg($parsed['port']);
    }
    $cred .= ' ' . escapeshellarg($parsed['database']);
    return $cred;
  }

  /**
   * Build a full "mysql …" command string.
   */
  protected function buildMysqlCmd(array $parsed): string {
    return 'mysql ' . $this->buildMysqlCredStr($parsed);
  }

  /**
   * Build a full "mysqldump …" command string.
   */
  protected function buildMysqldumpCmd(array $parsed): string {
    $cmd  = 'mysqldump -u ' . escapeshellarg($parsed['username']);
    if (!empty($parsed['password'])) {
      $cmd .= ' -p' . escapeshellarg($parsed['password']);
    }
    $cmd .= ' -h ' . escapeshellarg($parsed['host']);
    if (!empty($parsed['port']) && $parsed['port'] !== '3306') {
      $cmd .= ' -P ' . escapeshellarg($parsed['port']);
    }

    if ($this->input()->hasOption('data-only') && $this->input()->getOption('data-only')) {
      $cmd .= ' --no-create-info';
    }

    $tablesList = $this->input()->hasOption('tables-list') ? $this->input()->getOption('tables-list') : NULL;

    $cmd .= ' ' . escapeshellarg($parsed['database']);
    if (!empty($tablesList)) {
      foreach (explode(',', $tablesList) as $table) {
        $cmd .= ' ' . escapeshellarg(trim($table));
      }
    }

    return $cmd;
  }

  /**
   * Run a shell command, streaming output and throwing on failure.
   */
  protected function runShell(string $cmd): void {
    $process = Process::fromShellCommandline($cmd);
    $process->setTimeout(NULL);
    $process->run(function (string $type, string $buffer): void {
      $this->output()->write($buffer);
    });

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }
  }

}


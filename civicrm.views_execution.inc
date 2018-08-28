<?php

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;

/**
 * Implements hook_views_query_alter().
 */
function civicrm_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  \Drupal::service('civicrm')->initialize();

  // check if we are in multilingual mode, otherwise return
  // TODO: should be a simple call - is_multiligual ?
  $domain = new CRM_Core_DAO_Domain();
  $domain->find(TRUE);

  $multilingual = (bool) $domain->locales;

  if ($multilingual) {
    global $dbLocale;
    $columns = CRM_Core_I18n_SchemaStructure::columns();

    // TODO: for better performance, loop on $query->fields instead
    foreach ($columns as $table => $hash) {
      foreach ($hash as $column => $type) {
        if (array_key_exists("{$table}_{$column}", $query->fields)) {
          $query->fields["{$table}_{$column}"]['field'] = "{$column}{$dbLocale}";
        }
      }
    }
  }
}

<?php

namespace Drupal\civicrm\Entity;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the SQL content storage to use the correct connection.
 */
class CivicrmDatabaseStorage extends SqlContentEntityStorage {

  /**
   * Attempt to use the `civicrm` database connection.
   *
   * If a 'civicrm' database connection is defined (ie. in settings.php),
   * attempt to use this for our entity backend.
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    // @Todo: find a way to pull in this alternative database connection via
    // the $container instead.
    if (!$database = Database::getConnection('civicrm')) {
      $database = $container->get('database');
    }
    return new static(
      $entity_type,
      $database,
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // Do nothing.
  }

}

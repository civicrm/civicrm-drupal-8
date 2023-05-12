<?php

namespace Drupal\civicrm_group_roles;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Civicrm group role rule entities.
 */
class CivicrmGroupRoleRuleListBuilder extends ConfigEntityListBuilder {

  /**
   * CiviCRM group roles service.
   *
   * @var \Drupal\civicrm_group_roles\CivicrmGroupRoles
   */
  protected $groupRoles;

  /**
   * User role storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $roleStorage;

  /**
   * CivicrmGroupRoleRuleListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityStorageInterface $roleStorage
   *   The use role storage class.
   * @param \Drupal\civicrm_group_roles\CivicrmGroupRoles $groupRoles
   *   CiviCRM group roles service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityStorageInterface $roleStorage, CivicrmGroupRoles $groupRoles) {
    parent::__construct($entity_type, $storage);
    $this->roleStorage = $roleStorage;
    $this->groupRoles = $groupRoles;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('civicrm_group_roles')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Association rule');
    $header['id'] = $this->t('Machine name');
    $header['group'] = $this->t('CiviCRM Group');
    $header['role'] = $this->t('Drupal Role');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\civicrm_group_roles\Entity\CivicrmGroupRoleRuleInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['group'] = $this->getGroupTitle($entity->getGroup());
    $row['role'] = $this->getRoleName($entity->getRole());
    return $row + parent::buildRow($entity);
  }

  /**
   * Gets the name for a specified role.
   *
   * @param string $rid
   *   The role ID.
   *
   * @return string
   *   The role name, or the ID if not found.
   */
  protected function getRoleName($rid) {
    if (!$role = Role::load($rid)) {
      return $rid;
    }
    return $role->label();
  }

  /**
   * Gets the name for a specified group.
   *
   * @param int $groupID
   *   The group ID.
   *
   * @return string
   *   The group name, or the ID if not found.
   */
  protected function getGroupTitle($groupID) {
    if (!$group = $this->groupRoles->getGroup($groupID)) {
      return $groupID;
    }
    return $group['title'];
  }

}

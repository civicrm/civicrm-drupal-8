<?php

namespace Drupal\civicrm_group_roles\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Civicrm group role rule entities.
 */
interface CivicrmGroupRoleRuleInterface extends ConfigEntityInterface {

  /**
   * Gets the role.
   *
   * @return string
   *   The role.
   */
  public function getRole();

  /**
   * Sets the role.
   *
   * @param string $role
   *   The role.
   *
   * @return $this
   */
  public function setRole($role);

  /**
   * Get group.
   *
   * @return string
   */
  public function getGroup();

  /**
   * Set group.
   *
   * @param string $group
   *
   * @return $this
   */
  public function setGroup($group);

}

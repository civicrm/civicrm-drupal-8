<?php

namespace Drupal\civicrm_group_roles\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Civicrm group role rule entity.
 *
 * @ConfigEntityType(
 *   id = "civicrm_group_role_rule",
 *   label = @Translation("Association Rule"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\civicrm_group_roles\CivicrmGroupRoleRuleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\civicrm_group_roles\Form\CivicrmGroupRoleRuleForm",
 *       "edit" = "Drupal\civicrm_group_roles\Form\CivicrmGroupRoleRuleForm",
 *       "delete" = "Drupal\civicrm_group_roles\Form\CivicrmGroupRoleRuleDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\civicrm_group_roles\CivicrmGroupRoleRuleHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "civicrm_group_role_rule",
 *   admin_permission = "access civicrm group role setting",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/civicrm/civicrm-group-roles/rule/{civicrm_group_role_rule}",
 *     "add-form" = "/admin/config/civicrm/civicrm-group-roles/rule/add",
 *     "edit-form" = "/admin/config/civicrm/civicrm-group-roles/rule/{civicrm_group_role_rule}/edit",
 *     "delete-form" = "/admin/config/civicrm/civicrm-group-roles/rule/{civicrm_group_role_rule}/delete",
 *     "collection" = "/admin/config/civicrm/civicrm-group-roles"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "role",
 *     "group",
 *   }
 * )
 */
class CivicrmGroupRoleRule extends ConfigEntityBase implements CivicrmGroupRoleRuleInterface {

  /**
   * The association rule ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The association rule label.
   *
   * @var string
   */
  protected $label;

  /**
   * The association rule role.
   *
   * @var string
   */
  protected $role;

  /**
   * The association rule group.
   *
   * @var string
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public function getRole() {
    return $this->role;
  }

  /**
   * {@inheritdoc}
   */
  public function setRole($role) {
    $this->role = $role;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup($group) {
    $this->group = $group;
    return $this;
  }

}

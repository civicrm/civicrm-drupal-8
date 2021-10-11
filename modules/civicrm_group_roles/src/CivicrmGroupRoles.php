<?php

namespace Drupal\civicrm_group_roles;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Class CivicrmGroupRoles.
 */
class CivicrmGroupRoles {

  /**
   * CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * CiviCRM group roles configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Inactive status IDs.
   *
   * Call ::getInactiveStatusIds instead of directly accessing this property.
   *
   * @var array|null
   */
  protected $inactiveStatusIds;

  /**
   * CivicrmGroupRoles constructor.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   CiviCRM service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(Civicrm $civicrm, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->civicrm = $civicrm;
    $this->config = $configFactory->get('civicrm_group_roles.settings');
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get information for a group.
   *
   * @param int $id
   *   The group ID.
   *
   * @return array|null
   *   The group, or NULL if not found.
   */
  public function getGroup($id) {
    try {
      $this->civicrm->initialize();
      $result = civicrm_api3('Group', 'get', ['options' => ['limit' => 0, 'sort' => "name"]]);
    }
    catch (\Exception $e) {
      $result = NULL;
    }

    return $result;
  }

  /**
   * Get groups.
   *
   * @return array
   *   groups, keyed by ID.
   */
  public function getGroups() {
    $groups = [];

    $this->civicrm->initialize();
    $result = civicrm_api3('Group', 'get');
    if (empty($result['values'])) {
      return $groups;
    }

    foreach ($result['values'] as $value) {
      $groups[$value['id']] = $value['title'];
    }

    return $groups;
  }

  /**
   * Sync user roles to group status.
   *
   * @param int|null $limit
   *   A limit for the number of contacts to sync.
   */
  public function sync($limit = NULL) {
    foreach ($this->getSyncContactIds($limit) as $cid) {
      if ($account = $this->getContactAccount($cid)) {
        $this->syncContact($cid, $account);
      }
    }
  }

  /**
   * Get contact IDs to sync.
   *
   * @param int|null $limit
   *   A limit for the number of IDs to return.
   *
   * @return array
   *   Contact IDs to sync.
   */
  public function getSyncContactIds($limit = NULL) {
    // Gather all of the contact types we have rules for.
    $groups = [];
    foreach ($this->getRules() as $rule) {
      $groups[] = $rule->getGroup();
    }
    $groups = array_unique($groups);

    // If no types, bail.
    if (!$groups) {
      return $groups;
    }

    // Find contacts with applicable types.
    $select = $this->getDatabase()
      ->select('civicrm_uf_match', 'uf')
      ->fields('uf', ['contact_id']);

    if ($limit) {
      $select->range(0, $limit)->orderRandom();
    }

    return $select->execute()->fetchCol();
  }

  /**
   * Loads all assignment rules.
   *
   * @return \Drupal\civicrm_group_roles\Entity\CivicrmGroupRoleRuleInterface[]
   *   The assignment rules.
   */
  protected function getRules() {
    return $this->entityTypeManager->getStorage('civicrm_group_role_rule')->loadMultiple();
  }

  /**
   * Sync group roles for a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function syncUser(AccountInterface $account) {
    if (!$contactId = $this->getUserContactId($account)) {
      return;
    }

    $this->syncContact($contactId, $account);
  }

  /**
   * Obtain the contact for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return int|null
   *   The contact ID, or NULL if not found.
   */
  public function getUserContactId(AccountInterface $account) {
    try {
      $params = ['uf_id' => $account->id()];
      $this->civicrm->initialize();
      $result = civicrm_api3('UFMatch', 'getsingle', $params);
    }
    catch (\Exception $e) {
      return NULL;
    }

    return $result['contact_id'];
  }

  /**
   * Obtain the user account for a contact.
   *
   * @param int $cid
   *   The contact ID.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The contact ID, or NULL if not found.
   */
  public function getContactAccount($cid) {
    try {
      $this->civicrm->initialize();
      $params = ['contact_id' => $cid];
      $result = civicrm_api3('UFMatch', 'getsingle', $params);
    }
    catch (\Exception $e) {
      return NULL;
    }

    return $this->entityTypeManager->getStorage('user')->load($result['uf_id']);
  }

  /**
   * Get group data for a contact.
   *
   * @param int $contactId
   *   The contact ID.
   *
   * @return array
   *   Contact group data.
   */
  protected function getContactGroups($contactId) {
    $params = [
      'contact_id' => $contactId,
      'status' => "Added",
      'options' => ['limit' => 0],
    ];
    //TODO: Support smart groups.
    try {
      $this->civicrm->initialize();
      $result = civicrm_api3('GroupContact', 'get', $params);
    }
    catch (\Exception $e) {
      return [];
    }

    return $result['values'];
  }

  /**
   * Return if debugging is enabled in settings.
   */
  protected function isDebuggingEnabled() {
    $config = \Drupal::config('civicrm_group_roles.settings');
    if (empty($config->get('debugging'))) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Syncs the role for the user related to a contact.
   *
   * @param int $cid
   *   The contact ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function syncContact($cid, AccountInterface $account) {
    // The inital set of roles assigned to the user.
    $userRoles = $account->getRoles();
    $rules = $this->getRules();
    $rulesMapping = $this->getRulesMapping($rules);
    foreach ($rulesMapping as $rule) {
      $contacts = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $cid,
        'group' => $rule['group'],
      ]);
      if ($contacts['count'] > 0) {
        if ($this->isDebuggingEnabled()) {
          $msg = 'Role @role should be held by user @user (@uid) because they are part of group @group (contactID: @cid).';
          $params = [
            '@role' => $rule['role'],
            '@user' => $account->get('name')->value,
            '@uid' => $account->get('uid')->value,
            '@group' => $rule['group'],
            '@cid' => $cid,
          ];
          \Drupal::logger('civicrm_group_roles')->info($msg, $params);
        }
        $contactGroups[] = $rule['group'];
      }
    }

    if (empty($contactGroups)) {
      // Remove the roles managed by CiviCRM groups.
      $newRoles = array_diff($userRoles, array_column($rulesMapping, 'role'));
    }
    else {
      $newRoles = array_unique(array_merge($userRoles, $this->getAddRoles($rules, $contactGroups)));
    }

    // If changes to the user roles were made, save it.
    if ($userRoles != $newRoles) {
      $user = $this->getAccountUser($account);
      $user->roles = $newRoles;
      $user->save();
    }
  }

  /**
   * Returns a mapping of role => group rules.
   *
   * @param \Drupal\civicrm_group_roles\Entity\CivicrmGroupRoleRuleInterface[] $rules
   *   Assignment rules.
   *
   * @return array
   */
  protected function getRulesMapping(array $rules) {
    $ruleMapping = [];
    foreach ($rules as $rule) {
      $ruleMapping[] = [
        'role' => $rule->getRole(),
        'group' => $rule->getGroup()
      ];
    }
    return $ruleMapping;
  }

  /**
   * Finds roles used in a set of assignment rules.
   *
   * @param \Drupal\civicrm_group_roles\Entity\CivicrmGroupRoleRuleInterface[] $rules
   *   Assignment rules.
   * @param array role
   *  If specified, groups synced to this role is returned.
   *
   * @return array
   *   The roles found in the assignment rules.
   */
  protected function getRulesGroups(array $rules, $roles = NULL) {
    $groups = [];
    foreach ($rules as $rule) {
      if (!empty($roles)) {
        if (in_array($rule->getRole(), $roles)) {
          $groups[] = $rule->getGroup();
        }
      }
      else {
        $groups[] = $rule->getGroup();
      }
    }
    return array_unique($groups);
  }

  /**
   * Helper function to add a user to Civi groups depending on their roles, when
   * the user is first created
   */
  public function addGroupsOnCreate($account, $roles) {
    $this->civicrm->initialize();
    $contactId = \CRM_Core_BAO_UFMatch::getContactId($account->get('uid')->value);
    if (!$contactId) {
      return;
    }
    //Get groups synced with the roles.
    $ruleGroups = $this->getRulesGroups($this->getRules(), $roles);
    $groups = $this->validateGroups($ruleGroups);
    foreach ($groups as $groupID) {
      $groupContact = new \CRM_Contact_DAO_GroupContact();
      $groupContact->group_id = $groupID;
      $groupContact->contact_id = $contactId;
      if (!$groupContact->find(TRUE)) {
        // add the contact to group
        $historyParams = [
          'contact_id' => $contactId,
          'group_id' => $groupID,
          'method' => 'API',
          'status' => 'Added',
          'date' => date('YmdHis'),
          'tracking' => NULL,
        ];
        \CRM_Contact_BAO_SubscriptionHistory::create($historyParams);
        $groupContact->status = 'Added';
        $groupContact->save();
      }
    }
  }

/**
 * Filters invalid groups out of a civicrm_group_roles_rules query result
 *
 * @param array $groups Result of a Drupal Query::execute() against
 *                      civicrm_group_roles_rules - An array of stdClass objects
 *                      having a group_id property
 * @return array
 */
function validateGroups(array $groups) {
  foreach ($groups as $key => $groupId) {
    $group_result = civicrm_api3('Group', 'get', array(
      'group_id' => $groupId,
      'sequential' => 1,
    ));

    // CRM-16033: Ensure the group hasn't been deleted
    if ($group_result['count'] === 0) {
      $msg = 'Error: Cannot add contact to nonexistent group (ID @groupId)';
      $variables = ['@groupId' => $groupId];
      \Drupal::logger('civicrm_group_roles')->error($msg, $variables);
      unset($groups[$key]);
      continue;
    }

    // CRM-11161: Exclude smart groups as we don't want to add contacts statically to a smart group
    if (!empty($group_result['values'][0]['saved_search_id'])) {
      if ($this->isDebuggingEnabled()) {
        $msg = 'Group ID @groupId is a smart group, so the user was not added to it statically.';
        $variables = ['@groupId' => $groupId];
        \Drupal::logger('civicrm_group_roles')->info($msg, $variables);
      }
      unset($groups[$key]);
      continue;
    }
  }
  return $groups;
}


  /**
   * Gets the user for a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  protected function getAccountUser(AccountInterface $account) {
    if ($account instanceof UserInterface) {
      return $account;
    }

    return $this->entityTypeManager->getStorage('user')->load($account->id());
  }

  /**
   * Gets roles to add for a contact's groups.
   *
   * @param \Drupal\civicrm_group_roles\Entity\CivicrmGroupRoleRuleInterface[] $rules
   *   Assignment rules.
   * @param array $groups
   *   Contact group data.
   *
   * @return array
   *   The roles to add for a contact.
   */
  protected function getAddRoles(array $rules, array $groups) {
    $roles = [];
    foreach ($groups as $groupId) {
      // Find rules applicable to the group type.
      $groupRules = array_filter($rules, function ($rule) use ($groupId) {
        /* @var \Drupal\civicrm_group_roles\Entity\CivicrmGroupRoleRuleInterface */
        return $rule->getGroup() == $groupId;
      });
      foreach ($groupRules as $rule) {
        $roles[] = $rule->getRole();
      }
    }

    return array_unique($roles);
  }

  /**
   * Gets the database.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database.
   */
  protected function getDatabase() {
    return \Drupal::database();
  }

}

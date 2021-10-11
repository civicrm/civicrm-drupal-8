<?php

namespace Drupal\civicrm_group_roles\Form;

use Drupal\civicrm_group_roles\CivicrmGroupRoles;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CivicrmGroupRoleRuleForm.
 */
class CivicrmGroupRoleRuleForm extends EntityForm {

  /**
   * CiviCRM group roles service.
   *
   * @var \Drupal\civicrm_group_roles\CivicrmGroupRoles
   */
  protected $groupRoles;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  protected $messenger;

  /**
   * CivicrmGroupRoleRuleForm constructor.
   *
   * @param \Drupal\civicrm_group_roles\CivicrmGroupRoles $groupRoles
   *   CiviCRM group roles service.
   */
  public function __construct(
    CivicrmGroupRoles $groupRoles,
    MessengerInterface $messenger
  ) {
    $this->groupRoles = $groupRoles;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civicrm_group_roles'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\civicrm_group_roles\Entity\CivicrmGroupRoleRuleInterface $rule */
    $rule = $this->entity;
    \Drupal::service('civicrm')->initialize();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $rule->label(),
      '#description' => $this->t('Label for the association rule.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $rule->id(),
      '#machine_name' => [
        'exists' => '\Drupal\civicrm_group_roles\Entity\CivicrmGroupRoleRule::load',
      ],
      '#disabled' => !$rule->isNew(),
    ];

    $groups = $this->groupRoles->getGroups();
    $roles = user_roles(TRUE);

    $form['add_rule'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Association Rule'),
      '#description' => $this->t('Choose a CiviCRM Group and a Drupal Role below.'),
    ];

    $form['add_rule']['group'] = [
      '#type' => 'select',
      '#title' => $this->t('CiviCRM Group'),
      '#options' => $groups,
      '#required' => TRUE,
      '#default_value' => $this->entity->getGroup(),
    ];

    $form['add_rule']['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Drupal Role'),
      '#options' => [],
      '#required' => TRUE,
      '#default_value' => $this->entity->getRole(),
    ];
    foreach ($roles as $role) {
      if ($role->id() != 'authenticated') {
        $form['add_rule']['role']['#options'][$role->id()] = $role->label();
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\civicrm_group_roles\Entity\CivicrmGroupRoleRuleInterface $rule */
    $rule = $this->entity;

    $rule->setGroup($form_state->getValue('group'))
      ->setRole($form_state->getValue('role'));

    $status = $rule->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label rule.', [
          '%label' => $rule->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label rule.', [
          '%label' => $rule->label(),
        ]));
    }
    $form_state->setRedirectUrl($rule->toUrl('collection'));
  }

}

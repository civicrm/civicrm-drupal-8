<?php

namespace Drupal\civicrm_group_roles\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Civicrm group role rule entities.
 */
class CivicrmGroupRoleRuleDeleteForm extends EntityConfirmFormBase {

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  protected $messenger;

  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.civicrm_group_role_rule.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger->addMessage(
      $this->t('Deleted association rule @label.',
        [
          '@label' => $this->entity->label(),
        ]
      )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}

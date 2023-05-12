<?php

namespace Drupal\civicrm_group_roles\Form;

use Drupal\civicrm_group_roles\Batch\Sync;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ManualSyncForm.
 */
class ManualSyncForm extends FormBase {

  /**
   * CiviCRM group roles sync batch.
   *
   * @var \Drupal\civicrm_group_roles\Batch\Sync
   */
  protected $sync;

  /**
   * CivicrmMemberRoleRuleForm constructor.
   *
   * @param \Drupal\civicrm_group_roles\Batch\Sync $sync
   *   CiviCRM group roles service.
   */
  public function __construct(Sync $sync) {
    $this->sync = $sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('civicrm_group_roles.batch.sync'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_group_roles_manual_sync';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['manual_sync'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Manual Synchronization:'),
    ];

    $form['manual_sync']['manual_sync_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Synchronize CiviCRM groups to Drupal Roles now'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = $this->sync->getBatch();
    batch_set($batch);
  }

}

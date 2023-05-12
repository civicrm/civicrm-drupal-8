<?php

namespace Drupal\civicrm_group_roles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['civicrm_group_roles.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_group_roles_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('civicrm_group_roles.settings');

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    $form['settings']['debugging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable detailed database logging'),
      '#description' => $this->t('Log the details of roles that are added and removed from users.'),
      '#default_value' => $config->get('debugging'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('civicrm_group_roles.settings');
    $config->set('debugging', $form_state->getValue('debugging'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

<?php

namespace Drupal\civicrm\Form;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Drupal\Core\Cache\Cache;

/**
 * Contains a form that allows for editing of the user profile.
 */
class UserProfile extends FormBase {

  /**
   * The user account if the user is already logged in.
   *
   * @var \Drupal\Core\Session\AccountInterface|null
   */
  protected $user;

  /**
   *
   */
  protected $profile;

  /**
   *
   */
  protected $contactId;

  /**
   *
   */
  protected $ufGroup;

  /**
   * Constructs class.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   */
  public function __construct(Civicrm $civicrm) {
    $civicrm->initialize();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civicrm')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_user_profile';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL, $profile = NULL) {
    // Make the controller state available to form overrides.
    $form_state->set('controller', $this);
    $this->user = $user;
    $this->profile = $profile;

    // Search for the profile form, otherwise generate a 404.
    $uf_groups = \CRM_Core_BAO_UFGroup::getModuleUFGroup('User Account');
    if (empty($uf_groups[$profile])) {
      throw new ResourceNotFoundException();
    }
    $this->ufGroup = $uf_groups[$profile];

    // Grab the form html.
    $this->contactId = \CRM_Core_BAO_UFMatch::getContactId($user->id());
    $html = \CRM_Core_BAO_UFGroup::getEditHTML($this->contactId, $this->ufGroup['title']);

    $form['#title'] = $this->user->getUsername();
    $form['form'] = [
      '#type' => 'fieldset',
      '#title' => $this->ufGroup['title'],
      'html' => [
        '#markup' => Markup::create($html),
      ],
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => t('Save'),
        '#button_type' => 'primary',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $errors = \CRM_Core_BAO_UFGroup::isValid($this->contactId, $this->ufGroup['title']);

    if (is_array($errors)) {
      foreach ($errors as $name => $error) {
        $form_state->setErrorByName($name, $error);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Somehow, somewhere, CiviCRM is processing our form. I have no idea how.
    // Invalidate caches for user, so that latest profile information shows.
    Cache::invalidateTags(['user:' . $this->user->id()]);
    drupal_set_message($this->t("Profile successfully updated."));
  }

  /**
   * Controls access for this form.
   */
  public function access($profile) {
    $uf_groups = \CRM_Core_BAO_UFGroup::getModuleUFGroup('User Account', 0, FALSE, \CRM_Core_Permission::EDIT);

    if (isset($uf_groups[$profile])) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}

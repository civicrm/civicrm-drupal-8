<?php

namespace Drupal\civicrm;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Collects Drupal event subscribers destined for CiviCRM's dispatcher.
 *
 * Drupal auto-discovers this class (named "<Module>ServiceProvider" in the
 * module's namespace). During container compilation it gathers every service
 * tagged "civicrm.event_subscriber" and hands their IDs to the registrar, which
 * attaches them to \Civi::dispatcher() once CiviCRM has booted.
 *
 * @see \Drupal\civicrm\CivicrmEventSubscriberInterface
 * @see \Drupal\civicrm\EventSubscriberRegistrar
 * @see civicrm_civicrm_config()
 */
class CivicrmServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $container->registerForAutoconfiguration(CivicrmEventSubscriberInterface::class)
      ->addTag('civicrm.event_subscriber');
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if (!$container->hasDefinition('civicrm.event_subscriber_registrar')) {
      return;
    }

    $registrar = $container->getDefinition('civicrm.event_subscriber_registrar');
    $ids = [];
    foreach ($container->findTaggedServiceIds('civicrm.event_subscriber') as $id => $tags) {
      // The registrar resolves these services by ID at runtime (only when
      // CiviCRM boots), so they must remain public and must not be optimized
      // away as "unused".
      $container->getDefinition($id)->setPublic(TRUE);
      $ids[] = $id;
    }

    // Constructor argument 1: list of subscriber service IDs.
    $registrar->setArgument(1, $ids);
  }

}

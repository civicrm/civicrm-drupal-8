<?php

namespace Drupal\civicrm;

use Civi\Core\CiviEventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Registers Drupal event subscribers onto CiviCRM's event dispatcher.
 *
 * CiviCRM maintains its own service container and Symfony event dispatcher
 * (\Civi::dispatcher()), entirely separate from Drupal's. As a result, Drupal
 * event subscribers tagged "event_subscriber" never receive the Symfony events
 * that CiviCRM broadcasts (e.g. "civi.token.eval", "civi.api4.authorizeRecord",
 * "hook_civicrm_post", or any event published by a CiviCRM extension).
 *
 * This registrar bridges the gap: any Drupal service tagged
 * "civicrm.event_subscriber" is collected at container-compile time (see
 * \Drupal\civicrm\CivicrmServiceProvider) and attached to \Civi::dispatcher()
 * once CiviCRM has booted (see civicrm_civicrm_config()). Such a service is a
 * normal \Symfony\Component\EventDispatcher\EventSubscriberInterface whose
 * getSubscribedEvents() returns CiviCRM event names.
 */
class EventSubscriberRegistrar {

  /**
   * The Drupal service container.
   *
   * Injected so subscriber services are resolved on demand, i.e. only when
   * CiviCRM actually boots, rather than on every Drupal request.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * Service IDs of subscribers tagged "civicrm.event_subscriber".
   *
   * @var string[]
   */
  protected array $subscriberIds;

  /**
   * The subscribers attached to each dispatcher, with their container.
   *
   * Static, because CiviCRM keeps its dispatcher when Drupal rebuilds its
   * container, and the rebuilt container has a registrar of its own. A
   * \WeakMap, because a dispatcher CiviCRM replaces is freed, and PHP can give
   * the freed object's ID to a later one.
   *
   * @var \WeakMap|null
   */
  protected static ?\WeakMap $dispatchers = NULL;

  /**
   * Constructs the registrar.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   * @param string[] $subscriber_ids
   *   Service IDs of the tagged subscribers (supplied by the service provider).
   */
  public function __construct(ContainerInterface $container, array $subscriber_ids = []) {
    $this->container = $container;
    $this->subscriberIds = $subscriber_ids;
  }

  /**
   * Attaches all tagged Drupal subscribers to CiviCRM's dispatcher.
   *
   * Safe to call repeatedly: hook_civicrm_config (the trigger) may fire more
   * than once per process, and from more than one Drupal container, but each
   * dispatcher holds the subscribers of one container only, once.
   */
  public function register(): void {
    if (!$this->subscriberIds || !class_exists('\Civi')) {
      return;
    }

    $dispatcher = \Civi::dispatcher();
    if ($dispatcher) {
      $this->attach($dispatcher);
    }
  }

  /**
   * Attaches this container's tagged subscribers to a dispatcher.
   *
   * Attaching again from the same container does nothing. Subscribers that
   * another container attached, one Drupal has rebuilt since, are removed
   * first: they hold that container's services, and with both attached each
   * event would reach every subscriber twice.
   *
   * @param \Civi\Core\CiviEventDispatcherInterface $dispatcher
   *   CiviCRM's dispatcher.
   */
  public function attach(CiviEventDispatcherInterface $dispatcher): void {
    self::$dispatchers ??= new \WeakMap();
    $attached = self::$dispatchers[$dispatcher] ?? NULL;
    if ($attached && $attached['container'] === $this->container) {
      return;
    }
    foreach ($attached['subscribers'] ?? [] as $subscriber) {
      $dispatcher->removeSubscriber($subscriber);
    }

    $subscribers = [];
    foreach ($this->subscriberIds as $id) {
      try {
        $subscriber = $this->container->get($id);
      }
      catch (\Throwable $e) {
        // A single broken subscriber must not prevent CiviCRM from booting.
        \Drupal::logger('civicrm')->error('Unable to load CiviCRM event subscriber %id: @message', [
          '%id' => $id,
          '@message' => $e->getMessage(),
        ]);
        continue;
      }

      if ($subscriber instanceof EventSubscriberInterface) {
        $dispatcher->addSubscriber($subscriber);
        $subscribers[] = $subscriber;
      }
    }
    self::$dispatchers[$dispatcher] = [
      'container' => $this->container,
      'subscribers' => $subscribers,
    ];
  }

}

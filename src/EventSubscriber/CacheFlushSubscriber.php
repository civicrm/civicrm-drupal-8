<?php

namespace Drupal\civicrm\EventSubscriber;

use Drupal\civicrm\Cache\CivicrmCacheInvalidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Invalidates Drupal caches when CiviCRM clears its system caches.
 */
class CacheFlushSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a CiviCRM cache-flush subscriber.
   *
   * @param \Drupal\civicrm\Cache\CivicrmCacheInvalidator $cacheInvalidator
   *   The Drupal cache invalidator for CiviCRM-derived data.
   */
  public function __construct(
    protected CivicrmCacheInvalidator $cacheInvalidator,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return ['civi.core.clearcache' => 'onClearCache'];
  }

  /**
   * Invalidates the Drupal caches affected by CiviCRM data.
   */
  public function onClearCache(): void {
    $this->cacheInvalidator->invalidate();
  }

}

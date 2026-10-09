<?php

namespace Drupal\civicrm\Cache;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Invalidates Drupal caches whose contents are derived from CiviCRM data.
 */
class CivicrmCacheInvalidator {

  /**
   * Constructs a CiviCRM cache invalidator.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The Drupal cache-tags invalidator.
   * @param \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface $blockManager
   *   The block plugin manager.
   * @param \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface $localTaskManager
   *   The local-task plugin manager.
   */
  public function __construct(
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected CachedDiscoveryInterface $blockManager,
    protected CachedDiscoveryInterface $localTaskManager,
  ) {
  }

  /**
   * Invalidates Drupal data which may have changed during a CiviCRM rebuild.
   */
  public function invalidate(): void {
    $this->cacheTagsInvalidator->invalidateTags(['civicrm']);
    $this->blockManager->clearCachedDefinitions();
    $this->localTaskManager->clearCachedDefinitions();
  }

}

<?php

namespace Drupal\Tests\civicrm\Unit\Cache;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\civicrm\Cache\CivicrmCacheInvalidator;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests invalidation of Drupal caches derived from CiviCRM data.
 *
 * @group civicrm
 *
 * @coversDefaultClass \Drupal\civicrm\Cache\CivicrmCacheInvalidator
 */
class CivicrmCacheInvalidatorTest extends UnitTestCase {

  /**
   * Tests targeted invalidation of CiviCRM-dependent Drupal caches.
   *
   * @covers ::invalidate
   */
  public function testInvalidate(): void {
    $cache_tags_invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $cache_tags_invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['civicrm']);

    $block_manager = $this->createMock(CachedDiscoveryInterface::class);
    $block_manager->expects($this->once())
      ->method('clearCachedDefinitions');

    $local_task_manager = $this->createMock(CachedDiscoveryInterface::class);
    $local_task_manager->expects($this->once())
      ->method('clearCachedDefinitions');

    $invalidator = new CivicrmCacheInvalidator(
      $cache_tags_invalidator,
      $block_manager,
      $local_task_manager,
    );
    $invalidator->invalidate();
  }

}

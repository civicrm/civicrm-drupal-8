<?php

namespace Drupal\Tests\civicrm\Unit\EventSubscriber;

use Drupal\civicrm\Cache\CivicrmCacheInvalidator;
use Drupal\civicrm\EventSubscriber\CacheFlushSubscriber;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CiviCRM cache-flush event subscriber.
 *
 * @group civicrm
 *
 * @coversDefaultClass \Drupal\civicrm\EventSubscriber\CacheFlushSubscriber
 */
class CacheFlushSubscriberTest extends UnitTestCase {

  /**
   * Tests event registration and delegation to the cache invalidator.
   *
   * @covers ::getSubscribedEvents
   * @covers ::onClearCache
   */
  public function testClearCacheEvent(): void {
    $this->assertSame(
      ['civi.core.clearcache' => 'onClearCache'],
      CacheFlushSubscriber::getSubscribedEvents(),
    );

    $invalidator = $this->createMock(CivicrmCacheInvalidator::class);
    $invalidator->expects($this->once())
      ->method('invalidate');

    $subscriber = new CacheFlushSubscriber($invalidator);
    $subscriber->onClearCache();
  }

}

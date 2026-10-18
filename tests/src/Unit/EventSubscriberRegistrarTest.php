<?php

namespace Drupal\Tests\civicrm\Unit;

use Civi\Core\CiviEventDispatcher;
use Drupal\civicrm\EventSubscriberRegistrar;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests attaching Drupal subscribers to CiviCRM's dispatcher.
 *
 * @group civicrm
 *
 * @coversDefaultClass \Drupal\civicrm\EventSubscriberRegistrar
 */
class EventSubscriberRegistrarTest extends UnitTestCase {

  /**
   * Tests that attaching twice from one container attaches once.
   *
   * @covers ::attach
   */
  public function testAttachingAgainDoesNothing(): void {
    $dispatcher = new CiviEventDispatcher();
    [$registrar, $subscriber] = $this->registrar();

    $registrar->attach($dispatcher);
    $registrar->attach($dispatcher);
    $dispatcher->dispatch('civi.test');

    $this->assertSame(1, $subscriber->calls);
  }

  /**
   * Tests that a rebuilt container's subscribers replace the old ones.
   *
   * @covers ::attach
   */
  public function testRebuiltContainerReplacesTheSubscribers(): void {
    $dispatcher = new CiviEventDispatcher();
    [$old_registrar, $old] = $this->registrar();
    [$new_registrar, $new] = $this->registrar();

    $old_registrar->attach($dispatcher);
    $new_registrar->attach($dispatcher);
    $dispatcher->dispatch('civi.test');

    $this->assertSame(0, $old->calls);
    $this->assertSame(1, $new->calls);
    $this->assertCount(1, $dispatcher->getListeners('civi.test'));
  }

  /**
   * Tests that every new dispatcher is populated.
   *
   * @covers ::attach
   */
  public function testEachDispatcherIsPopulated(): void {
    [$registrar, $subscriber] = $this->registrar();
    $first = new CiviEventDispatcher();
    $second = new CiviEventDispatcher();

    $registrar->attach($first);
    $registrar->attach($second);
    $first->dispatch('civi.test');
    $second->dispatch('civi.test');

    $this->assertSame(2, $subscriber->calls);
  }

  /**
   * Builds a registrar whose container holds one subscriber.
   *
   * @return array
   *   The registrar and the subscriber.
   */
  private function registrar(): array {
    $subscriber = new class() implements EventSubscriberInterface {

      /**
       * How many events have reached the subscriber.
       *
       * @var int
       */
      public int $calls = 0;

      /**
       * {@inheritdoc}
       */
      public static function getSubscribedEvents(): array {
        return ['civi.test' => 'onTest'];
      }

      /**
       * Counts an event.
       */
      public function onTest(): void {
        $this->calls++;
      }

    };
    $container = new Container();
    $container->set('civicrm_test.subscriber', $subscriber);
    return [new EventSubscriberRegistrar($container, ['civicrm_test.subscriber']), $subscriber];
  }

}

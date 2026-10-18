<?php

namespace Drupal\Tests\civicrm\Unit;

use Drupal\civicrm\Civicrm;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CiviCRM service.
 *
 * @group civicrm
 *
 * @coversDefaultClass \Drupal\civicrm\Civicrm
 */
class CivicrmTest extends UnitTestCase {

  /**
   * Tests that the settings file is looked for where initialize() reads it.
   *
   * @covers ::hasSettingsFile
   */
  public function testHasSettingsFile(): void {
    $site = sys_get_temp_dir() . '/civicrm-test-' . bin2hex(random_bytes(4));
    mkdir($site);
    $kernel = $this->createStub(DrupalKernelInterface::class);
    $kernel->method('getSitePath')->willReturn($site);
    $container = new ContainerBuilder();
    $container->set('kernel', $kernel);
    \Drupal::setContainer($container);

    try {
      $civicrm = new Civicrm();
      $this->assertFalse($civicrm->hasSettingsFile());
      touch("$site/civicrm.settings.php");
      $this->assertTrue($civicrm->hasSettingsFile());
    }
    finally {
      @unlink("$site/civicrm.settings.php");
      rmdir($site);
    }
  }

}

<?php

namespace Drupal\Tests\civicrm\Unit;

use Drupal\civicrm\Civicrm;
use Drupal\civicrm\CivicrmPermissions;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CiviCRM permission callback.
 *
 * @group civicrm
 *
 * @coversDefaultClass \Drupal\civicrm\CivicrmPermissions
 */
class CivicrmPermissionsTest extends UnitTestCase {

  /**
   * Tests that nothing boots CiviCRM before its settings file exists.
   *
   * Drupal collects permissions whenever a role is saved, including the roles
   * of modules installed in one group with civicrm, before civicrm_install()
   * has written the settings file.
   *
   * @covers ::__construct
   * @covers ::permissions
   */
  public function testNoPermissionsBeforeInstall(): void {
    $civicrm = $this->createMock(Civicrm::class);
    $civicrm->method('hasSettingsFile')->willReturn(FALSE);
    $civicrm->expects($this->never())->method('initialize');

    $this->assertSame([], (new CivicrmPermissions($civicrm))->permissions());
  }

}

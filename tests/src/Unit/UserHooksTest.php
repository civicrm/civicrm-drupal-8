<?php

namespace Drupal\Tests\civicrm\Unit;

use Drupal\civicrm\Civicrm;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the user hooks before CiviCRM is installed.
 *
 * Drupal's user_install() saves the anonymous and administrator accounts.
 * When civicrm is installed in the same group, that happens before
 * civicrm_install() has written the settings file.
 *
 * @group civicrm
 */
class UserHooksTest extends UnitTestCase {

  /**
   * Tests that the user hooks leave CiviCRM alone until it is installed.
   *
   * @covers ::civicrm_user_insert
   * @covers ::civicrm_user_update
   * @covers ::civicrm_user_delete
   */
  public function testUserHooksBeforeInstall(): void {
    require_once dirname(__DIR__, 3) . '/civicrm.module';
    $civicrm = $this->createMock(Civicrm::class);
    $civicrm->method('hasSettingsFile')->willReturn(FALSE);
    $civicrm->expects($this->never())->method('initialize');
    $container = new ContainerBuilder();
    $container->set('civicrm', $civicrm);
    \Drupal::setContainer($container);

    $account = $this->createStub(AccountInterface::class);
    civicrm_user_insert($account);
    civicrm_user_update($account);
    civicrm_user_delete($account);
  }

}

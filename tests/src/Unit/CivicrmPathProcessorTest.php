<?php

namespace Drupal\Tests\Unit {

use Drupal\civicrm\Civicrm;
use Drupal\civicrm\PathProcessor\CivicrmPathProcessor;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CivicrmPathProcessorTest
 *
 * @group civicrm-drupal
 */
class CivicrmPathProcessorTest extends UnitTestCase {

  /**
   * Tests path processing.
   */
  public function testProcessInbound() {
    $civicrm = $this->prophesize(Civicrm::class);
    $civicrm->initialize()->willReturn(TRUE);
    $sut = new CivicrmPathProcessor($civicrm->reveal());

    $r = new Request();
    $this->assertEquals('/foo/', $sut->processInbound('/foo/', $r));
    $this->assertEquals('/civicrm/', $sut->processInbound('/civicrm/', $r));
    $this->assertEquals('/civicrm/fo', $sut->processInbound('/civicrm/fo', $r));
    $this->assertEquals('/civicrm/foo', $sut->processInbound('/civicrm/foo', $r));
    $this->assertEquals('/civicrm/foo/', $sut->processInbound('/civicrm/foo/', $r));
    $this->assertEquals('/civicrm/foo/bar', $sut->processInbound('/civicrm/foo/bar', $r));
    $this->assertEquals('/civicrm/things/bar', $sut->processInbound('/civicrm/things/bar', $r));
    $this->assertEquals('/civicrm/things/bar/foo', $sut->processInbound('/civicrm/things/bar/foo', $r));
    $this->assertEquals('/civicrm/things/bar?foo=1&qux=2', $sut->processInbound('/civicrm/things/bar?foo=1&qux=2', $r));
    $this->assertEquals('/civicrm/things:bar', $sut->processInbound('/civicrm/things:bar', $r));
  }

}
}

/**
 * Mock CRM_Core_Menu from the global namespace.
 */
namespace {
  class CRM_Core_Menu {
    public static function items() {
      return [
        '/civicrm/fo/',
        '/civicrm/foo/',
        '/civicrm/foo/bar/',
        '/civicrm/bar/',
        '/civicrm/things/',
      ];
    }
  }
}


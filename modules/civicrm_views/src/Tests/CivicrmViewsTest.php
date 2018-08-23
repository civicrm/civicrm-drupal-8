<?php

namespace Drupal\civicrm_views\Tests;

use Drupal\civicrm\Tests\CivicrmTestBase;
use Drupal\views\Views;

/**
 * Tests basic CiviCRM views functionality.
 *
 * @group CiviCRM
 */
class CivicrmViewsTest extends CivicrmTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['civicrm_views', 'civicrm_views_config'];

  /**
   * Disables the config schema checking.
   *
   * @var bool
   *
   * @Todo: Provide schema declaraction
   */
  protected $strictConfigSchema = FALSE;

  /**
   * An array of contact data.
   *
   * @var array
   */
  protected $contactData = [
    [
      'contact_type' => 'Individual',
      'first_name' => 'John',
      'last_name' => 'Smith',
      'api.email.create' => [
        [
          'email' => 'john.smith@example.com',
          'is_primary' => TRUE,
        ],
      ],
      'api.address.create' => [
        [
          'street_address' => '14 Main Street',
          'is_primary' => TRUE,
          'location_type_id' => 'Home',
        ],
      ],
      'api.entity_tag.create' => [
        'tag_id' => 'Volunteer',
      ],
      'api.relationship.create' => [
        // Employee of.
        'relationship_type_id' => 5,
        'contact_id_a' => '$value.id',
        // Default Organization.
        'contact_id_b' => 1,
      ],
    ],
    [
      'contact_type' => 'Individual',
      'first_name' => 'Jane',
      'last_name' => 'Smith',
      'api.email.create' => [
        [
          'email' => 'jane.smith@example.com',
          'is_primary' => TRUE,
        ],
        [
          'email' => 'jane.smithy@example.com',
        ],
      ],
      'api.address.create' => [
        [
          'street_address' => '3 Broadway Avenue',
          'is_primary' => TRUE,
          'location_type_id' => 'Work',
        ],
        [
          'street_address' => '5 Garden Grove',
          'location_type_id' => 'Home',
        ],
      ],
      'api.entity_tag.create' => [
        'tag_id' => 'Company',
      ],
      'api.relationship.create' => [
        // Employee of.
        'relationship_type_id' => 5,
        'contact_id_a' => '$value.id',
        // Default Organization.
        'contact_id_b' => 1,
      ],
    ],
  ];

  /**
   * Creates data needed for the test.
   */
  protected function createData() {
    foreach ($this->contactData as $contact) {
      civicrm_api3('Contact', 'create', $contact);
    }

    $result = civicrm_api3('Contact', 'get', [
      'options' => ['limit' => 100],
      'api.email.get' => 1,
      'api.entity_tag.get' => 1,
      'api.address.get' => 1,
    ]);

    $this->assertTrue(empty($result['is_error']), "api.contact.get result OK.");
    $this->assertEqual(3, count($result['values']), "3 contacts have been created.");
    $this->verbose("<pre>" . var_export($result, TRUE) . "</pre>");
  }

  /**
   * Tests a CiviCRM view.
   */
  public function testCivicrmViewsTest() {
    $this->createData();

    // @Todo: Why do we need to call this?
    $view = Views::getView('contacts');
    $this->dieOnFail = TRUE;
    $this->assertTrue(is_object($view), "View object loaded.");
    $this->dieOnFail = FALSE;

    $output = $view->preview();
    $output = \Drupal::service('renderer')->render($output, TRUE);
    $this->setRawContent($output);

    $xpath = $this->xpath('//div[@class="view-content"]');
    $this->assertTrue($xpath, 'View content has been found in the rendered output.');

    $this->verbose($this->getRawContent());

    $xpath = $this->xpath('//tbody/tr');
    $this->assertEqual(3, count($xpath), "There are 3 rows in the table.");

    foreach ($xpath as $key => $tr) {
      if ($key == 0) {
        // Skip Default Organization.
        continue;
      }

      $this->assertEqual("{$this->contactData[$key - 1]['first_name']} {$this->contactData[$key - 1]['last_name']}", trim((string) $tr->td[1]));
      $this->assertEqual($this->contactData[$key - 1]['api.email.create'][0]['email'], trim((string) $tr->td[2]));
      $this->assertEqual($this->contactData[$key - 1]['api.address.create'][0]['street_address'], trim((string) $tr->td[3]));
      $this->assertEqual($this->contactData[$key - 1]['api.entity_tag.create']['tag_id'], trim((string) $tr->td[4]));
      $this->assertEqual('Default Organization', trim((string) $tr->td[5]));
    }
  }

}

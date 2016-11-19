<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Crypt;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;
use Drupal\domain\DomainInterface;

abstract class DomainTestBase extends BrowserTestBase {

  /**
   * Sets a base hostname for running tests.
   *
   * When creating test domains, try to use $this->base_hostname or the
   * domainCreateTestDomains() method.
   */
  public $base_hostname;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'node');

  /**
   * We use the standard profile for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set the base hostname for domains.
    $this->base_hostname = \Drupal::service('domain.creator')->createHostname();
  }

  /**
   * Generates a list of domains for testing.
   *
   * In my environment, I use the example.com hostname as a base. Then I name
   * hostnames one.* two.* up to ten. Note that we always use *_example_com
   * for the machine_name (entity id) value, though the hostname can vary
   * based on the system. This naming allows us to load test schema files.
   *
   * The script may also add test1, test2, test3 up to any number to test a
   * large number of domains.
   *
   * @param int $count
   *   The number of domains to create.
   * @param string|NULL $base_hostname
   *   The root domain to use for domain creation (e.g. example.com).
   * @param array $list
   *   An optional list of subdomains to apply instead of the default set.
   */
  public function domainCreateTestDomains($count = 1, $base_hostname = NULL, $list = array()) {
    $original_domains = \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    if (empty($base_hostname)) {
      $base_hostname = $this->base_hostname;
    }
    // Note: these domains are rigged to work on my test server.
    // For proper testing, yours should be set up similarly, but you can pass a
    // $list array to change the default.
    if (empty($list)) {
      $list = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten');
    }
    for ($i = 0; $i < $count; $i++) {
      if (!empty($list[$i])) {
        if ($i < 11) {
          $hostname = $list[$i] . '.' . $base_hostname;
          $machine_name = $list[$i] . '.example.com';
          $name = ucfirst($list[$i]);
        }
        // These domains are not setup and are just for UX testing.
        else {
          $hostname = 'test' . $i . '.' . $base_hostname;
          $machine_name = 'test' . $i . '.example.com';
          $name = 'Test ' . $i;
        }
      }
      else {
        $hostname = $base_hostname;
        $machine_name = 'example.com';
        $name = 'Example';
      }
      // Create a new domain programmatically.
      $values = array(
        'hostname' => $hostname,
        'name' => $name,
        'id' => \Drupal::service('domain.creator')->createMachineName($machine_name),
      );
      $domain = \Drupal::entityTypeManager()->getStorage('domain')->create($values);
      $domain->save();
    }
    $domains = \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    $this->assertTrue((count($domains) - count($original_domains)) == $count, new FormattableMarkup('Created %count new domains.', array('%count' => $count)));
  }

  /**
   * Adds a test domain to an entity.
   *
   * @param string $entity_type
   *   The entity type being acted upon.
   * @param int $entity_id
   *   The entity id.
   * @param int $id
   *   The id of the domain to add.
   * @param string $field
   *   The name of the domain field used to attach to the entity.
   */
  public function addDomainToEntity($entity_type, $entity_id, $id, $field) {
    if ($entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
      $entity->set($field, $id);
      $entity->save();
    }
  }

  /**
   * Creates a default administrative user with all necessary permissions.
   */
  public function createDomainAdmin() {
    $account = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
      'create domains',
      'administer users',
    ]);
    return $account;
  }

}

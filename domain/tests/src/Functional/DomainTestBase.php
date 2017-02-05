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
      if ($i === 0) {
        $hostname = $base_hostname;
        $machine_name = 'example.com';
        $name = 'Example';
      }
      elseif (!empty($list[$i])) {
        $hostname = $list[$i] . '.' . $base_hostname;
        $machine_name = $list[$i] . '.example.com';
        $name = 'Test ' . ucfirst($list[$i]);
      }
      // These domains are not setup and are just for UX testing.
      else {
        $hostname = 'test' . $i . '.' . $base_hostname;
        $machine_name = 'test' . $i . '.example.com';
        $name = 'Test ' . $i;
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
   * @param array $ids
   *   An array of ids to add.
   * @param string $field
   *   The name of the domain field used to attach to the entity.
   */
  public function addDomainsToEntity($entity_type, $entity_id, $ids, $field) {
    if ($entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
      $entity->set($field, $ids);
      $entity->save();
    }
  }

  /**
   * The methods below are brazenly copied from Rules module. They are all
   * helper methods that make writing tests a bit easier.
   */

  /**
   * Finds link with specified locator.
   *
   * @param string $locator
   *   Link id, title, text or image alt.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The link node element.
   */
  public function findLink($locator) {
    return $this->getSession()->getPage()->findLink($locator);
  }

  /**
   * Finds field (input, textarea, select) with specified locator.
   *
   * @param string $locator
   *   Input id, name or label.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The input field element.
   */
  public function findField($locator) {
    return $this->getSession()->getPage()->findField($locator);
  }

  /**
   * Finds button with specified locator.
   *
   * @param string $locator
   *   Button id, value or alt.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The button node element.
   */
  public function findButton($locator) {
    return $this->getSession()->getPage()->findButton($locator);
  }

  /**
   * Presses button with specified locator.
   *
   * @param string $locator
   *   Button id, value or alt.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function pressButton($locator) {
    $this->getSession()->getPage()->pressButton($locator);
  }

  /**
   * Fills in field (input, textarea, select) with specified locator.
   *
   * @param string $locator
   *   Input id, name or label.
   * @param string $value
   *   Value.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *
   * @see \Behat\Mink\Element\NodeElement::setValue
   */
  public function fillField($locator, $value) {
    $this->getSession()->getPage()->fillField($locator, $value);
  }

  /**
   * Checks checkbox with specified locator.
   *
   * @param string $locator input id, name or label
   *
   * @throws ElementNotFoundException
   */
  public function checkField($locator) {
    $this->getSession()->getPage()->checkField($locator);
  }

  /**
   * Unchecks checkbox with specified locator.
   *
   * @param string $locator input id, name or label
   *
   * @throws ElementNotFoundException
   */
  public function uncheckField($locator) {
    $this->getSession()->getPage()->uncheckField($locator);
  }

  /**
   * Selects option from select field with specified locator.
   *
   * @param string  $locator  input id, name or label
   * @param string  $value    option value
   * @param Boolean $multiple select multiple options
   *
   * @throws ElementNotFoundException
   *
   * @see NodeElement::selectOption
   */
  public function selectFieldOption($locator, $value, $multiple = false) {
    $this->getSession()->getPage()->selectFieldOption($locator, $value, $multiple);
  }

  /**
   * Returns an uncached list of all domains.
   *
   * @return array
   *   An array of domain entities.
   */
  public function getDomains() {
    return \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
  }

  /**
   * Returns an uncached list of all domains, sorted by weight.
   *
   * @return array
   *   An array of domain entities.
   */
  public function getDomainsSorted() {
    \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    return \Drupal::service('domain.loader')->loadMultipleSorted();
  }
}

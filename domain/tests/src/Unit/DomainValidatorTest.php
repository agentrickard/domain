<?php

namespace Drupal\Tests\domain\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\domain\DomainValidator;

/**
 * Tests domain record validation.
 *
 * @group domain
 */
class DomainValidatorTest extends UnitTestCase {

  /** @var \Drupal\domain\DomainValidator */
  private $object;

  /** @var \GuzzleHttp\Client */
  public $client;

  /**
   * Set up a service container and SUT (Subject Under Test).
   *
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    /** @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $mock = $this->getMockBuilder('Drupal\Core\Config\ImmutableConfig')
      ->disableOriginalConstructor()->getMock();
    $mock->expects(static::any())->method('get')
      ->willReturnMap([
        ['allow_non_ascii', FALSE],
        ['www-prefix', FALSE]
      ]);

    /** @var \Drupal\Core\Config\ConfigFactory $factory */
    $factory = $mock = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()->getMock();
    $mock->expects(static::once())->method('get')->willReturn($config);

    /** @var \Drupal\Core\Entity\EntityStorageBase $storage */
    $storage = $mock = $this->getMockBuilder('Drupal\Core\Entity\EntityStorageBase')
      ->disableOriginalConstructor()->getMock();
    $mock->expects(static::any())->method('loadByProperties')->willReturn(array());

    /** @var \Drupal\Core\Entity\EntityTypeManager $manager */
    $manager = $mock = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()->getMock();
    $mock->expects(static::any())->method('getStorage')
      ->willReturn($storage);

    /** @var \Drupal\Core\Extension\ModuleHandler $handler */
    $handler = $mock = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandler')
      ->disableOriginalConstructor()->getMock();

    $response = $this->getMockBuilder('GuzzleHttp\Psr7\Response')
      ->disableOriginalConstructor()
      ->getMock();
    $response->expects(static::any())->method('getStatusCode')
      ->willReturn(200);

    static::assertNotEmpty($response);
    $client = $this->getMockBuilder('GuzzleHttp\Client')
      ->setMethods(['get'])
      ->disableOriginalConstructor()
      ->getMock();
    $client->expects(static::any())->method('get')
      ->will(static::returnValue($response));

    $this->object = new DomainValidator($handler, $factory, $client, $manager);
  }

  /**
   * @param string $host
   * @param int $result
   *
   * @dataProvider provider
   */
  public function testValidateStaticInspection($host, $result) {
    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = $mock = $this->getMockBuilder('Drupal\domain\Entity\Domain')
      ->disableOriginalConstructor()->getMock();
    $mock->expects(static::any())->method('getHostname')
      ->willReturn($host);
    $errors = $this->object->validate($domain);
    static::assertNotEquals((bool) $result, (bool) count($errors));
  }

  /**
   * @return array
   */
  public function provider() {
    return [
      ['localhost', 1],
      ['example.com', 1],
      ['www.example.com', 1], // see www-prefix test, below.
      ['one.example.com', 1],
      ['example.com:8080', 1],
      // these tests work, but translation service mock is not yet working
//      ['example.com::8080', 0], // only one colon.
//      ['example.com:abc', 0], // no letters after a colon.
//      ['.example.com', 0], // cannot begin with a dot.
//      ['example.com.', 0], // cannot end with a dot.
//      ['EXAMPLE.com', 0], // lowercase only.
//      ['éxample.com', 0], // ascii-only.
    // this should be moved to a test of the Drupal logic, outside the string tests
//      ['foo.com', 0], // duplicate.
    ];
  }

  public function testCheckResponse() {
    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = $mock = $this->getMockBuilder('Drupal\domain\Entity\Domain')
      ->disableOriginalConstructor()->getMock();
    $mock->expects(static::once())->method('setResponse')->with(200);
    $this->object->checkResponse($domain, 'module/path');
  }

  /**
   * Tests that a domain hostname validates.
   */
  public function testGetRequiredFields() {
    $check = $this->object->getRequiredFields();
    static::assertInternalType('array', $check);
    static::assertGreaterThanOrEqual(6, count($check));
  }

}

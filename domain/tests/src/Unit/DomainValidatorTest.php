<?php

/**
 * Unit tests for domain record validation.
 */

namespace Drupal\Tests\domain\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\domain\DomainValidator;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;

/**
 * @coversDefaultClass \Drupal\domain\DomainValidator
 * @group domain
 */
class DomainValidatorTest extends UnitTestCase {

  /** @var \Drupal\domain\DomainValidator */
  private $object;

  /** @var \Drupal\Core\Config\ImmutableConfig */
  private $config;

  /** @var \PHPUnit_Framework_MockObject_MockObject */
  private $mockConfig;

  /** @var \Drupal\domain\Entity\Domain */
  private $domain;

  /** @var \PHPUnit_Framework_MockObject_MockObject */
  private $mockDomain;

  /** @var \PHPUnit_Framework_MockObject_MockObject */
  private $mockClient;

  /** @var \PHPUnit_Framework_MockObject_MockObject */
  private $mockResponse;

  /**
   * Set up a service container and SUT (Subject Under Test).
   *
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $handler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandler')
      ->disableOriginalConstructor()->getMock();

    $this->config = $this->mockConfig =
      $this->getMockBuilder('Drupal\Core\Config\ImmutableConfig')
        ->disableOriginalConstructor()
        ->setMethods(['get'])->getMock();

    $configFactory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()->getMock();
    $configFactory->expects(static::once())->method('get')
      ->willReturn($this->config);

    $this->mockResponse = $this->getMockBuilder('GuzzleHttp\Psr7\Response')
      ->disableOriginalConstructor()->getMock();

    $this->mockClient = $this->getMockBuilder('GuzzleHttp\Client')
      ->disableOriginalConstructor()->setMethods(['request'])->getMock();

    $domain = $this->getMockBuilder('Drupal\domain\Entity\Domain')
      ->disableOriginalConstructor()->setMethods(['id'])->getMock();
    $domain->expects(static::any())->method('id')->willReturn('foo_com');

    $storage = $this->getMockBuilder('Drupal\Core\Entity\EntityStorageBase')
      ->disableOriginalConstructor()->getMock();
    $storage->expects(static::any())->method('loadByProperties')
      ->willReturnCallback(function ($array) use ($domain) {
        return ($array['hostname'] === 'foo.com') ? array($domain) : array();
      });

    $manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()->getMock();
    $manager->expects(static::any())->method('getStorage')
      ->willReturn($storage);

    $stringTranslation =
      $this->getMockBuilder('\Drupal\Core\StringTranslation\TranslationManager')
        ->disableOriginalConstructor()
        ->setMethods(['translateString'])->getMock();

    $this->domain = $this->mockDomain =
      $this->getMockBuilder('Drupal\domain\Entity\Domain')
        ->disableOriginalConstructor()
        ->setMethods(['getHostname', 'id'])->getMock();

    $this->object = new DomainValidator($handler, $configFactory, $this->mockClient, $manager, $stringTranslation);
  }

  /**
   * @param $nonAscii
   * @param $prefix
   * @param $host
   * @param $expect
   *
   * @dataProvider providerValidate
   */
  public function testValidate($nonAscii, $prefix, $host, $expect) {
    $this->mockConfig->expects(static::exactly(2))->method('get')
      ->willReturnMap([['allow_non_ascii', $nonAscii], ['www_prefix', $prefix]]);
    $this->mockDomain->expects(static::once())->method('getHostname')
      ->willReturn($host);
    $this->mockDomain->expects(static::any())->method('id')
      ->willReturn(str_replace('.', '__', $host));

    $errors = $this->object->validate($this->domain);
    if ($expect) {
      static::assertInstanceOf('\Drupal\Core\StringTranslation\TranslatableMarkup', $errors);
    }
    else {
      static::assertInternalType('array', $errors);
      static::assertCount($expect, $errors);
    }
  }

  /**
   * Data provider for testValidate
   * @return array
   */
  public function providerValidate() {
    return [
      // These are invariant format check on hostname.
      [FALSE, FALSE, 'localhost',         0],
      [FALSE, FALSE, 'example.com',       0],
      [FALSE, FALSE, 'www.example.com',   0], // see www-prefix test, below.
      [FALSE, FALSE, 'one.example.com',   0],
      [FALSE, FALSE, 'example.com:8080',  0],
      [FALSE, FALSE, 'example.com::8080', 1], // only one colon.
      [FALSE, FALSE, 'example.com:abc',   1], // no letters after a colon.
      [FALSE, FALSE, '.example.com',      1], // cannot begin with a dot.
      [FALSE, FALSE, 'example.com.',      1], // cannot end with a dot.
      [FALSE, FALSE, 'EXAMPLE.com',       1], // lowercase only.
      // These depend on the module settings.
      [FALSE, FALSE, 'www.example.com', 0],
      [FALSE, FALSE,     'example.com', 0],
      [FALSE,  TRUE, 'www.example.com', 1],
      [FALSE,  TRUE,     'example.com', 0],
      [FALSE, FALSE,     'éxample.com', 1],
      [ TRUE, FALSE,     'éxample.com', 0],
      [FALSE, FALSE,         'foo.com', 1], // duplicate.
    ];
  }

  /**
   * @param int $statusCode
   *
   * @dataProvider providerCheckResponse
   */
  public function testCheckResponse($statusCode) {
    $this->mockClient->expects(static::any())->method('request')
      ->will(static::returnValue($this->mockResponse));
    $this->mockResponse->expects(static::any())->method('getStatusCode')
      ->willReturn($statusCode);

    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = $mock = $this->getMockBuilder('Drupal\domain\Entity\Domain')
      ->disableOriginalConstructor()->getMock();
    $mock->expects(static::once())->method('setResponse')->with($statusCode);
    $this->object->checkResponse($domain, 'module/path');
  }

  /**
   * Data provider for testCheckResponse.
   *
   * @return array
   */
  public function providerCheckResponse() {
    return [
      [200],
      [404],
    ];
  }

  /**
   * Test will not work due to use of global function watchdog_exception.
   *
   * See https://www.drupal.org/node/2116043
   * And https://www.drupal.org/node/2595985
   *
   * @todo Implement logger service in DomainValidator and enable test.
   */
  public function notestCheckResponseException() {
    $path = 'module/path';
    $this->mockClient->expects(static::any())->method('request')
      ->willThrowException(new BadResponseException('test', new Request('get', $path)));

    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = $mock = $this->getMockBuilder('Drupal\domain\Entity\Domain')
      ->disableOriginalConstructor()->getMock();
    $mock->expects(static::once())->method('setResponse')->with(500);
    $this->object->checkResponse($domain, $path);
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

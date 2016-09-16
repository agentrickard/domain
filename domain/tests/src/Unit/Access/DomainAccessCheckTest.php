<?php

namespace Drupal\Tests\domain\Unit\Access;

//use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\domain\Access\DomainAccessCheck;
use Drupal\Core\Access\AccessResult;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the domain record actions.
 *
 * @group domain
 */
class DomainAccessCheckTest extends UnitTestCase {

  /** @var \Drupal\domain\Access\DomainAccessCheck */
  private $object;

  /** @var \PHPUnit_Framework_MockObject_MockObject */
  private $mockNegotiator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $negotiator = $this->getMockBuilder('\Drupal\domain\DomainNegotiator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->mockNegotiator = $negotiator;

    $config = $this
      ->getMockBuilder('Drupal\Core\Config\ImmutableConfig')
      ->disableOriginalConstructor()->setMethods(['get'])->getMock();
    $config->expects(static::any())->method('get')
      ->with('login_paths')
      ->willReturn("/user/login\n/user/password");

    $configFactory = $this
      ->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()->setMethods(['get'])->getMock();
    $configFactory->expects(static::any())->method('get')
      ->willReturn($config);
    /** @var \Drupal\Core\Config\ConfigFactory $factory */

    $this->object = new DomainAccessCheck($negotiator, $configFactory);
  }

  /**
   * @param string $path
   *
   * @dataProvider providerCheckPathTrue
   */
  public function testAppliesTrue($path) {
    $route = $this->getMockBuilder('\Symfony\Component\Routing\Route')
      ->disableOriginalConstructor()
      ->getMock();
    $route->expects(static::once())->method('getPath')
      ->willReturn($path);
    static::assertTrue($this->object->applies($route));
  }

  /**
   * @param string $path
   *
   * @dataProvider providerCheckPathFalse
   */
  public function testAppliesFalse($path) {
    $route = $this->getMockBuilder('\Symfony\Component\Routing\Route')
      ->disableOriginalConstructor()->getMock();
    $route->expects(static::once())->method('getPath')
      ->willReturn($path);
    static::assertFalse($this->object->applies($route));
  }

  /**
   * @param string $path
   *
   * @dataProvider providerCheckPathTrue
   */
  public function testCheckPathTrue($path) {
    static::assertTrue($this->object->checkPath($path));
  }

  /**
   * @param string $path
   *
   * @dataProvider providerCheckPathFalse
   */
  public function testCheckPathFalse($path) {
    static::assertFalse($this->object->checkPath($path));
  }

  /**
   * @todo Find a way to mock AccessResult::allowedIfHasPermissions to complete test
   *
   * If AccessResult::allowedIfHasPermissions cannot be done by mock, then
   * it will need to be done as a web test case. Maybe an example in
   * comment\Unit\Entity\CommentLockTest
   */
  public function testAccess() {
    $domain = $this->getMockBuilder('\Drupal\domain\DomainInterface')
      ->disableOriginalConstructor()->getMock();
    $domain->expects(static::exactly(1))->method('status')
      ->willReturnOnConsecutiveCalls(TRUE, FALSE, FALSE);
    $this->mockNegotiator->expects(static::any())->method('getActiveDomain')
      ->willReturnOnConsecutiveCalls(NULL, $domain, $domain, $domain);

    $account = $this->getMockBuilder('\Drupal\Core\Session\AccountInterface')
      ->disableOriginalConstructor()->getMock();
    $account->expects(static::never())->method('hasPermission');

    $expected = AccessResult::allowed()->setCacheMaxAge(0);
    static::assertEquals($expected, $this->object->access($account));
    static::assertEquals($expected, $this->object->access($account));
  }

  /**
   * @return array
   */
  public function providerCheckPathTrue() {
    return [
      ['/user/1'],
      ['/user/admin'],
      ['/node/1'],
    ];
  }

  /**
   * @return array
   */
  public function providerCheckPathFalse() {
    return [
      ['/user/login'],
      ['/user/password'],
    ];
  }

}

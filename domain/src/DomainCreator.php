<?php

namespace Drupal\domain;

/**
 * Creates new domain records.
 *
 * This class is a helper that replaces legacy procedural code.
 */
class DomainCreator implements DomainCreatorInterface {

  /**
   * The Domain loader.
   *
   * @var \Drupal\domain\DomainLoaderInterface $loader
   */
  protected $loader;

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface $negotiator
   */
  protected $negotiator;

  /**
   * Constructs a DomainCreator object.
   *
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainLoaderInterface $loader, DomainNegotiatorInterface $negotiator) {
    $this->loader = $loader;
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function createDomain(array $values = array()) {
    $default = $this->loader->loadDefaultId();
    $domains = $this->loader->loadMultiple();
    if (empty($values)) {
      $values['hostname'] = $this->createHostname();
      $values['name'] = \Drupal::config('system.site')->get('name');
      $values['id'] = $this->createMachineName($values['hostname']);
    }
    $values += array(
      'scheme' => empty($GLOBALS['is_https']) ? 'http' : 'https',
      'status' => 1,
      'weight' => count($domains) + 1,
      'is_default' => (int) empty($default),
    );
    $domain = \Drupal::entityTypeManager()->getStorage('domain')->create($values);

    return $domain;
  }

  /**
   * {@inheritdoc}
   */
  public function createHostname() {
    return $this->negotiator->negotiateActiveHostname();
  }

  /**
   * {@inheritdoc}
   */
  public function createMachineName($hostname = NULL) {
    if (empty($hostname)) {
      $hostname = $this->createHostname();
    }
    return preg_replace('/[^a-z0-9_]/', '_', $hostname);
  }

}

<?php

namespace Drupal\domain_alias;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\domain\DomainInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Alias loader utility class.
 */
class DomainAliasStorage extends ConfigEntityStorage implements DomainAliasStorageInterface {

  /**
   * The typed config handler.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;


  /**
   * Sets the TypedConfigManager dependency.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config handler.
   */
  protected function setTypedConfigManager(TypedConfigManagerInterface $typed_config) {
    $this->typedConfig = $typed_config;
  }

  /**
   * Sets the request stack object dependency.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   */
  protected function setRequestStack(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->setTypedConfigManager($container->get('config.typed'));
    $instance->setRequestStack($container->get('request_stack'));

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function loadSchema() {
    $fields = $this->typedConfig->getDefinition('domain_alias.alias.*');
    return isset($fields['mapping']) ? $fields['mapping'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadByHostname($hostname) {
    $patterns = $this->getPatterns($hostname);
    foreach ($patterns as $pattern) {
      if ($alias = $this->loadByPattern($pattern)) {
        return $alias;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByPattern($pattern) {
    $result = $this->loadByProperties(['pattern' => $pattern]);
    if (empty($result)) {
      return NULL;
    }
    return current($result);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEnvironment($environment) {
    $result = $this->loadByProperties(['environment' => $environment]);
    if (empty($result)) {
      return NULL;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEnvironmentMatch(DomainInterface $domain, $environment) {
    $result = $this->loadByProperties(['domain_id' => $domain->id(), 'environment' => $environment]);
    if (empty($result)) {
      return [];
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function sort($a, $b) {
    // Fewer wildcards is a closer match.
    // A longer string indicates a closer match.
    if ((substr_count($a, '*') > substr_count($b, '*')) || (strlen($a) < strlen($b))) {
      return 1;
    }
    return 0;
  }

  /**
   * Returns an array of eligible matching patterns.
   *
   * @param string $hostname
   *   A hostname string, in the format example.com.
   *
   * @return array
   *   An array of eligible matching patterns.
   */
  public function getPatterns($hostname) {
    $parts = explode('.', $hostname);
    $count = count($parts);

    // Account for ports.
    $port = NULL;
    if (substr_count($hostname, ':') > 0) {
      // Extract port and save for later.
      $ports = explode(':', $parts[$count - 1]);
      $parts[$count - 1] = preg_replace('/:(\d+)/', '', $parts[$count - 1]);
      $port = $ports[1];
    }

    // Build the list of possible matching patterns.
    $patterns = $this->buildPatterns($parts);
    // Pattern lists are sorted based on the fewest wildcards. That gives us
    // more precise matches first.
    uasort($patterns, [$this, 'sort']);
    // Re-assemble parts without port
    array_unshift($patterns, implode('.', $parts));

    // Account for ports.
    $patterns = $this->buildPortPatterns($patterns, $hostname, $port);

    // Return unique patters.
    return array_unique($patterns);
  }

  /**
   * Builds a list of matching patterns.
   *
   * @param array $parts
   *   The hostname of the request, as an array split by dots.
   *
   * @return array
   *   An array of eligible matching patterns.
   */
  private function buildPatterns(array $parts) {
    $count = count($parts);
    for ($i = 0; $i < $count; $i++) {
      // Basic replacement of each value.
      $temp = $parts;
      $temp[$i] = '*';
      $patterns[] = implode('.', $temp);
      // Advanced multi-value wildcards.
      // Pattern *.*.
      if (count($temp) > 2 && $i < ($count - 1)) {
        $temp[$i + 1] = '*';
        $patterns[] = implode('.', $temp);
      }
      // Pattern foo.bar.*.
      if ($count > 3 && $i < ($count - 2)) {
        $temp[$i + 2] = '*';
        $patterns[] = implode('.', $temp);
      }
      // Pattern *.foo.*.
      if ($count > 3 && $i < 2) {
        $temp = $parts;
        $temp[$i] = '*';
        $temp[$i + 2] = '*';
        $patterns[] = implode('.', $temp);
      }
      // Pattern *.foo.*.*.
      if ($count > 2) {
        $temp = array_fill(0, $count, '*');
        $temp[$i] = $parts[$i];
        $patterns[] = implode('.', $temp);
      }
    }
    return $patterns;
  }

  /**
   * Builds a list of matching patterns, including ports.
   *
   * @param array $patterns
   *   An array of eligible matching patterns.
   * @param string $hostname
   *   A hostname string, in the format example.com.
   * @param integer $port
   *   The port of the request.
   *
   * @return array
   *   An array of eligible matching patterns, modified by port.
   */
  private function buildPortPatterns(array $patterns, $hostname, $port = NULL) {
    // Fetch the port if empty.
    if (empty($port) && !empty($this->requestStack->getCurrentRequest())) {
      $port = $this->requestStack->getCurrentRequest()->getPort();
    }

    $new_patterns = [];
    foreach ($patterns as $index => $pattern) {
      // If default ports, allow exact no-port alias
      $new_patterns[] = $pattern . ':*';
      if (empty($port) || $port == 80 || $port == 443) {
        $new_patterns[] = $pattern;
      }
      if (!empty($port)) {
        $new_patterns[] = $pattern . ':' . $port;
      }
    }

    return $new_patterns;
  }

}

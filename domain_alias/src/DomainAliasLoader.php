<?php

namespace Drupal\domain_alias;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\domain\DomainInterface;

/**
 * Alias loader utility class.
 *
 * @deprecated
 *  This class will be removed before the 8.1.0 release.
 */
class DomainAliasLoader implements DomainAliasLoaderInterface {

  /**
   * The typed config handler.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * Constructs a DomainAliasLoader object.
   *
   * Trying to inject the storage manager throws an exception.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config handler.
   *
   * @see getStorage()
   */
  public function __construct(TypedConfigManagerInterface $typed_config) {
    $this->typedConfig = $typed_config;
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
  public function load($id, $reset = FALSE) {
    $controller = $this->getStorage();
    if ($reset) {
      $controller->resetCache([$id]);
    }
    return $controller->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL, $reset = FALSE) {
    $controller = $this->getStorage();
    if ($reset) {
      $controller->resetCache($ids);
    }
    return $controller->loadMultiple($ids);
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
    $result = $this->getStorage()->loadByProperties(['pattern' => $pattern]);
    if (empty($result)) {
      return NULL;
    }
    return current($result);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEnvironment($environment) {
    $result = $this->getStorage()->loadByProperties(['environment' => $environment]);
    if (empty($result)) {
      return NULL;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEnvironmentMatch(DomainInterface $domain, $environment) {
    $result = $this->getStorage()->loadByProperties(['domain_id' => $domain->id(), 'environment' => $environment]);
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
    if (substr_count($hostname, ':') > 0) {
      $ports = explode(':', $parts[$count - 1]);
      $parts[$count - 1] = preg_replace('/:(\d+)/', '', $parts[$count - 1]);
      $parts[] = $ports[1];
    }
    // Build the list of possible matching patterns.
    $patterns = $this->buildPatterns($parts);
    // Pattern lists are sorted based on the fewest wildcards. That gives us
    // more precise matches first.
    uasort($patterns, [$this, 'sort']);
    array_unshift($patterns, $hostname);

    // Account for ports.
    if (isset($ports)) {
      $patterns = $this->buildPortPatterns($patterns, $hostname);
    }

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
   *
   * @return array
   *   An array of eligible matching patterns, modified by port.
   */
  private function buildPortPatterns(array $patterns, $hostname) {
    foreach ($patterns as $index => $pattern) {
      // Make a pattern for port wildcards.
      if (substr_count($pattern, ':') < 1) {
        $new = explode('.', $pattern);
        $port = (int) array_pop($new);
        $allow = FALSE;
        // Do not allow *.* or *:*.
        foreach ($new as $item) {
          if ($item != '*') {
            $allow = TRUE;
          }
        }
        if ($allow) {
          // For port 80, allow bare hostname matches.
          if ($port == 80) {
            // Base hostname with port.
            $patterns[] = str_replace(':' . $port, '', $hostname);
            // Base hostname is allowed.
            $patterns[] = implode('.', $new);
          }
          // Base hostname with wildcard port.
          $patterns[] = str_replace(':' . $port, ':*', $hostname);
          // Pattern with exact port.
          $patterns[] = implode('.', $new) . ':' . $port;
          // Pattern with wildcard port.
          $patterns[] = implode('.', $new) . ':*';
        }
        unset($patterns[$index]);
      }
    }
    return $patterns;
  }

  /**
   * Loads the storage controller.
   *
   * We use the loader very early in the request cycle. As a result, if we try
   * to inject the storage container, we hit a circular dependency. Using this
   * method at least keeps our code easier to update.
   */
  protected function getStorage() {
    $storage = \Drupal::entityTypeManager()->getStorage('domain_alias');
    return $storage;
  }

}

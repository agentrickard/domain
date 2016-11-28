<?php
namespace Drupal\domain_config_ui\Config;

use Drupal\Core\Config\ConfigFactory as CoreConfigFactory;
use Drupal\domain_config_ui\Config\Config;

/**
 * Overrides Drupal\Core\Config\ConfigFactory in order to use our own Config class.
 */
class ConfigFactory extends CoreConfigFactory {
  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Config\ConfigFactory::createConfigObject()
   */
  protected function createConfigObject($name, $immutable) {
    // Always writeable.
    // @todo load immutable with overrides applied.
    return parent::createConfigObject($name, FALSE);
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Config\ConfigFactory::doLoadMultiple()
   */
  protected function doLoadMultiple(array $names, $immutable = TRUE) {
    // Load with $immutable set to FALSE to include config overrides.
    return parent::doLoadMultiple($names, TRUE);
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Config\ConfigFactory::doGet()
   */
  protected function doGet($name, $immutable = TRUE) {
    // Load with $immutable set to FALSE to include config overrides.
    return parent::doGet($name, TRUE);
  }
}

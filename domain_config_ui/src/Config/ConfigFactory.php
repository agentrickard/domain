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
    if (!$immutable) {
      return new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
    }
    return parent::createConfigObject($name, $immutable);
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

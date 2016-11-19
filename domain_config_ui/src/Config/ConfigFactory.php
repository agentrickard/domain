<?php
namespace Drupal\domain_config_ui\Config;

use Drupal\Core\Config\ConfigFactory as CoreConfigFactory;
use Drupal\domain_config_ui\Config\Config;

/**
 * Overrides Drupal\Core\Config\ConfigFactory in order to use our own Config class.
 */
class ConfigFactory extends CoreConfigFactory {
  protected function createConfigObject($name, $immutable) {
    if (!$immutable) {
      return new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
    }
    return parent::createConfigObject($name, $immutable);
  }
}

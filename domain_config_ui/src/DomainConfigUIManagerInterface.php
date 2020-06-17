<?php

namespace Drupal\domain_config_ui;

/**
 * Domain Config UI manager.
 */
interface DomainConfigUIManagerInterface {

  /**
   * Get selected config name.
   *
   * @param string $name
   *   The config name.
   * @param bool $omit_language
   *   A flag to indicate if the language-sensitive config should be loaded.
   *
   * @return string
   *   A config object name.
   */
  public function getSelectedConfigName($name, $omit_language = FALSE);

  /**
   * Get the selected domain ID.
   *
   * @return string
   *   A domain machine name.
   */
  public function getSelectedDomainId();

  /**
   * Get the selected language ID.
   *
   * @return string
   *   A language code.
   */
  public function getSelectedLanguageId();

}

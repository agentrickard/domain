<?php

namespace Drupal\domain_config_ui;

/**
 * Contains helper classes for the user interface.
 *
 * Some methods are called from a form and an Ajax callback, so we have those
 * shared methods in this trait.
 */
trait DomainConfigUITrait {

  /**
   * Adds a path to the registry.
   *
   * @param string $new_path
   *   The path to add.
   *
   * @return string
   *   The normalized path that was added.
   */
  public function addPath($new_path) {
    $config = \Drupal::configFactory()->getEditable('domain_config_ui.settings');
    $path_string = $config->get('path_pages');

    $path_array = $this->explodePathSettings($path_string);
    $path_array[] = $new_path;

    $path_string = $this->implodePathSettings($path_array);
    $config->set('path_pages', $path_string)->save();

    return $path_string;
  }

  /**
   * Removes a path from the registry.
   *
   * @param string $old_path
   *   The path to remove.
   *
   * @return string
   *   The normalized path that was removed.
   */
  public function removePath($old_path) {
    $config = \Drupal::configFactory()->getEditable('domain_config_ui.settings');
    $path_string = $config->get('path_pages');

    $path_array = $this->explodePathSettings($path_string);
    $list = array_flip($path_array);
    if (isset($list[$old_path])) {
      unset($list[$old_path]);
    }
    $path_array = array_flip($list);

    $path_string = $this->implodePathSettings($path_array);
    $config->set('path_pages', $path_string)->save();

    return $path_string;
  }

  /**
   * Turns an array of paths into a linebreak separated string.
   *
   * @param array $path_array
   *   An array of registered paths.
   *
   * @return string
   *   A normalized string of paths.
   */
  public function implodePathSettings(array $path_array) {
    return implode("\r\n", $path_array);
  }

  /**
   * Turns the path string into an array.
   *
   * @param string $path_string
   *   An string of registered paths.
   *
   * @return array
   *   A normalized array of paths.
   */
  public function explodePathSettings($path_string) {
    // Replace newlines with a logical 'or'.
    $find = '/(\\r\\n?|\\n)/';
    $replace = '|';
    $list = preg_replace($find, $replace, $path_string);
    return explode("|", $list);
  }

  /**
   * Normalizes the path string using \r\n for linebreaks.
   *
   * @param string $path_string
   *   The string of paths.
   *
   * @return string
   *   A normalized path string.
   */
  public function standardizePaths($path_string) {
    return $this->implodePathSettings($this->explodePathSettings($path_string));
  }

}

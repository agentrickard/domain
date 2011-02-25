<?php

/**
 * @file
 *  Domain Views plugin that caches views on a per domain basis.
 *  This is necessary for views that filter on "current domain"
 *  (ex. SELECT * FROM {node} WHERE domain_source = current_domain)
 *  otherwise "current domain" will be cached.
 *
 * Code by bleen16
 * @link http://drupal.org/user/77375
 * @link http://drupal.org/node/782208
 */

/**
 * Cache plugin that provides caching on a per domain basis.
 */
class domain_views_plugin_cache_time extends views_plugin_cache_time {
  function summary_title() {
    // Return a sumary title for the views admin screen (ex. 1 min/1min (Per Domain)).
    return format_interval($this->options['results_lifespan'], 1) . '/' . format_interval($this->options['output_lifespan'], 1) . ' (Per Domain)';
  }

  function get_results_key() {
    /**
     * Create an md5 hashed key including the current domain
     * to use as the cache key for caching the views results.
     */
    global $user;
    global $_domain;

    if (!isset($this->_results_key)) {
      $key_data = array(
        'build_info' => $this->view->build_info,
        'roles' => array_keys($user->roles),
        'super-user' => $user->uid == 1, // Special caching for super user.
        'language' => $GLOBALS['language'],
        'domain' => $_domain['domain_id'], // Adding current domain to key data.
      );
      foreach (array('exposed_info', 'page', 'sort', 'order') as $key) {
        if (isset($_GET[$key])) {
          $key_data[$key] = $_GET[$key];
        }
      }
      // Set the results key.
      $this->_results_key = $this->view->name . ':' . $this->display->id . ':results:' . md5(serialize($key_data));
    }

    return $this->_results_key;
  }

  function get_output_key() {
    /**
     * Create an md5 hashed key including the current domain
     * to use as the cache key for caching the views output.
     */
    global $user;
    global $_domain;

    if (!isset($this->_output_key)) {
      $key_data = array(
        'result' => $this->view->result,
        'roles' => array_keys($user->roles),
        'super-user' => $user->uid == 1, // Special caching for super user.
        'theme' => $GLOBALS['theme'],
        'language' => $GLOBALS['language'],
        'domain' => $_domain['domain_id'], // Adding current domain to key data.
      );
      // Match the results key.
      $this->_output_key = $this->view->name . ':' . $this->display->id . ':output:' . md5(serialize($key_data));
    }

    return $this->_output_key;
  }
}

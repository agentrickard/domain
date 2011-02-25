<?php
/**
 * @file
 * Interface between domain_views.module and views.module.
 *
 * @ingroup domain_views
 */

/**
 * Field handler to provide simple renderer that allows linking to a domain.
 *
 * @ingroup domain_views
 */
class domain_views_handler_field_domain_id extends views_handler_field {
  /**
   * Constructor to provide additional field to add.
   */
  function construct() {
    parent::construct();
    $this->additional_fields['domain_id'] = 'domain_id';
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_default_domain'] = array('default' => FALSE);
    $options['link_to_original_domain'] = array('default' => FALSE);
    $options['list_all_domains'] = array('default' => FALSE);
    return $options;
  }

  /**
   * Provide link to default domain option
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_domain_id'] = array(
      '#title' => t('Link this field to its domain by domain_id'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_domain_id']),
    );
    $form['link_to_sitename'] = array(
      '#title' => t('Link this field to its domain by sitename'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_sitename']),
    );
  }


  function query() {
    parent::query();
    // The {domain_alias} table uses GID instead of DOMAIN_ID.
    if (isset($this->query->fields['domain_access_gid'])) {
      $this->query->fields['domain_access_domain_id']['field'] = 'gid';
    }
  }

  function render($values) {
    $domain_id = $values->{$this->field_alias};
    $domain = domain_lookup($domain_id);
    if (empty($domain)) {
      return;
    }
    $text = $domain_id;
    $path = $domain['path'];
    if (!empty($this->options['link_to_sitename'])) {
      $text = $domain['sitename'];
    }
    return l($text, $path);
  }
}

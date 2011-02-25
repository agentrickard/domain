<?php


/**
 * @file
 *  Domain Views plugin that restricts View display based on the current domain.
 *  This plugin respects hook_domaingrants().

/**
 * Access plugin that provides permission-based access control.
 */
class domain_views_plugin_access extends views_plugin_access {
  function access($account) {
    return domain_views_access(array_filter($this->options['domains']), $this->options['domain_strict'], $this->options['domain_member']);
  }

  function get_access_callback() {
    return array('domain_views_access', array(array_filter($this->options['domains']), $this->options['domain_strict'], $this->options['domain_member']));
  }

  function summary_title() {
    return t('Domains');
  }

  function option_defaults(&$options) {
    $options['domains'] = array(-1);
    $options['domain_strict'] = FALSE;
    $options['domain_member'] = FALSE;
  }

  function options_form(&$form, &$form_state) {
    $domains = domain_domains();
    $options = array();
    $format = domain_select_format();
    foreach ($domains as $domain) {
      // Checkboxes cannot handles zeros.
      if ($domain['domain_id'] == 0) {
        $domain['domain_id'] = -1;
      }
      // The domain must be valid.
      if ($domain['valid'] || user_access('access inactive domains')) {
        // Filter checkboxes but not select lists.
        $options[$domain['domain_id']] = empty($format) ? check_plain($domain['sitename']) : $domain['sitename'];
      }
    }
    $form['domains'] = array(
      '#type' => empty($format) ? 'checkboxes' : 'select',
      '#multiple' => TRUE,
      '#options' => $options,
      '#title' => t('Domains'),
      '#default_value' => $this->options['domains'],
      '#description' => t('This display will only be available on the selected domains. Note that users with "access all views" can see any view, regardless of other permissions.'),
    );
    if ($format) {
      $form['domains']['#multiple'] = TRUE;
      $form['domains']['#size'] = count($options) > 10 ? 10 : count($options);
    }
    $form['domain_strict'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use strict access control'),
      '#default_value' => $this->options['domain_strict'],
      '#description' => t('If checked, the user must be able to access the domain being viewed.'),
    );
    $form['domain_member'] = array(
      '#type' => 'checkbox',
      '#title' => t('Require domain membership'),
      '#default_value' => $this->options['domain_member'],
      '#description' => t('If checked, the user must be a member of the domain being viewed.'),
    );
  }
}

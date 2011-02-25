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
class domain_views_handler_field_domain_sitename extends views_handler_field {
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
    $form['link_to_default_domain'] = array(
      '#title' => t('Link this field to default domain'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_default_domain']),
    );
    $form['link_to_original_domain'] = array(
      '#title' => t('Link this field to the domain it originates from'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_original_domain']),
    );
    // Only list the node links if this is a node view.
    if ($this->view->base_table == 'node' || $this->view->base_table == 'node_revisions') {
      $form['list_all_domains'] = array(
        '#title' => t('List all domains this node is published to'),
        '#description' => t('This will override the default setting to show only one domain.'),
        '#type' => 'checkbox',
        '#default_value' => !empty($this->options['list_all_domains']),
      );
    }
  }


  function pre_render($values) {
    // If there are no values to render (displaying a summary, or query returned no results)
    // do nothing specific.
    if (isset($this->view->build_info['summary']) || empty($values)) {
      return parent::pre_render($values);
    }
    $options = $this->options;
    // Only alter output if requested.
    if (($this->view->base_table == 'node' || $this->view->base_table == 'node_revisions') && !empty($options['list_all_domains'])) {
      // Build a list of nids
      $nids = array();
      foreach ($values as $value) {
        if (isset($value->node_nid)) {
          $nids[] = $value->node_nid;
        }
        else {
          $nids[] = $value->nid;
        }
      }
      $this->field_values = array();
      $query = "SELECT node.nid AS nid, domain.sitename AS domain_sitename, domain.domain_id AS domain_domain_id
      FROM node node  LEFT JOIN domain_access domain_access ON node.nid = domain_access.nid
      INNER JOIN domain domain ON domain_access.gid = domain.domain_id AND (domain_access.realm = 'domain_id')
      WHERE node.nid  IN (". implode(',', $nids) .")";
      $result = db_query($query);
      while ($item = db_fetch_array($result)) {
        $this->field_values[$item['nid']][$item['domain_domain_id']] = $item['domain_sitename'];
      }
    }
  }


  /**
   * Render sitename is as a link to the domain.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    // Determine the node path, if any.
    $nid = NULL;
    if (isset($values->{$this->aliases['nid']})) {
      $nid = $values->{$this->aliases['nid']};
    }
    else if (isset($values->nid)) {
      $nid = $values->nid;
    }
    elseif (isset($values->node_nid)) {
      $nid = $values->node_nid;
    }
    $node_path = '';
    if (!empty($nid)) {
      $node_path = drupal_get_path_alias('node/'. $nid);
    }
    // Determine the link path.
    if (!empty($this->options['link_to_default_domain']) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $domain = domain_default();
      $this->options['alter']['path'] = $domain['path'] . $node_path;
    }
    if (!empty($this->options['link_to_original_domain']) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $domain_id = $values->{$this->aliases['domain_id']};
      $domain = domain_lookup($domain_id);
      $this->options['alter']['path'] = $domain['path'] . $node_path;
    }
    return $data;
  }

  function render($values) {
    // Special case handling when returning multiple sitenames per record.
    // This only happens when list all domains is selected in options.

    // Determine the node path, if any.
    $nid = NULL;
    if (isset($values->{$this->aliases['nid']})) {
      $nid = $values->{$this->aliases['nid']};
    }
    else if (isset($values->nid)) {
      $nid = $values->nid;
    }
    elseif (isset($values->node_nid)) {
      $nid = $values->node_nid;
      $values->nid = $nid;
    }
    $node_path = '';
    if (!empty($nid)) {
      $node_path = drupal_get_path_alias('node/'. $nid);
    }
    $link_options = array('absolute' => TRUE);
    // Now write the links.
    if (is_array($this->field_values[$values->nid])) {
      $field = $this->content_field;
      $options = $this->options;
      $items = array();
      foreach ($this->field_values[$values->nid] as $domain_id => $item) {
        if (!empty($item)) {
          $link = $item;
          if (!empty($options['link_to_original_domain'])) {
            $domain = domain_lookup($domain_id);
            $link = l($item, $domain['path'] . $node_path, $link_options);
          }
          if (!empty($options['link_to_default_domain'])) {
            $domain = domain_default();
            $link = l($item, $domain['path'] . $node_path, $link_options);
          }
          $items[] = $link;
        }
      }
      if (count($items) > 1) {
        return theme('domain_views_view_multiple_field', $items, $field, $values);
      }
      else if (count($items) == 1) {
        return $items[0];
      }
      else {
        return '';
      }
    }
    else {
      return $this->render_link(check_plain($values->{$this->field_alias}), $values);
    }
  }
}

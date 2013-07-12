<?php

/**
 * @file
 * Definition of Drupal\domain_alias\DomainAliasFormController.
 */

namespace Drupal\domain_alias;

use Drupal\Core\Form\FormInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain_alias\DomainAliasInterface;
use Drupal\Core\Config\Entity\ConfigStorageController;

/**
 * Base form controller for domain alias edit forms.
 */
class DomainAliasFormController implements FormInterface {

  /**
   * Overrides Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, DomainInterface $domain = NULL) {
    $form['domain_help'] = array(
      '#type' => 'markup',
      '#markup' => domain_alias_help_text(),
    );
    $form['domain_id'] = array('#type' => 'value', '#value' => $domain->id());
    $form['domain'] = array(
      '#type' => 'item',
      '#title' => t('Registered aliases for <a href="!url"%title</a>', array('!url' => url('admin/structure/domain/' . $domain->id()), '%title' => $domain->hostname)),
    );
    if (empty($domain->aliases)) {
      $form['domain']['#markup'] = t('There are no current aliases for this domain.');
    }
    // List all existing aliases
    else {
      $header = array(
        array('data' => t('Redirect')),
        array('data' => t('Alias')),
        array('data' => t('Delete')),
      );
      $form['domain_alias'] = array(
        '#type' => 'table',
        '#header' => $header,
        '#tree' => TRUE,
      );
      foreach ($domain->aliases as $alias_id => $alias) {
        $form['domain_alias'][$alias_id]['redirect'] = array(
          '#type' => 'checkbox',
          '#default_value' => $alias->redirect,
        );
        $form['domain_alias'][$alias_id]['pattern'] = array(
          '#type' => 'textfield',
          '#default_value' => $alias->pattern,
          '#maxlength' => 255,
          '#width' => 40,
        );
        $form['domain_alias'][$alias_id]['delete'] = array(
          '#type' => 'checkbox',
          '#default_value' => FALSE,
        );
      }
    }
    $form['domain_new_help'] = array(
      '#type' => 'item',
      '#title' => t('Add new aliases'),
      '#markup' => t('To create a new alias, enter the matching pattern. Check the <em>redirect</em> box if you would like requests made to the alias to redirect to the registered domain.
        <em>You may enter up to five (5) aliases at a time.</em>'),
    );
    $header = array(
      array('data' => t('Redirect')),
      array('data' => t('Alias')),
    );
    $form['domain_alias_new'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#tree' => TRUE,
    );
    for ($i = 0; $i < 5; $i++) {
      $form['domain_alias_new'][$i]['redirect'] = array(
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      );
      $form['domain_alias_new'][$i]['pattern'] = array(
        '#type' => 'textfield',
        '#default_value' => NULL,
        '#maxlength' => 255,
        '#width' => 40,
      );
    }
    $form['submit'] = array('#type' => 'submit', '#value' => t('Save aliases'));
    return $form;

  }

  public function getFormID() {
    return 'domain_alias_form';
  }

  /**
   * Overrides Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    // @TODO validation
  }

  /**
   * Overrides Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $values = $form_state['values'];
    $domain = domain_load($values['domain_id']);
    $aliases = isset($values['domain_alias']) ? array_merge($values['domain_alias'], $values['domain_alias_new']) : $values['domain_alias_new'];
    foreach ($aliases as $id => $values) {
      if (empty($values['pattern'])) {
        continue;
      }
      // @TODO abstract this logic?
      $values['domain_machine_name'] = $domain->machine_name;
      $alias = entity_load('domain_alias', $id);
      if (empty($alias)) {
        $alias = entity_create('domain_alias', $values);
        $alias->createID();
      }
      if (!empty($values['delete'])) {
        $alias->delete();
      }
      else {
        $alias->save();
      }
    }
  }

}

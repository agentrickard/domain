<?php

namespace Drupal\domain\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block that links to all active domains.
 *
 * @Block(
 *   id = "domain_nav_block",
 *   admin_label = @Translation("Domain navigation")
 * )
 */
class DomainNavBlock extends DomainBlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $elements['link_options'] = [
      '#title' => $this->t('Link paths'),
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => ['active' => $this->t('Link to active url'), 'home' => $this->t('Link to site home page')],
      '#default_value' => !empty($this->configuration['link_options']) ? $this->configuration['link_options'] : 'active',
      '#description' => $this->t('Determines how links to each domain will be written. Note that some paths may not be accessible on all domains.'),
    ];
    $options = array(
      'default' => t('JavaScript select list'),
      'menus' => t('Menu-style tab links'),
      'ul' => t('Unordered list of links'),
    );
    $elements['link_theme'] = array(
      '#type' => 'radios',
      '#title' => t('Link theme'),
      '#default_value' => !empty($this->configuration['link_theme']) ? $this->configuration['link_theme'] : 'default',
      '#options' => $options,
      '#description' => $this->t('Select how to display the block output.'),
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Process the block's submission handling if no errors occurred only.
    if (!$form_state->getErrors()) {
      foreach (['link_options', 'link_theme'] as $element) {
        $this->configuration[$element] = $form_state->getValue($element);
      }
    }
  }

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = AccessResult::allowedIfHasPermissions($account, array('administer domains', 'use domain nav block'), 'OR');
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Build the output.
   */
  public function build() {
    /** @var \Drupal\domain\DomainInterface $active_domain */
    $active_domain = \Drupal::service('domain.negotiator')->getActiveDomain();
    $access_handler = \Drupal::service('entity_type.manager')->getAccessControlHandler('domain');
    $account = \Drupal::currentUser();
    $current_path = TRUE;
    $items = [];
    /** @var \Drupal\domain\DomainInterface $domain */
    foreach (\Drupal::service('entity_type.manager')->getStorage('domain')->loadMultipleSorted() as $domain) {
      $current_path = ($this->configuration['link_options'] == 'active');
      $string = $domain->getLink($current_path);
      if ($domain->access('view', $account)) {
        $items[] = array('#markup' => $string);
      }
    }
    return array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
  }

}

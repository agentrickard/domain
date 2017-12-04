<?php

namespace Drupal\domain\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

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
    $defaults = $this->defaultConfiguration();
    $elements['link_options'] = [
      '#title' => $this->t('Link paths'),
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => ['active' => $this->t('Link to active url'), 'home' => $this->t('Link to site home page')],
      '#default_value' => !empty($this->configuration['link_options']) ? $this->configuration['link_options'] : $defaults['link_options'],
      '#description' => $this->t('Determines how links to each domain will be written. Note that some paths may not be accessible on all domains.'),
    ];
    $options = array(
      'select' => t('JavaScript select list'),
      'menus' => t('Menu-style tab links'),
      'ul' => t('Unordered list of links'),
    );
    $elements['link_theme'] = array(
      '#type' => 'radios',
      '#title' => t('Link theme'),
      '#default_value' => !empty($this->configuration['link_theme']) ? $this->configuration['link_theme'] : $defaults['link_theme'],
      '#options' => $options,
      '#description' => $this->t('Select how to display the block output.'),
    );
    $options = array(
      'name' => t('The domain display name'),
      'hostname' => t('The raw hostname'),
      'url' => t('The domain base URL'),
    );
    $elements['link_label'] = array(
      '#type' => 'radios',
      '#title' => t('Link text'),
      '#default_value' => !empty($this->configuration['link_label']) ? $this->configuration['link_label'] : $defaults['link_label'],
      '#options' => $options,
      '#description' => $this->t('Select the text to display for each link.'),
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Process the block's submission handling if no errors occurred only.
    if (!$form_state->getErrors()) {
      foreach (array_keys($this->defaultConfiguration()) as $element) {
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

    // Get the settings.
    $settings = [];
    $defaults = $this->defaultConfiguration();
    foreach (array_keys($this->defaultConfiguration()) as $element) {
      if (isset($this->configuration[$element])) {
        $settings[$element] = $this->configuration[$element];
      }
      else {
        $settings[$element] = $defaults[$element];
      }
    }

    // Determine the visible domain list.
    $items = [];
    $add_path = ($settings['link_options'] == 'active');
    /** @var \Drupal\domain\DomainInterface $domain */
    foreach (\Drupal::service('entity_type.manager')->getStorage('domain')->loadMultipleSorted() as $domain) {
      // Set the URL.
      $options = ['absolute' => TRUE, 'https' => ($domain->getScheme() == 'https')];
      if ($add_path) {
        $url = Url::fromUri($domain->getUrl(), $options);
      }
      else {
        $url = Url::fromUri($domain->getPath(), $options);
      }
      // Set the label text.
      $label = $domain->label();
      if ($settings['link_label'] == 'hostname') {
        $label = $domain->getHostname();
      }
      elseif ($settings['link_label'] == 'url') {
        $label = $domain->getPath();
      }
      // Handles menu links.
      if ($domain->status() || $account->hasPermission('access inactive domains')) {
        if ($settings['link_theme'] == 'menus') {
          // @TODO: active trail isn't working properly, likely because this
          // isn't really a menu.
          $items[] = [
            'title' => $label,
            'url' => $url,
            'attributes' => $domain->isActive() ? ['classes' => ['menu-item--active-trail']] : [],
            'below' => [],
            'is_expanded' => FALSE,
            'is_collapsed' => FALSE,
            'in_active_trail' => FALSE,
          ];
        }
        else {
          $items[] = array('#markup' => Link::fromTextAndUrl($label, $url)->toString());
        }
      }
    }

    // Set the proper theme.
    switch ($settings['link_theme']) {
      case 'select':
        $theme = 'new_theme';
        break;
      case 'menus':
        $theme = 'menu';
        break;
      case 'ul':
      default:
        $theme = 'item_list';
        break;
    }

    return array(
      '#theme' => $theme,
      '#items' => $items,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'link_options' => 'home',
      'link_theme' => 'ul',
      'link_label' => 'name',
    ];
  }

}

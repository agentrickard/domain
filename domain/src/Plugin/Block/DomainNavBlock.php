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
   * An array of settings.
   *
   * @var string[]
   */
  public $settings = [];

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // By default, all domain blocks are per-url. This block, though, can be
    // cached by url.site if we are printing the homepage path, not the request.
    if ($this->getSetting('link_options') == 'home') {
      return ['url.site'];
    }
    return ['url'];
  }

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
    $options = [
      'select' => $this->t('JavaScript select list'),
      'menus' => $this->t('Menu-style tab links'),
      'ul' => $this->t('Unordered list of links'),
    ];
    $elements['link_theme'] = [
      '#type' => 'radios',
      '#title' => t('Link theme'),
      '#default_value' => !empty($this->configuration['link_theme']) ? $this->configuration['link_theme'] : $defaults['link_theme'],
      '#options' => $options,
      '#description' => $this->t('Select how to display the block output.'),
    ];
    $options = [
      'name' => $this->t('The domain display name'),
      'hostname' => $this->t('The raw hostname'),
      'url' => $this->t('The domain base URL'),
    ];
    $elements['link_label'] = [
      '#type' => 'radios',
      '#title' => $this->t('Link text'),
      '#default_value' => !empty($this->configuration['link_label']) ? $this->configuration['link_label'] : $defaults['link_label'],
      '#options' => $options,
      '#description' => $this->t('Select the text to display for each link.'),
    ];
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
    $access = AccessResult::allowedIfHasPermissions($account, ['administer domains', 'use domain nav block'], 'OR');
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Build the output.
   */
  public function build() {
    /** @var \Drupal\domain\DomainInterface $active_domain */
    $active_domain = \Drupal::service('domain.negotiator')->getActiveDomain();
    $access_handler = \Drupal::entityTypeManager()->getAccessControlHandler('domain');
    $account = \Drupal::currentUser();

    // Determine the visible domain list.
    $items = [];
    $add_path = ($this->getSetting('link_options') == 'active');
    /** @var \Drupal\domain\DomainInterface $domain */
    foreach (\Drupal::entityTypeManager()->getStorage('domain')->loadMultipleSorted() as $domain) {
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
      if ($this->getSetting('link_label') == 'hostname') {
        $label = $domain->getHostname();
      }
      elseif ($this->getSetting('link_label') == 'url') {
        $label = $domain->getPath();
      }
      // Handles menu links.
      if ($domain->status() || $account->hasPermission('access inactive domains')) {
        if ($this->getSetting('link_theme') == 'menus') {
          // @TODO: active trail isn't working properly, likely because this
          // isn't really a menu.
          $items[] = [
            'title' => $label,
            'url' => $url,
            'attributes' => [],
            'below' => [],
            'is_expanded' => FALSE,
            'is_collapsed' => FALSE,
            'in_active_trail' => $domain->isActive(),
          ];
        }
        elseif ($this->getSetting('link_theme') == 'select') {
          $items[] = [
            'label' => $label,
            'url' => $url->toString(),
            'active' => $domain->isActive(),
          ];
        }
        else {
          $items[] = ['#markup' => Link::fromTextAndUrl($label, $url)->toString()];
        }
      }
    }

    // Set the proper theme.
    switch ($this->getSetting('link_theme')) {
      case 'select':
        $build['#theme'] = 'domain_nav_block';
        $build['#items'] = $items;
        break;

      case 'menus':
        // Map the $items params to what menu.html.twig expects.
        $build['#items'] = $items;
        $build['#menu_name'] = 'domain-nav';
        $build['#sorted'] = TRUE;
        $build['#theme'] = 'menu__' . strtr($build['#menu_name'], '-', '_');
        break;

      case 'ul':
      default:
        $build['#theme'] = 'item_list';
        $build['#items'] = $items;
        break;
    }

    return $build;
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

  /**
   * Gets the configuration for the block, loading defaults if not set.
   *
   * @param string $key
   *   The setting key to retrieve, a string.
   *
   * @return string
   *   The setting value, a string.
   */
  public function getSetting($key) {
    if (isset($this->settings[$key])) {
      return $this->settings[$key];
    }
    $defaults = $this->defaultConfiguration();
    if (isset($this->configuration[$key])) {
      $this->settings[$key] = $this->configuration[$key];
    }
    else {
      $this->settings[$key] = $defaults[$key];
    }
    return $this->settings[$key];
  }

}

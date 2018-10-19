<?php

namespace Drupal\domain_config_ui\Controller;

use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\DomainInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for AJAX callbacks for domain actions.
 */
class DomainConfigUIController {

  use StringTranslationTrait;

  /**
   * Handles AJAX operations to add/remove configuration forms.
   *
   * @param $route_name
   *   A domain record object.
   * @param string $op
   *   The operation being performed, either 'enable' to enable the form,
   *   'disable' to disable the domain form, or 'remove' to disable the form
   *   and remove its stored configurations.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to redirect back to the calling form.
   *   Supported by the UrlGeneratorTrait.
   */
  public function ajaxOperation($route_name, $op) {
    $success = FALSE;
    $url = Url::fromRoute($route_name);
    // Get current module settings.
    $config = \Drupal::configFactory()->getEditable('domain_config_ui.settings');
    $path_pages = $config->get('path_pages');

    if (!$url->isExternal() && $url->access()) {
      switch ($op) {
        case 'enable':
          // Check to see if we already registered this form.
          if (!$exists = \Drupal::service('path.matcher')->matchPath($url->toString(), $path_pages)) {
            // TODO: reverse logic if negate is turned on.
            $config->set('path_pages', $path_pages . $url->toString() . "\r\n")
              ->save();
            $message = $this->t('Form added to domain configuration interface.');
            $success = TRUE;
          }
          break;
        case 'disable':
          if ($exists = \Drupal::service('path.matcher')->matchPath($url->toString(), $path_pages)) {
            $new_pages = str_replace($url->toString(), '', $path_pages);
            $config->set('path_pages', $new_pages)
              ->save();
            $message = $this->t('Form removed domain configuration interface.');
            $success = TRUE;
            // @TODO: Remove existing cofiguration as a separate action.
          }
          break;
      }

    }
    // Set a message.
    if ($success) {
      \Drupal::messenger()->addMessage($message);
    }
    else {
      \Drupal::messenger()->addMessage($this->t('The operation failed.'));
    }
    // Return to the invoking page.
    return new RedirectResponse($url->toString(), 302);
  }

  /**
   * Lists all stored configuration.
   */
  public function overview() {
    $page['table'] = array(
      '#type' => 'table',
      '#header' => [
        'name' => t('Configuration key'),
        'item' => t('Item'),
        'domain' => t('Domain'),
        'language' => t('Language'),
        'actions' => t('Actions'),
      ],
    );
    // @TODO: inject services.
    $storage = \Drupal::service('config.storage');
    foreach ($storage->listAll('domain.config') as $name) {
      $elements[] = $this->deriveElements($name);
    }
    // Sort the items.
    uasort($elements, [$this, 'sortItems']);

    foreach ($elements as $element) {
      $page['table'][] = [
        'name' => ['#markup' => $element['name']],
        'item' => ['#markup' => $element['item']],
        'domain' => ['#markup' => $element['domain']],
        'language' => ['#markup' => $element['language']],
        'actions' => ['#type' => 'link',
          '#url' => Url::fromRoute('domain_config_ui.delete', ['config_name' => $element['name']]),
          '#title' => $this->t('Delete'),
        ],
      ];
    }
    return $page;
  }

  /**
   * Derives the parts of a config object for presentation.
   *
   * @param $name
   *   A configuration object name.
   *
   * @return array
   */
  public static function deriveElements($name) {
    $entity_manager = \Drupal::entityTypeManager();
    $items = explode('.', $name);
    $elements = [
      'prefix' => $items[0],
      'config' => isset($items[1]) && isset($items[2]) ? $items[1] : '',
      'domain' => isset($items[2]) && isset($items[3]) ? $items[2] : '',
      'language' => isset($items[3]) && isset($items[4]) && strlen($items[3]) == 2 ? $items[3] : '',
    ];

    $elements['item'] = trim(str_replace($elements, '', $name), '.');

    if (!empty($elements['domain']) && $domain = $entity_manager->getStorage('domain')->load($elements['domain'])) {
      $elements['domain'] = $domain->label();
    }

    if (!$elements['language']) {
      $elements['language'] = $this->t('all');
    }
    elseif ($language = \Drupal::languageManager()->getLanguage($elements['language'])) {
      $elements['language'] = $language->getName();
    }

    $elements['name'] = $name;

    return $elements;
  }

  /**
   * Sorts items by parent config.
   */
  public function sortItems($a, $b) {
    return $a['item'] > $b['item'];
  }

}

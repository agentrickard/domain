<?php

namespace Drupal\domain_config_ui\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain_config_ui\DomainConfigUITrait;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for AJAX callbacks for domain actions.
 */
class DomainConfigUIController {

  use DomainConfigUITrait;
  use StringTranslationTrait;

  /**
   * Handles AJAX operations to add/remove configuration forms.
   *
   * @param string $route_name
   *   The route from which the AJAX request was triggered.
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
    // Get the query string for the return URL.
    $query = \Drupal::requestStack()->getCurrentRequest()->getQueryString();
    $params = [];
    $parts = explode('&', $query);
    foreach ($parts as $part) {
      $element = explode('=', $part);
      if ($element[0] !== 'token') {
        $params[$element[0]] = $element[1];
      }
    }
    $url = Url::fromRoute($route_name, $params);

    // Get current module settings.
    $config = \Drupal::configFactory()->getEditable('domain_config_ui.settings');
    $path_pages = $this->standardizePaths($config->get('path_pages'));
    $new_path = '/' . $url->getInternalPath();

    if (!$url->isExternal() && $url->access()) {
      switch ($op) {
        case 'enable':
          // Check to see if we already registered this form.
          if (!$exists = \Drupal::service('path.matcher')->matchPath($new_path, $path_pages)) {
            $this->addPath($new_path);
            $message = $this->t('Form added to domain configuration interface.');
            $success = TRUE;
          }
          break;

        case 'disable':
          if ($exists = \Drupal::service('path.matcher')->matchPath($new_path, $path_pages)) {
            $this->removePath($new_path);
            $message = $this->t('Form removed from domain configuration interface.');
            $success = TRUE;
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
    $elements = [];
    $page['table'] = [
      '#type' => 'table',
      '#header' => [
        'name' => t('Configuration key'),
        'item' => t('Item'),
        'domain' => t('Domain'),
        'language' => t('Language'),
        'actions' => t('Actions'),
      ],
    ];
    // @TODO: inject services.
    $storage = \Drupal::service('config.storage');
    foreach ($storage->listAll('domain.config') as $name) {
      $elements[] = $this->deriveElements($name);
    }
    // Sort the items.
    if (!empty($elements)) {
      uasort($elements, [$this, 'sortItems']);
      foreach ($elements as $element) {
        $operations = [
          'inspect' => [
            'url' => Url::fromRoute('domain_config_ui.inspect', ['config_name' => $element['name']]),
            'title' => $this->t('Inspect'),
          ],
          'delete' => [
            'url' => Url::fromRoute('domain_config_ui.delete', ['config_name' => $element['name']]),
            'title' => $this->t('Delete'),
          ],
        ];
        $page['table'][] = [
          'name' => ['#markup' => $element['name']],
          'item' => ['#markup' => $element['item']],
          'domain' => ['#markup' => $element['domain']],
          'language' => ['#markup' => $element['language']],
          'actions' => ['#type' => 'operations', '#links' => $operations],
        ];
      }
    }
    else {
      $page = [
        '#markup' => $this->t('No domain-specific configurations have been found.'),
      ];
    }
    return $page;
  }

  /**
   * Controller for inspecting configuration.
   *
   * @param string $config_name
   *   The domain config object being inspected.
   */
  public function inspectConfig($config_name = NULL) {
    if (empty($config_name)) {
      $url = Url::fromRoute('domain_config_ui.list');
      return new RedirectResponse($url->toString());
    }
    $elements = $this->deriveElements($config_name);
    $config = \Drupal::configFactory()->get($config_name)->getRawData();
    if ($elements['language'] == $this->t('all')->render()) {
      $language = $this->t('all languages');
    }
    else {
      $language = $this->t('the @language language.', ['@language' => $elements['language']]);
    }
    $page['help'] = [
      '#type' => 'item',
      '#title' => Html::escape($config_name),
      '#markup' => $this->t('This configuration is for the %domain domain and
        applies to %language.', [
          '%domain' => $elements['domain'],
          '%language' => $language,
        ]
      ),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $page['text'] = [
      '#markup' => $this->printArray($config),
    ];
    return $page;
  }

  /**
   * Derives the parts of a config object for presentation.
   *
   * @param string $name
   *   A configuration object name.
   *
   * @return array
   *   An array of config values, keyed by name.
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
      // Static context requires use of t() here.
      $elements['language'] = t('all')->render();
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

  /**
   * Prints array data for the form.
   *
   * @param array $array
   *   An array of data. Note that we support two levels of nesting.
   *
   * @return string
   *   A suitable output string.
   */
  public static function printArray(array $array) {
    $items = [];
    foreach ($array as $key => $val) {
      if (!is_array($val)) {
        $value = self::formatValue($val);
        $item = [
          '#theme' => 'item_list',
          '#items' => [$value],
          '#title' => self::formatValue($key),
        ];
        $items[] = render($item);
      }
      else {
        $list = [];
        foreach ($val as $k => $v) {
          $list[] = t('<strong>@key</strong> : @value', ['@key' => $k, '@value' => self::formatValue($v)]);
        }
        $variables = [
          '#theme' => 'item_list',
          '#items' => $list,
          '#title' => self::formatValue($key),
        ];
        $items[] = render($variables);
      }
    }
    $rendered = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return render($rendered);
  }

  /**
   * Formats a value as a string, for readable output.
   *
   * Taken from config_inspector module.
   *
   * @param mixed $value
   *   The value element.
   *
   * @return string
   *   The value in string form.
   */
  protected static function formatValue($value) {
    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }
    if (is_scalar($value)) {
      return Html::escape($value);
    }
    if (empty($value)) {
      return '<' . t('empty') . '>';
    }
    return '<' . gettype($value) . '>';
  }

}

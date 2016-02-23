<?php

/**
 * @file
 * Contains \Drupal\domain_config\DomainConfigMapperManager.
 */

namespace Drupal\domain_config;

use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\InfoHookDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Manages plugins for domain configuration mappers.
 */
class DomainConfigMapperManager extends DefaultPluginManager implements DomainConfigMapperManagerInterface {

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    'title' => '',
    'names' => array(),
    'weight' => 20,
    'class' => '\Drupal\domain_config\DomainConfigNamesMapper',
  );

  /**
   * Constructs a DomainConfigMapperManager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(CacheBackendInterface $cache_backend, DomainNegotiatorInterface $domain_negotiator, ModuleHandlerInterface $module_handler, TypedConfigManagerInterface $typed_config_manager, ThemeHandlerInterface $theme_handler) {
    $this->typedConfigManager = $typed_config_manager;

    $this->factory = new ContainerFactory($this, '\Drupal\domain_config\DomainConfigMapperInterface');
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;

    /*
    // Let others alter definitions with hook_domain_config_info_alter().
    $this->alterInfo('domain_config_info');
    // Config translation only uses an info hook discovery, cache by url.
    $cache_key = 'domain_config_info_plugins' . ':' . $domain_negotiator->getActiveDomain()->id();
    $this->setCacheBackend($cache_backend, $cache_key, array('domain_config_info_plugins'));
    */
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      // Look at all themes and modules.
      // @todo If the list of installed modules and themes is changed, new
      //   definitions are not picked up immediately and obsolete definitions
      //   are not removed, because the list of search directories is only
      //   compiled once in this constructor. The current code only works due to
      //   coincidence: The request that installs (for instance, a new theme)
      //   does not instantiate this plugin manager at the beginning of the
      //   request; when routes are being rebuilt at the end of the request,
      //   this service only happens to get instantiated with the updated list
      //   of installed themes.
      $directories = array();
      foreach ($this->moduleHandler->getModuleList() as $name => $module) {
        $directories[$name] = $module->getPath() . '/config/schema';
      }
      foreach ($this->themeHandler->listInfo() as $theme) {
        $directories[$theme->getName()] = $theme->getPath() . '/config/schema';
      }

      // Check for files named MODULE.schema.yml and
      // THEME.schema.yml in module/theme config/schema.
      // @TODO: This YAML discovery is not sufficient for our needs.
      $this->discovery = new YamlDiscovery('schema', $directories);
      $this->discovery = new InfoHookDecorator($this->discovery, 'domain_config_info');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }

    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappers(RouteCollection $collection = NULL) {
    $mappers = array();
    foreach($this->getDefinitions() as $id => $definition) {
      $mappers[$id] = $this->createInstance($id);
      if ($collection) {
        $mappers[$id]->setRouteCollection($collection);
      }
    }

    return $mappers;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    if (!isset($definition['base_route_name'])) {
      throw new InvalidPluginDefinitionException($plugin_id, "The plugin definition of the mapper '$plugin_id' does not contain a base_route_name.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildDataDefinition(array $definition, $value = NULL, $name = NULL, $parent = NULL) {
    return $this->typedConfigManager->buildDataDefinition($definition, $value, $name, $parent);
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();
    foreach ($definitions as $plugin_id => &$definition) {
      // We do not allow configuring domains by domain.
      if ($definition['provider'] == 'domain') {
        unset($definitions[$plugin_id]);
        continue;
      }
      // We support only certain types.
      if (in_array($definition['type'], $this->allowedTypes())) {
        $definition = $this->deriveRoute($definition);
      }
      if (isset($definition['base_route_name'])) {
        $this->processDefinition($definition, $plugin_id);
      }
      else {
        unset($definitions[$plugin_id]);
      }
    }

    if ($this->alterHook) {
      $this->moduleHandler->alter($this->alterHook, $definitions);
    }

    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    foreach ($definitions as $plugin_id => $plugin_definition) {
      if (isset($plugin_definition['provider']) && !in_array($plugin_definition['provider'], array('core', 'component')) && (!$this->moduleHandler->moduleExists($plugin_definition['provider']) && !in_array($plugin_definition['provider'], array_keys($this->themeHandler->listInfo())))) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  private function deriveRoute($definition) {
    if (isset($definition['provider']) && $module = $this->moduleHandler->getModule($definition['provider'])) {
      $directory[$definition['provider']] = $module->getPath();
      $discovery = new YamlDiscovery('routing', $directory);
      // @TODO: Proof-of-concept, we need all routes for the module.
      // @TODO: Name matching is unreliable. Perhaps we can derive from controllers?
      foreach ($discovery->getDefinitions() as $route => $data) {
        // Assume that a _form property indicates a config_object.
        // Do not allow arguments in the form.
        // Ensure that we are not loading 'theme' settings.
        // Must match on title.
        // This doesn't work, because of mismatched definitions.
        // We likely need our own registry.
        if ($data['defaults']['_title'] == $definition['label'] && isset($data['defaults']['_form']) && substr_count($data['path'], '{') < 1 && substr_count($data['id'], 'theme') < 1) {
          $definition['base_route_name'] = $route;
          $definition['title'] = $definition['label'];
        }
      }
    }
    return $definition;
  }

  public function allowedTypes() {
    $types = [
      'config_object',
      'config_entity',
      #'theme_settings',
    ];
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslatable($name) {
    return $this->findTranslatable($this->typedConfigManager->get($name));
  }

  /**
   * Returns TRUE if at least one translatable element is found.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $element
   *   Configuration schema element.
   *
   * @return bool
   *   A boolean indicating if there is at least one translatable element.
   */
  protected function findTranslatable(TypedDataInterface $element) {
    // In case this is a sequence or a mapping check whether any child element
    // is translatable.
    if ($element instanceof TraversableTypedDataInterface) {
      foreach ($element as $child_element) {
        if ($this->findTranslatable($child_element)) {
          return TRUE;
        }
      }
      // If none of the child elements are translatable, return FALSE.
      return FALSE;
    }
    else {
      $definition = $element->getDataDefinition();
      return isset($definition['translatable']) && $definition['translatable'];
    }
  }

}

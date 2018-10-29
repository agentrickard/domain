<?php

namespace Drupal\domain_access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Checks the access status of entities based on domain settings.
 *
 * @TODO: It is possible that this class may become a subclass of the
 * DomainElementManager, however, the use-case is separate as far as I can tell.
 */
class DomainAccessManager implements DomainAccessManagerInterface {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The Drupal module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The domain storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * Constructs a DomainAccessManager object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Drupal module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->negotiator = $negotiator;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->domainStorage = $entity_type_manager->getStorage('domain');
  }

  /**
   * {@inheritdoc}
   */
  public static function getAccessValues(FieldableEntityInterface $entity, $field_name = DOMAIN_ACCESS_FIELD) {
    // @TODO: static cache.
    $list = [];
    // @TODO In tests, $entity is returning NULL.
    if (is_null($entity)) {
      return $list;
    }
    // Get the values of an entity.
    $values = $entity->hasField($field_name) ? $entity->get($field_name) : NULL;
    // Must be at least one item.
    if (!empty($values)) {
      foreach ($values as $item) {
        if ($target = $item->getValue()) {
          if ($domain = \Drupal::entityTypeManager()->getStorage('domain')->load($target['target_id'])) {
            $list[$domain->id()] = $domain->getDomainId();
          }
        }
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public static function getAllValue(FieldableEntityInterface $entity) {
    return $entity->hasField(DOMAIN_ACCESS_ALL_FIELD) ? $entity->get(DOMAIN_ACCESS_ALL_FIELD)->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function checkEntityAccess(FieldableEntityInterface $entity, AccountInterface $account) {
    $entity_domains = $this->getAccessValues($entity);
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($account->id());
    if (!empty($this->getAllValue($user)) && !empty($entity_domains)) {
      return TRUE;
    }
    $user_domains = $this->getAccessValues($user);
    return (bool) !empty(array_intersect($entity_domains, $user_domains));
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $item = [];
    if (!$entity->isNew()) {
      // If set, ensure we do not drop existing data.
      foreach (self::getAccessValues($entity) as $id) {
        $item[] = $id;
      }
    }
    // When creating a new entity, populate if required.
    elseif ($entity->getFieldDefinition(DOMAIN_ACCESS_FIELD)->isRequired()) {
      /** @var \Drupal\domain\DomainInterface $active */
      if ($active = \Drupal::service('domain.negotiator')->getActiveDomain()) {
        $item[0]['target_uuid'] = $active->uuid();
      }
    }
    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDomainPermissions(AccountInterface $account, DomainInterface $domain, array $permissions, $conjunction = 'AND') {
    // Assume no access.
    $access = FALSE;

    // In the case of multiple AND permissions, assume access and then deny if
    // any check fails.
    if ($conjunction == 'AND' && !empty($permissions)) {
      $access = TRUE;
      foreach ($permissions as $permission) {
        if (!($permission_access = $account->hasPermission($permission))) {
          $access = FALSE;
          break;
        }
      }
    }
    // In the case of multiple OR permissions, assume deny and then allow if any
    // check passes.
    else {
      foreach ($permissions as $permission) {
        if ($permission_access = $account->hasPermission($permission)) {
          $access = TRUE;
          break;
        }
      }
    }
    // Validate that the user is assigned to the domain. If not, deny.
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($account->id());
    $allowed = $this->getAccessValues($user);
    if (!isset($allowed[$domain->id()]) && empty($this->getAllValue($user))) {
      $access = FALSE;
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentUrls(FieldableEntityInterface $entity) {
    $list = [];
    $processed = FALSE;
    $domains = $this->getAccessValues($entity);
    if ($this->moduleHandler->moduleExists('domain_source')) {
      $source = domain_source_get($entity);
      if (isset($domains[$source])) {
        unset($domains['source']);
      }
      if (!empty($source)) {
        $list[] = $source;
      }
      $processed = TRUE;
    }
    $list = array_merge($list, array_keys($domains));
    $domains = $this->domainStorage->loadMultiple($list);
    $urls = [];
    foreach ($domains as $domain) {
      $options['domain_target_id'] = $domain->id();
      $url = $entity->toUrl('canonical', $options);
      if ($processed) {
        $urls[$domain->id()] = $url->toString();
      }
      else {
        $urls[$domain->id()] = trim($domain->getPath(), '/') . $url->toString();
      }
    }
    return $urls;
  }

}

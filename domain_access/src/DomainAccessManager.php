<?php

namespace Drupal\domain_access;

use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks the access status of entities based on domain settings.
 *
 * @TODO: It is possible that this class may become a subclass of the
 * DomainElementManager, however, the use-case is separate as far as I can tell.
 */
class DomainAccessManager implements DomainAccessManagerInterface {

  /**
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a DomainAccessManager object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainNegotiatorInterface $negotiator) {
    $this->negotiator = $negotiator;
  }

  /**
   * @inheritdoc
   */
  public static function getAccessValues(EntityInterface $entity, $field_name = DOMAIN_ACCESS_FIELD) {
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
   * @inheritdoc
   */
  public function getAllValue(EntityInterface $entity) {
    return $entity->hasField(DOMAIN_ACCESS_ALL_FIELD) ? $entity->get(DOMAIN_ACCESS_ALL_FIELD)->value : NULL;
  }

  /**
   * @inheritdoc
   */
  public function checkEntityAccess(EntityInterface $entity, AccountInterface $account) {
    $entity_domains = $this->getAccessValues($entity);
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($account->id());
    if (!empty($this->getAllValue($user)) && !empty($entity_domains)) {
      return TRUE;
    }
    $user_domains = $this->getAccessValues($user);
    return (bool) !empty(array_intersect($entity_domains, $user_domains));
  }

  /**
   * @inheritdoc
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

}

<?php

namespace Drupal\domain_alias\Entity;

use Drupal\domain_alias\DomainAliasInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the domain alias entity.
 *
 * @ConfigEntityType(
 *   id = "domain_alias",
 *   label = @Translation("Domain alias"),
 *   module = "domain_alias",
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "access" = "Drupal\domain_alias\DomainAliasAccessControlHandler",
 *     "list_builder" = "Drupal\domain_alias\DomainAliasListBuilder",
 *     "form" = {
 *       "default" = "Drupal\domain_alias\DomainAliasForm",
 *       "edit" = "Drupal\domain_alias\DomainAliasForm",
 *       "delete" = "Drupal\domain_alias\Form\DomainAliasDeleteForm"
 *     }
 *   },
 *   config_prefix = "alias",
 *   admin_permission = "administer domain aliases",
 *   entity_keys = {
 *     "id" = "id",
 *     "domain_id" = "domain_id",
 *     "label" = "pattern",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/domain/alias/delete/{domain_alias}",
 *     "edit-form" = "/admin/config/domain/alias/edit/{domain_alias}",
 *   },
 *   config_export = {
 *     "id",
 *     "domain_id",
 *     "pattern",
 *     "redirect",
 *   }
 * )
 */
class DomainAlias extends ConfigEntityBase implements DomainAliasInterface {

  /**
   * The ID of the domain alias entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The parent domain record ID.
   *
   * @var string
   */
  protected $domain_id;

  /**
   * The domain alias record UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The domain alias record pattern.
   *
   * @var string
   */
  protected $pattern;

  /**
   * The domain alias record redirect value.
   *
   * @var integer
   */
  protected $redirect;

  /**
   * {@inheritdoc}
   */
  public function getPattern() {
    return $this->pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomainId() {
    return $this->domain_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirect() {
    return $this->redirect;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    // Invalidate cache tags relevant to domains.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['rendered', 'url.site']);
  }

}

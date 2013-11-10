<?php

/**
 * @file
 * Definition of Drupal\domain_alias\Entity\DomainAlias.
 */

namespace Drupal\domain_alias\Entity;

use Drupal\domain_alias\DomainAliasInterface;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines the domain alias entity.
 *
 * @EntityType(
 *   id = "domain_alias",
 *   label = @Translation("Domain alias"),
 *   module = "domain_alias",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "access" = "Drupal\domain\DomainAccessController",
 *     "list" = "Drupal\domain_alias\DomainAliasListController",
 *     "form" = {
 *       "default" = "Drupal\domain_alias\DomainAliasFormController",
 *       "edit" = "Drupal\domain_alias\DomainAliasFormController",
 *       "delete" = "Drupal\domain_alias\DomainAliasFormController"
 *     }
 *   },
 *   config_prefix = "domain_alias.alias",
 *   admin_permission = "administer domains",
 *   entity_keys = {
 *     "id" = "id",
 *     "domain_id" = "domain_id",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class DomainAlias extends ConfigEntityBase implements DomainAliasInterface {

  /**
   * The ID of the domain alias entity.
   *
   * @var string
   */
  public $id;

  /**
   * The parent domain record ID.
   *
   * @var string
   */
  public $domain_id;

  /**
   * The domain alias record UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The domain alias record pattern.
   *
   * @var string
   */
  public $pattern;

  /**
   * The domain alias record redirect value.
   *
   * @var integer
   */
  public $redirect;

  /**
   * Valiadates an alias.
   */
  public function validate() {
    $pattern = $this->pattern;
  }

}

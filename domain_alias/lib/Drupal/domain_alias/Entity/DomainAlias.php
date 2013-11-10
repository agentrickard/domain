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
 *     "label" = "pattern",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "admin/structure/domain/alias/edit/{domain_alias}",
 *     "delete-form" = "admin/structure/domain/alias/edit/{domain_alias}"
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
   * Validates an alias.
   */
  public function validate() {
    $pattern = $this->pattern;

    // 1) Check that the alias only has one wildcard.
    $count = substr_count($pattern, '*') + substr_count($pattern, '?');
    if ($count > 1) {
      return t('You may only have one wildcard character in each alias.');
    }
    // 2) Only one colon allowed, and it must be followed by an integer.
    $count = substr_count($pattern, ':');
    if ($count > 1) {
      return t('You may only have one colon ":" character in each alias.');
    }
    elseif ($count == 1) {
      $int = substr($pattern, strpos($pattern, ':') + 1);
      if (!is_numeric($int)) {
        return t('A colon may only be followed by an integer indicating the proper port.');
      }
    }
    // 3) Check that the alias doesn't contain any invalid characters.
    $check = preg_match('/^[a-z0-9\.\+\-\*\?:]*$/', $pattern);
    if ($check == 0) {
      return t('The pattern contains invalid characters.');
    }
    // 4) Check that the alias is not a direct match for a registered domain.
    $check = preg_match('/[a-z0-9\.\+\-:]*$/', $pattern);
    if ($check == 1 && $test = domain_load_hostname($pattern)) {
      return t('The pattern matches an existing domain record.');
    }
    // 5) Check that the alias is unique across all records.
    if ($alias = domain_alias_pattern_load($pattern)) {
      if ($alias->id() != $this->id()) {
        return t('The pattern already exists.');
      }
    }
  }

}

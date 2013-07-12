<?php

/**
 * @file
 * Contains \Drupal\domain_alias\Plugin\Core\Entity\DomainAlias.
 */

namespace Drupal\domain_alias\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\domain\DomainInterface;
use Drupal\domain_alias\DomainAliasInterface;
use Drupal\Core\Config\Entity\ConfigStorageController;

/**
 * Defines a Domain alias configuration entity class.
 *
 * @EntityType(
 *   id = "domain_alias",
 *   label = @Translation("Domain alias"),
 *   module = "domain_alias",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *   },
 *   config_prefix = "domain.alias",
 *   entity_keys = {
 *     "id" = "id",
 *     "domain_machine_name" = "domain_machine_name",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class DomainAlias extends ConfigEntityBase implements DomainAliasInterface {

  /**
   * The alias id.
   *
   * @var string
   */
  public $id;

  /**
   * The alias UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The parent domain machine name.
   *
   * @var string
   */
  public $domain_machine_name;

  /**
   * The alias pattern.
   *
   * @var string
   */
  public $pattern;

  /**
   * The alias redirect status.
   *
   * @var boolean
   */
  public $redirect;

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->id;
  }

  public function createID() {
    // Be careful with wildcards when writing config files.
    $search = array('*', '?', '.');
    $replace = array('+', '-', '_');
    $this->id = str_replace($search, $replace, $this->pattern);
  }

}

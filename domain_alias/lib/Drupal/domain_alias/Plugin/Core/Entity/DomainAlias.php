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
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines a Domain alias configuration entity class.
 *
 * @EntityType(
 *   id = "domain_alias",
 *   label = @Translation("Domain alias"),
 *   module = "domain_alias",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "form" = {
 *       "default" = "Drupal\domain_alias\DomainAliasFormController",
 *     }
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
   * The alias ID.
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

}

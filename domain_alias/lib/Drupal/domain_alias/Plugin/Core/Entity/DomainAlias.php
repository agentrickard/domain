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
 *     "access" = "Drupal\Core\Entity\EntityAccessController",
 *     "render" = "Drupal\Core\Entity\EntityRenderController",
 *     "form" = {
 *       "default" = "Drupal\domain_alias\DomainAliasFormController",
 *       "delete" = "Drupal\domain_alias\Form\DomainAliasDeleteForm"
 *     }
 *   },
 *   config_prefix = "domain.alias",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "pattern" = "pattern"
 *   }
 * )
 */
class DomainAlias extends ConfigEntityBase implements DomainAliasInterface {

}

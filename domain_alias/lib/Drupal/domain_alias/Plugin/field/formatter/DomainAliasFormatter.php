<?php

/**
 * @file
 * Contains \Drupal\domain_alias\Plugin\field\formatter\DomainAliasFormatter.
 */

namespace Drupal\domain_alias\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'domain_alias' formatter.
 *
 * @FieldFormatter(
 *   id = "domain_alias",
 *   module = "domain_alias",
 *   label = @Translation("Domain alias"),
 *   field_types = {
 *     "domain_alias"
 *   }
 * )
 */
class DomainAliasFormatter extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();
    return $elements;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\domain_alias\Plugin\field\formatter\DomainAliasFormatter.
 */

namespace Drupal\domain_alias\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;

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

}

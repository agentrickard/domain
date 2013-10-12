<?php

/**
 * @file
 * Definition of Drupal\domain\Plugin\field\formatter\DomainFormatter.
 */

namespace Drupal\domain\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'domain' formatter.
 *
 * @Plugin(
 *   id = "domain",
 *   module = "domain",
 *   label = @Translation("Domain reference"),
 *   field_types = {
 *     "domain",
 *   }
 * )
 */
class DomainFormatter extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();
    $data = domain_extract_field_items($items);
    $list = array();
    foreach ($data as $machine_name => $domain) {
      if ($machine_name == DOMAIN_ALL_AFFILIATES) {
        $list[] = t('All affiliates');
      }
      else {
        $list[] = check_plain($domain->name->value);
      }
    }
    if (!empty($list)) {
      $elements[] = array('#markup' => theme('item_list', array('items' => $list)));
      return $elements;
    }
  }

}

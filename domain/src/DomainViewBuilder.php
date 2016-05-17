<?php

namespace Drupal\domain;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for domain records.
 */
class DomainViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   *
   * @TODO EntityViewBuilder does not have a buildContent() method. viewMultiple() is the likely candidate.
   * @TODO domain_field_extra_fields() is not defined anywhere.
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    // If we can get domain_field_extra_fields() working here, we may not even
    // need this override class and can do everything via formatters.
    parent::buildContent($entities, $displays, $view_mode, $langcode);
    $fields = domain_field_extra_fields();
    $list = array_keys($fields['domain']['domain']['display']);

    foreach ($entities as $entity) {
      // Add the fields.
      // @TODO: get field sort order.
      $display = $displays[$entity->bundle()];
      foreach ($list as $key) {
        if (!empty($entity->{$key}) && $display->getComponent($key)) {
          $class = str_replace('_', '-', $key);
          $entity->content[$key] = array(
            '#markup' => Html::escape($entity->{$key}),
            '#prefix' => '<div class="domain-' . $class . '">' . '<strong>' . Html::escape($key) . ':</strong><br />',
            '#suffix' => '</div>',
          );
        }
      }
    }
  }

}

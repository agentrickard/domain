<?php
/**
 * @file
 * Contains \Drupal\domain_content\Controller\DomainContentController.
 */

namespace Drupal\domain_content\Controller;

use Drupal\domain\DomainInterface;
use Drupal\domain\Controller\DomainControllerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Controller routines domain content pages.
 */
class DomainContentController extends DomainControllerBase {

  use StringTranslationTrait;

  /**
   * Generates a list of content by domain.
   */
  public function contentList() {
    $build = [
      '#theme' => 'table',
      '#header' => [$this->t('Domain'), $this->t('Content count')],
    ];
    $build['#rows'][] = [$this->l($this->t('All affiliates'), Url::fromUri('internal:/admin/content/domain-content/all_affiliates')), $this->getCount('node')];
    // @TODO: Inject this service.
    $domains = \Drupal::service('domain.loader')->loadMultipleSorted();
    foreach ($domains as $domain) {
      $row = [$this->l($domain->label(), Url::fromUri('internal:/admin/content/domain-content/' . $domain->id())), $this->getCount('node', $domain)];
      $build['#rows'][] = $row;
    }
    return $build;
  }

  /**
   * Generates a list of editors by domain.
   */
  public function editorsList() {
    $build = [
      '#theme' => 'table',
      '#header' => [$this->t('Domain'), $this->t('Editor count')],
    ];
    $build['#rows'][] = [$this->l($this->t('All affiliates'), Url::fromUri('internal:/admin/content/domain-editors/all_affiliates')), $this->getCount('user')];
    // @TODO: Inject this service.
    $domains = \Drupal::service('domain.loader')->loadMultipleSorted();
    foreach ($domains as $domain) {
      $row = [$this->l($domain->label(), Url::fromUri('internal:/admin/content/domain-editors/' . $domain->id())), $this->getCount('user', $domain)];
      $build['#rows'][] = $row;
    }
    return $build;
  }

  /**
   * Counts the content for a domain.
   *
   * @param $entity_type
   *  The entity type.
   * @param DomainInterface $domain
   *  The domain to query. If passed NULL, checks status for all affiliates.
   *
   * @return int
   */
  protected function getCount($entity_type = 'node', $domain = NULL) {
    if (is_null($domain)) {
      $field = DOMAIN_ACCESS_ALL_FIELD;
      $value = 1;
    }
    else {
      $field = DOMAIN_ACCESS_FIELD;
      $value = $domain->id();
    }
    // Note that we ignore node access so these queries work on any domain.
    $query = \Drupal::entityQuery($entity_type)
      ->condition($field, $value);

    return count($query->execute());
  }

}

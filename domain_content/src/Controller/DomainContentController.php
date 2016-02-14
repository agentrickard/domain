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

  public function contentList() {
    $build = [
      '#theme' => 'table',
      '#header' => [$this->t('Domain'), $this->t('Content count')],
    ];
    $build['#rows'][] = [$this->l($this->t('All affiliates'), Url::fromUri('internal:/admin/content/domain-content/all_affiliates')), $this->getEditorCount()];
    // @TODO: Inject this service.
    $domains = \Drupal::service('domain.loader')->loadMultipleSorted();
    foreach ($domains as $domain) {
      $row = [$this->l($domain->label(), Url::fromUri('internal:/admin/content/domain-content/' . $domain->id())), $this->getContentCount($domain)];
      $build['#rows'][] = $row;
    }
    return $build;
  }

  public function editorsList() {
    $build = [
      '#theme' => 'table',
      '#header' => [$this->t('Domain'), $this->t('Editor count')],
    ];
    $build['#rows'][] = [$this->l($this->t('All affiliates'), Url::fromUri('internal:/admin/content/domain-editors/all_affiliates')), $this->getEditorCount()];
    // @TODO: Inject this service.
    $domains = \Drupal::service('domain.loader')->loadMultipleSorted();
    foreach ($domains as $domain) {
      $row = [$this->l($domain->label(), Url::fromUri('internal:/admin/content/domain-editors/' . $domain->id())), $this->getEditorCount($domain)];
      $build['#rows'][] = $row;
    }
    return $build;
  }

  public function contentPage(DomainInterface $domain) {
    return ['#markup' => $domain->label()];
  }

  public function editorsPage(DomainInterface $domain) {
    return ['#markup' => $domain->label()];
  }

  protected function getContentCount($domain = NULL) {
    if (is_null($domain)) {
      return 50;
    }
    return 100;
  }

  protected function getEditorCount($domain = NULL) {
    if (is_null($domain)) {
      return 50;
    }
    return 100;
  }

}

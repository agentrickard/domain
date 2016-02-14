<?php
/**
 * @file
 * Contains \Drupal\domain_content\Controller\DomainContentController.
 */

namespace Drupal\domain_content\Controller;

use Drupal\domain\DomainInterface;
use Drupal\domain\Controller\DomainControllerBase;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Controller routines domain content pages.
 */
class DomainContentController extends DomainControllerBase {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  public function contentList() {
    $build = [
      '#markup' => 'foo',
    ];
    return $build;
  }

  public function editorsList() {
    $build = [
      '#markup' => 'bar',
    ];
    return $build;
  }

  public function contentPage(DomainInterface $domain) {

  }

  public function editorsPage(DomainInterface $domain) {

  }


}

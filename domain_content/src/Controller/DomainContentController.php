<?php

namespace Drupal\domain_content\Controller;

use Drupal\Core\Link;
use Drupal\domain\DomainInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Controller routines domain content pages.
 */
class DomainContentController extends ControllerBase {

  /**
   * Generates a list of content by domain.
   */
  public function contentList() {
    $account = $this->getUser();
    $permission = 'publish to any assigned domain';
    $build = [
      '#theme' => 'table',
      '#header' => [$this->t('Domain'), $this->t('Content count')],
    ];
    if ($account->hasPermission('publish to any domain')) {
      $build['#rows'][] = [
        Link::fromTextAndUrl($this->t('All affiliates'), Url::fromUri('internal:/admin/content/domain-content/all_affiliates')),
        $this->getCount('node'),
      ];
    }
    // Loop through domains.
    $domains = \Drupal::service('domain.loader')->loadMultipleSorted();
    /** @var \Drupal\domain\DomainInterface $domain */
    foreach ($domains as $domain) {
      if ($account->hasPermission('publish to any domain') || $this->allowAccess($account, $domain, $permission)) {
        $row = [
          Link::fromTextAndUrl($domain->label(), Url::fromUri('internal:/admin/content/domain-content/' . $domain->id())),
          $this->getCount('node', $domain),
        ];
        $build['#rows'][] = $row;
      }
    }
    return $build;
  }

  /**
   * Generates a list of editors by domain.
   */
  public function editorsList() {
    $account = $this->getUser();
    $permission = 'assign domain editors';
    $build = [
      '#theme' => 'table',
      '#header' => [$this->t('Domain'), $this->t('Editor count')],
    ];
    if ($account->hasPermission('assign editors to any domain')) {
      $build['#rows'][] = [
        Link::fromTextAndUrl($this->t('All affiliates'), Url::fromUri('internal:/admin/content/domain-editors/all_affiliates')),
        $this->getCount('user'),
      ];
    }
    // Loop through domains.
    $domains = \Drupal::service('domain.loader')->loadMultipleSorted();
    /** @var \Drupal\domain\DomainInterface $domain */
    foreach ($domains as $domain) {
      if ($account->hasPermission('assign editors to any domain') || $this->allowAccess($account, $domain, $permission)) {
        $row = [
          Link::fromTextAndUrl($domain->label(), Url::fromUri('internal:/admin/content/domain-editors/' . $domain->id())),
          $this->getCount('user', $domain),
        ];
        $build['#rows'][] = $row;
      }
    }
    return $build;
  }

  /**
   * Counts the content for a domain.
   *
   * @param string $entity_type
   *   The entity type.
   * @param DomainInterface $domain
   *   The domain to query. If passed NULL, checks status for all affiliates.
   *
   * @return int
   *   The content count for the given domain.
   */
  protected function getCount($entity_type = 'node', DomainInterface $domain = NULL) {
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

  /**
   * Returns a fully loaded user object for the current request.
   *
   * @return AccountInterface
   *   The current user object.
   */
  protected function getUser() {
    $account = $this->currentUser();
    // Advanced grants for edit/delete require permissions.
    return \Drupal::entityTypeManager()->getStorage('user')->load($account->id());
  }

  /**
   * Checks that a user can access the internal page for a domain list.
   *
   * @param AccountInterface $account
   *   The fully loaded user account.
   * @param DomainInterface $domain
   *   The domain being checked.
   * @param string $permission
   *   The relevant permission to check.
   *
   * @return bool
   *   Returns TRUE if the user can access the domain list page.
   */
  protected function allowAccess(AccountInterface $account, DomainInterface $domain, $permission) {
    $allowed = \Drupal::service('domain_access.manager')->getAccessValues($account);
    if ($account->hasPermission($permission) && isset($allowed[$domain->id()])) {
      return TRUE;
    }
    return FALSE;
  }

}

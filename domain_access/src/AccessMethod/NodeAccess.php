<?php

namespace Drupal\domain_access\AccessMethod;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain_access\DomainAccessMethodInterface;
use Drupal\domain_access\DomainAccessManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Class NodeAccess.
 *
 * @package Drupal\domain_access
 */
class NodeAccess implements DomainAccessMethodInterface {

  /**
   * @var \Drupal\domain_access\DomainAccessManagerInterface
   */
  protected $domainAccessManager;

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AccessMethodBase constructor.
   *
   * @param \Drupal\domain_access\DomainAccessManagerInterface $domainAccessManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(DomainAccessManagerInterface $domainAccessManager, DomainNegotiatorInterface $domainNegotiator, EntityTypeManagerInterface $entityTypeManager) {
    $this->domainAccessManager = $domainAccessManager;
    $this->domainNegotiator = $domainNegotiator;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function nodeAccessGrants(AccountInterface $account, $op) {
    $grants = [];
    /** @var \Drupal\domain\Entity\Domain $active */
    $active = $this->domainNegotiator->getActiveDomain();

    if (empty($active)) {
      $active = $this->entityTypeManager->getStorage('domain')->loadDefaultDomain();
    }

    // No domains means no permissions.
    if (empty($active)) {
      return $grants;
    }

    $id = $active->getDomainId();
    // Advanced grants for edit/delete require permissions.
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $user_domains = $this->domainAccessManager->getAccessValues($user);
    // Grants for view are simple. Use the active domain and all affiliates.
    // Note that "X to any domain" is a global permission designed for admins.
    if ($op == 'view') {
      $grants['domain_id'][] = $id;
      $grants['domain_site'][] = 0;
      if ($user->hasPermission('view unpublished domain content')) {
        if ($user->hasPermission('publish to any domain') || in_array($id, $user_domains) || !empty($user->get(DOMAIN_ACCESS_ALL_FIELD)->value)) {
          $grants['domain_unpublished'][] = $id;
        }
      }
    }
    elseif ($op == 'update' && $user->hasPermission('edit domain content')) {
      if ($user->hasPermission('publish to any domain') || in_array($id, $user_domains) || !empty($user->get(DOMAIN_ACCESS_ALL_FIELD)->value)) {
        $grants['domain_id'][] = $id;
      }
    }
    elseif ($op == 'delete' && $user->hasPermission('delete domain content')) {
      if ($user->hasPermission('publish to any domain') || in_array($id, $user_domains) || !empty($user->get(DOMAIN_ACCESS_ALL_FIELD)->value)) {
        $grants['domain_id'][] = $id;
      }
    }
    return $grants;
  }

  /**
   * {@inheritdoc}
   */
  public function nodeAccessRecords(NodeInterface $node) {
    $grants = [];
    // Create grants for each translation of the node. See the report at
    // https://www.drupal.org/node/2825419 for the logic here. Note that right
    // now, grants may not be the same for all languages.
    $translations = $node->getTranslationLanguages();
    foreach ($translations as $langcode => $language) {
      $translation = $node->getTranslation($langcode);
      // If there are no domains set, use the current one.
      $domains = \Drupal::service('domain_access.manager')->getAccessValues($translation);
      /** @var \Drupal\domain\DomainInterface $active */
      if (empty($domains) && $active = \Drupal::service('domain.negotiator')->getActiveDomain()) {
        $domains[$active->id()] = $active->getDomainId();
      }
      foreach ($domains as $id => $domainId) {
        /** @var \Drupal\domain\DomainInterface $domain */
        if ($domain = \Drupal::entityTypeManager()->getStorage('domain')->load($id)) {
          $grants[] = [
            'realm' => ($translation->isPublished()) ? 'domain_id' : 'domain_unpublished',
            'gid' => $domain->getDomainId(),
            'grant_view' => 1,
            'grant_update' => 1,
            'grant_delete' => 1,
            'langcode' => $langcode,
          ];
        }
      }
      // Set the domain_site grant.
      if ($translation->hasField(DOMAIN_ACCESS_ALL_FIELD) && !empty($translation->get(DOMAIN_ACCESS_ALL_FIELD)->value) && $translation->isPublished()) {
        $grants[] = [
          'realm' => 'domain_site',
          'gid' => 0,
          'grant_view' => 1,
          'grant_update' => 0,
          'grant_delete' => 0,
          'langcode' => $langcode,
        ];
      }
      // Because of language translation, we must save a record for each language.
      // even if that record adds no permissions, as this one does.
      else {
        $grants[] = [
          'realm' => 'domain_site',
          'gid' => 1,
          'grant_view' => 0,
          'grant_update' => 0,
          'grant_delete' => 0,
          'langcode' => $langcode,
        ];
      }
    }
    return $grants;
  }

}
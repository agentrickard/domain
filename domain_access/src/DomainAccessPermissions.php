<?php

namespace Drupal\domain_access;

use Drupal\node\Entity\NodeType;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Dynamic permissions class for Domain Access.
 */
class DomainAccessPermissions {

  use StringTranslationTrait;

  /**
   * Define permissions.
   */
  public function permissions() {
    $permissions = [
      'assign domain editors' => [
        'title' => $this->t('Assign additional editors to assigned domains'),
        'restrict access' => TRUE,
      ],
      'assign editors to any domain' => [
        'title' => $this->t('Assign additional editors to any domains'),
        'restrict access' => TRUE,
      ],
      'publish to any domain' => [
        'title' => $this->t('Publish to any domain'),
      ],
      'publish to any assigned domain' => [
        'title' => $this->t('Publish content to any assigned domain'),
      ],
      'create domain content' => [
        'title' => $this->t('Create any content on assigned domains'),
      ],
      'edit domain content' => [
        'title' => $this->t('Edit any content on assigned domains'),
      ],
      'delete domain content' => [
        'title' => $this->t('Delete any content on assigned domains'),
      ],
      'view unpublished domain content' => [
        'title' => $this->t('View unpublished content on assigned domains'),
        'restrict access' => TRUE,
      ],
    ];

    // Generate standard node permissions for all applicable node types.
    foreach (NodeType::loadMultiple() as $type) {
      $permissions += $this->nodePermissions($type);
    }

    return $permissions;
  }

  /**
   * Helper method to generate standard node permission list for a given type.
   *
   * Shamelessly lifted from node_list_permissions().
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type object.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  private function nodePermissions(NodeType $type) {
    // Build standard list of node permissions for this type.
    $id = $type->id();
    $perms = [
      "create $id content on assigned domains" => [
        'title' => $this->t('%type_name: Create new content on assigned domains', ['%type_name' => $type->label()]),
      ],
      "update $id content on assigned domains" => [
        'title' => $this->t('%type_name: Edit any content on assigned domains', ['%type_name' => $type->label()]),
      ],
      "delete $id content on assigned domains" => [
        'title' => $this->t('%type_name: Delete any content on assigned domains', ['%type_name' => $type->label()]),
      ],
    ];

    return $perms;
  }

}

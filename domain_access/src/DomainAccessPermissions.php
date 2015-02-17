<?php
/**
 * @file
 * Contains \Drupal\domain_access\DomainAccessPermissions.
 */

namespace Drupal\domain_access;

use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;

/**
 * Dynamic permissions class for Domain Access.
 */
class DomainAccessPermissions {

  /**
   * Define permissions.
   */
  public function permissions() {
    $permissions = array(
      'assign domain editors' => array(
        'title' => 'Assign editors to assigned domains',
      ),
      // @TODO: check how this will work.
      'set domain access' => array(
        'title' => 'Set domain access status for all content',
      ),
      'publish to any assigned domain' => array(
        'title' => 'Publish content to any assigned domain',
      ),
      'publish from assigned domain' => array(
        'title' => 'Publish content only from assigned domain',
      ),
      'publish from default domain' => array(
        'title' => 'Publish content only from the default domain',
      ),
      'edit domain content' => array(
        'title' => 'Edit any content on assigned domains',
      ),
      'delete domain content' => array(
        'title' => 'Delete any content on assigned domains',
      ),
      'view unpublished domain content' => array(
        'title' => 'View unpublished content on assigned domains',
      ),
    );

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
   * @param $type
   *   The node type object.
   * @return array
   *   An array of permission names and descriptions.
   */
  function nodePermissions(NodeTypeInterface $type) {
    // Build standard list of node permissions for this type.
    $typeId = $type->getEntityTypeId();
    $typeLabel = $type->label();
    $perms = array(
      "create $typeId content on assigned domains" => array(
        'title' => t('%type_name: Create new content on assigned domains', array('%type_name' => $typeLabel)),
      ),
      "update $typeId content on assigned domains" => array(
        'title' => t('%type_name: Edit any content on assigned domains', array('%type_name' => $typeLabel)),
      ),
      "delete $typeId content on assigned domains" => array(
        'title' => t('%type_name: Delete any content on assigned domains', array('%type_name' => $typeLabel)),
      ),
    );

    return $perms;
  }

}

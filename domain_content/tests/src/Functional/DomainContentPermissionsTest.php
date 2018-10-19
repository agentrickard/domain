<?php

namespace Drupal\Tests\domain_content\Functional;

/**
 * Creates domain admins and test which content lists they can access.
 *
 * @group domain_content
 */
class DomainContentPermissionsTest extends DomainContentTestBase {

  /**
   * Tests domain content permissions.
   */
  public function testDomainContentPermissions() {
    // This user should be able to see everything.
    $this->admin_user = $this->drupalCreateUser([
      'administer domains',
      'access administration pages',
      'access domain content',
      'access domain content editors',
      'publish to any domain',
      'assign editors to any domain',
    ]);
    $this->drupalLogin($this->admin_user);

    // Create users and content.
    $this->createDomainContent();
    $this->createDomainUsers();

    // Base Urls for our views.
    $urls = [
      'admin/content/domain-content',
      'admin/content/domain-editors',
    ];
    // Test the overview and domain-specific pages.
    foreach ($urls as $url) {
      $this->drupalGet($url);
      $this->assertResponse(200);
      // Find the links.
      $this->findLink('All affiliates');
      foreach ($this->domains as $id => $domain) {
        $this->findLink($domain->label());
      }

      // All affiliates link.
      $this->drupalGet($url . '/all_affiliates');
      $this->assertResponse(200);

      // Individual domain pages.
      foreach ($this->domains as $id => $domain) {
        $this->drupalGet($url . '/' . $id);
        $this->assertResponse(200);
      }
    }
    // This user should be able to see everything but all affiliates.
    $this->limited_user = $this->drupalCreateUser([
      'administer domains',
      'access administration pages',
      'access domain content',
      'access domain content editors',
      'publish to any assigned domain',
      'assign domain editors',
    ]);
    $this->addDomainsToEntity('user', $this->limited_user->id(), array_keys($this->domains), DOMAIN_ACCESS_FIELD);

    $this->drupalLogin($this->limited_user);
    // Test the overview and domain-specific pages.
    foreach ($urls as $url) {
      $this->drupalGet($url);
      $this->assertResponse(200);
      // Find the links.
      $this->assertNoRaw('All affiliates');
      foreach ($this->domains as $id => $domain) {
        $this->findLink($domain->label());
      }

      // All affiliates link.
      $this->drupalGet($url . '/all_affiliates');
      $this->assertResponse(403);

      // Individual domain pages.
      foreach ($this->domains as $id => $domain) {
        $this->drupalGet($url . '/' . $id);
        $this->assertResponse(200);
      }
    }

    // This user should be able to see everything but all affiliates and nothing
    // for editor assignments.
    $this->editor_user = $this->drupalCreateUser([
      'access administration pages',
      'access domain content',
      'publish to any assigned domain',
    ]);
    $this->addDomainsToEntity('user', $this->editor_user->id(), array_keys($this->domains), DOMAIN_ACCESS_FIELD);

    $this->drupalLogin($this->editor_user);
    // Test the overview and domain-specific pages.
    foreach ($urls as $url) {
      $expected = 200;
      if ($url == 'admin/content/domain-editors') {
        $expected = 403;
      }
      $this->drupalGet($url);
      $this->assertResponse($expected);
      // Find the links.
      $this->assertNoRaw('All affiliates');
      foreach ($this->domains as $id => $domain) {
        if ($expected == 200) {
          $this->findLink($domain->label());
        }
        else {
          $this->findNoLink($domain->label());
        }
      }

      // All affiliates link will fail for both paths.
      $this->drupalGet($url . '/all_affiliates');
      $this->assertResponse(403);

      // Individual domain pages.
      foreach ($this->domains as $id => $domain) {
        $this->drupalGet($url . '/' . $id);
        $this->assertResponse($expected);
      }
    }

    // This user should be able to see one domain for editor assignments.
    $this->assign_user = $this->drupalCreateUser([
      'access administration pages',
      'access domain content editors',
      'assign domain editors',
    ]);
    $ids = array_keys($this->domains);
    $assigned_id = end($ids);
    $this->addDomainsToEntity('user', $this->assign_user->id(), [$assigned_id], DOMAIN_ACCESS_FIELD);

    $this->drupalLogin($this->assign_user);
    // Test the overview and domain-specific pages.
    foreach ($urls as $url) {
      $expected = 200;
      if ($url == 'admin/content/domain-content') {
        $expected = 403;
      }
      $this->drupalGet($url);
      $this->assertResponse($expected);
      // Find the links.
      $this->assertNoRaw('All affiliates');
      foreach ($this->domains as $id => $domain) {
        if ($expected == 200 && $id == $assigned_id) {
          $this->findLink($domain->label());
        }
        else {
          $this->findNoLink($domain->label());
        }
      }

      // All affiliates link will fail for both paths.
      $this->drupalGet($url . '/all_affiliates');
      $this->assertResponse(403);

      // Individual domain pages.
      foreach ($this->domains as $id => $domain) {
        $this->drupalGet($url . '/' . $id);
        if ($expected == 200 && $id == $assigned_id) {
          $this->assertResponse(200);
        }
        else {
          $this->assertResponse(403);
        }
      }
    }
  }

}

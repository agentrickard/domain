<?php

namespace Drupal\Tests\domain_content\Functional;

/**
 * Creates domain admins and test which content lists they can access.
 *
 * @group domain_content
 */
class DomainContentPermissionsTest extends DomainContentTestBase {

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
    // This user should be able to see everything but all affiliates
    $this->limited_user = $this->drupalCreateUser([
      'administer domains',
      'access administration pages',
      'access domain content',
      'access domain content editors',
      ]);
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

  }

}

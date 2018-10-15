<?php

namespace Drupal\Tests\domain_content\Functional;

/**
 * Creates editors and users and count them on the overview page.
 *
 * @group domain_content
 */
class DomainContentCountTest extends DomainContentTestBase {

  /**
   * Tests domain content count.
   */
  public function testDomainContentCount() {
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
    // Test the overview pages.
    foreach ($urls as $url) {
      $content = $this->drupalGet($url);
      $this->assertResponse(200);
      // Find the links.
      $this->findLink('All affiliates');
      foreach ($this->domains as $id => $domain) {
        $this->findLink($domain->label());
        $string = $domain->label() . "</a></td><td>5</td>";
        $this->checkContent($content, $string);
      }
      $string = 'All affiliates</a></td><td>5</td>';
      $this->checkContent($content, $string);
    }
  }

}

// $Id$

/**
 * @file
 * README file for Domain Content
 */
 
Domain Access: Content Administration
Provides a Content list screen for each active domain.

CONTENTS
--------

1.  Introduction
1.1   Use-Case
1.2   Sponsors
2.  Installation
2.1   Permissions
3.  Menu Items
3.1   Access Control
3.2   Affiliated Content
3.3   Active Domains
4.  Content Editing
4.1   Privileged Users
4.2   Affiliates
5.  Developer Notes


----
1.  Introduction

The Domain Content provides an alternate view for batch editing site content.
The module is part of the Domain Access module group.

----
1.1 Use-Case

Domain Access is a node access module.  By design, Drupal's default content
administration page does not respect node access rules.

The Domain Content provides an additional set of batch content administration
pages that respect the node access rules set by the Doamin Access module.

----
1.2 Sponsors

Domain Content is sponsored by Morris DigitalWorks.
  http://morrisdigitalworks.com
  
----
2.  Installation

To install the module, simply untar the download and put it in your site's
modules directory.  After reading this document, enable the module normally.

When you enable the module, no new tables will be created in your Drupal 
database.

----
2.1 Permissions

The Domain Content module does not add any new Access Control permissions.

The module uses the existing permissions from the Domain Access module.

----
3.  Menu Items

When the module is installed, a new top-level Administration menu is created.
This menu is titled 'Affiliated Content'.

----
3.1 Access Control

The Access Control permissions for the module are set by the root Domain Access
module. They are:

  - 'edit domain nodes'
  - 'set domain access'
  
Users with the 'edit domain nodes' permission can view content for domains where
they are assigned editors.  Users with the 'set domain access' permission can 
view content for all active domains.

Either permission is required to access the 'Affiliate content' screen.

----
3.2 Affiliated Content

The Affiliated Content link resides at the path 'admin/domain/content'.  This 
page returns a list of all domains for which the user can edit or view content.

By design, the first menu item under Affiliated Content is the 'Content for all
affiliate sites' link.  This page will show all content that is viewable across
all site affiliates.  [Technically, the page shows all content assigned to the
'domain_site' access realm.]

----
3.3 Active Domains

For each active domain in your Domain Access configuration, the Domain Content
module will set a new menu item.  This page shows a list of all content that is
assigned to that domain.

If users have the appropriate access to edit content for that domain, the menu
item will be available.

----
4.  Content Editing

The batch editing forms for Domain Content work identically to those for the
default Drupal 'administer content' screen.

----
4.1 Privileged Users

It is possible that some users with 'administer nodes' and 'edit TYPE nodes'
permissions may be able to see nodes that do not belong to the selected domain.
This behavior is currently unavoidable, due to the nature of node access in 
Drupal.  Users with these permissions will be shown a message indicating that
some content might not be specific to the current domain.

----
4.2 Affiliates

On the batch editing form is an additional column labelled 'Affiliates.'

This column shows the Domain Access rules for each node.  Remember that content
that is assigned to more than one domain can be edited by multiple users, so
be careful when editing content that is published to multiple affiliates.

----
5.  Developer Notes

A companion module that handles this function for Comments is also needed.

Contributions welcome.

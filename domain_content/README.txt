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
2.  Installation
2.1   Permissions
3.  Menu Items
3.1   Access Control
3.2   Affiliated Content
3.3   Active Domains
4.  Content Editing
4.1   Affiliates
4.2   Domain Access Options
4.3   Form Behavior
5.  Developer Notes


----
1.  Introduction

The Domain Content provides an alternate page for batch editing site content.
The module is part of the Domain Access module group.

For administrative users, this module also enables a new batch update
operation on the Content administration screen.

----
1.1 Use-Case

Domain Access is a node access module.  By design, Drupal's default content
administration page does not respect node access rules.

The Domain Content provides an additional set of batch content administration
pages that respect the node access rules set by the Domain Access module.

----
2.  Installation

To install the module, simply untar the download and put it in your site's
modules directory.  After reading this document, enable the module normally.

When you enable the module, no new tables will be created in your Drupal
database.

If you wish for users with the 'edit domain content' permission to access the
Domain Content interface, you must enable the 'Use access control for
editors' option under Advanced Settings on the Domains configuration page:

  Structure > Domains

----
2.1 Permissions

The Domain Content adds 'review content for all domains' permission to Access
Control permissions and uses the existing permissions from the Domain Access
module.

----
3.  Menu Items

When the module is installed, a new top-level Administration menu is created.
This menu is titled 'Affiliated Content'.

If your site has fewer domains than set in the Domain List Size setting
of the main module, then each affiliate will be given its own menu entry.
The default size of this variable is 25.

See http://drupal.org/node/367752 for the rationale.

----
3.1 Access Control

The relevant permissions to Domain Content are:

  - 'edit domain content'
  - 'set domain access'
  - 'review content for all domains'
  - 'access the domain content page'
  - 'administer nodes'

Users with the 'access the domain content page' permission can view content for
domains where they are assigned editors.  Users with the 'set domain access'
permission can view content for domains where they are assigned editors _and_
reassign content to one or more affiliates.

Users with the core 'administer nodes' permission may perform additional
operations (such as deleting or promoting content).

Either the 'access the domain content page'' or the 'review content for all
domains' permission is required to access the 'Affiliated content' screen.

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
4.1 Affiliates

On the batch editing form is an additional column labeled 'Affiliates.'

This column shows the Domain Access rules for each node.  Remember that content
that is assigned to more than one domain can be edited by multiple users, so
be careful when editing content that is published to multiple affiliates.

----
4.2 Domain Access Options

You may use this form to batch update the Domain Access rules for your nodes.

If you have the 'set domain access' permission, you will see the Domain Access
Options form elements beneath the node list.

If you select the operation "Change affiliate publishing options", any nodes that you
select can be batch updated to the new settings you select.

By default, the currently active domain will be chosen, as will the value set for
promoting new nodes to all affiliates.

WARNING: It is possible that you may move some nodes to domains other
than the currently active domain.  If so, some nodes will be removed from
the form after you submit the update.  This behavior is normal and desired.

----
4.3 Form Behavior

In 6.x.2.5 and higher, you may select one of two options when updating domains.

Under the 'Update behavior' form element, you may choose:

  [] Replace old values with new settings
  [] Add new settings to existing values
  [] Remove selected domains from existing values

Choosing 'replace' will erase any current domain affiliation for the selected nodes
and replace them with those entered into the form. Choosing 'add' will merge the
new values with the existing values. Choosing 'remove' will remove the new values
from the existing ones.

This new feature is helpful when you want to alter domain settings, but do not
want all nodes to be assigned to the same affiliates.

----
5.  Developer Notes

A companion module that handles this function for Comments is also needed.

For support for Views Bulk Operations (which was released after this module)
see the Domain Actions project: http://drupal.org/project/domain_actions.

/**
 * @file
 * README file for Domain Navigation
 */

Domain Access: Navigation
Navigation block and menu options for Domain Access.

CONTENTS
--------

1.  Introduction
2.  Installation
2.1   Dependencies
2.2   Permissions
3.  Configuration Options
3.1   Link Paths
3.2   Link Theme
3.3   Menu Items
4.  Developer Notes
4.1   domain_nav_render()
4.2   hook_domain_nav()

----
1.  Introduction

The Domain Access: Navigation module is a small module that generates
a block of themed HTML with links to the currently available domains used
by your site.

The default implementation is a JavaScript-enabled select form that sends
users to a different domain when selected.

----
2.  Installation

The Domain Navigation module is included in the Domain Access download.
To install, untar the domain package and place the entire folder in your modules
directory.

The Domain Navigation module does not add any database tables.

----
2.1 Dependencies

Domain Navigation requires the Domain Access module be installed and active.

----
2.2 Permissions

As of 6.x.2.0, Domain Navigation has one permission:

  - 'access domain navigation'
  This permission allows users to view the Domain Navigation block.

The module also uses the following inherited permission:

  - 'access inactive domains'
  If the user has this permission, inactive domains are shown and marked with
  an asterisk (*).

----
3.  Configuration Options

When active, the Domain Navigation module provides a block for use with your
Drupal themes.  By default, this block presents a Javascript-powered
select list.

If you click 'configure' for the block, you can set the block title (which
is empty by default) and control the following behaviors.

----
3.1 Link Paths

Indicates whether to link to the home page of each domain or to the active
url on the domain.

If set to 'Link to site home page' (the default option), all links will go to
http://example.com/, http://one.example.com, and so forth, regardless of the
current url.

If set to 'Link to active url,' all links will go to the equivalent url on the
selected domain.  That is, if the user is at http://example.com/?q=node, then
links will be written to http://one.example.com/?q=node.

NOTE: Linking to the active url may cause Access Denied messages if users link
from a node page, since Domain Access restricts node views to specific domains.

----
3.2 Link theme

Indicates how to format the HTML output.  There are three options:

  - JavaScript select list
  Creates a select-list form that uses JavaScript to goto the selected domain.
  Requires JavaScript and does not include a submit button.
  Note: This is _not_ a drupal-generated form element.

  - Menu-style tab links
  Creates a list of links formatted like primary tabs, with the active domain
  highlighted.

  - Unordered list of links
  Creates a simple unordered list of links.

----
3.3 Menu Items

The Domain Navigation module creates a group of menu items that correspond to
the home pages of your active domains.

By design, the root menu element is disabled, since it is only used to group
the menu items together.

The designed use-case of the menu is for use as Primary or Secondary links.  To
enable this feature, use the following steps:

  - Go to 'admin/structure/menu'
  - Find the Navigation => Domain menu item.
  - Enable the menu item.
  - Assign the menu item to Primary or Secondary links, as desired.
  - Save the changes.
  - Disable the top-level 'Domain' menu item, but leave the others intact.
  - Sort the menu items so that the active links are at the top level of the menu.

This final step is new in Drupal 6.

NOTE: If you wish to disable the menu entirely, but keep the block functions
for this module, you may edit the following line at the top of the module:

  define('DOMAIN_NAV_MENU', TRUE);

If you set this value to FALSE before you install the module, the menu items
will not be created.  If you have already installed the module, you may also set
this value to FALSE and then navigate to 'admin/structure/menu'.  Note that
updated module releases will always reset this value to TRUE.

----
4.  Developer Notes

Some working notes on the module, which can be invoked by other template
or module files.

----
4.1 domain_nav_render()

This function allows you to place the themed HTML in your own module,
theme, or block function call.

Just call:
 domain_list_render($paths = 0, $style = 'default');

Where $paths is a boolean flag indicating how to write links to other domains:
  0 == link to home page
  1 == link to current url

And $style indicates which theme function to invoke.  Default options are:
  'default' == theme_domain_nav_default()
  'menus' == theme_domain_nav_menus()
  'ul' == theme_domain_nav_ul()

----
4.2 hook_domain_nav()

The domain_nav hook allows other modules to add parameters to the $options
array that is passed to theme functions.  It is intended for use with
custom theme functions of theme overrides that you may use.

To use the function, Implements hook_domain_nav($domain).  You should return
an array of values to append to $options.

Default parameters are passed in the $domain variable and should not be changed;
these are:
  domain_id == the unique identifier of this domain
  subdomain == the host path of the url for this domain
  sitename == the human-readable name of this domain
  path == the link path (a Drupal-formatted path)
  active == a boolean flag indicating the currently active domain

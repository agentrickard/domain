/**
 * @file
 * README file for Domain Access.
 */

Domain Access
A domain-based access control system.

===============================================================================
This document is out of date. Current documentation is online at:

http://drupal.org/documentation/modules/domain

If you find errors or omissions, please help fix them. Anyone can create
documentation pages.

===============================================================================


CONTENTS
--------

1.  Introduction
1.1   Use-Case
1.2   Examples
1.3   Using Multiple Node Access Modules
1.4   Known Issues
1.4.1   Logging In To Multiple Domains
1.4.2   Cron Handling
1.4.3   Updating Your Site
2.  Installation
2.1   Edits to settings.php
2.2   Server Configuration
2.3   Creating Domain Records
2.4   Setting DOMAIN_INSTALL_RULE
2.5   Setting DOMAIN_SITE_GRANT
2.6   Setting DOMAIN_ASSIGN_USERS
3.  Permissions
3.1   Module Permissions
3.2   Normal Usage
3.3   Advanced Usage
3.4   Limitations
4.  Module Configuration
4.1   Default Domain Settings
4.1.1   Primary Domain Name
4.1.2   Site Name
4.1.3   Domain URL Scheme
4.2   Creating Domain Records
4.2.1   Restricted Characters in Domains
4.2.2   Altering Domain Name Validation
4.3   Domain Module Behaviors
4.3.1   New Content Settings
4.3.1.1   Send to All Affiliates
4.3.2   Debugging Status
4.3.3   Enforce Rules on Administrators
4.3.4   Domain List Size
4.3.5   Display in Vertical Tabs
4.3.6   Domain Selection Format
4.4   Advanced Settings
4.4.1   Search Settings
4.4.2   Search Engine Optimization
4.4.3   Default Source Domain
4.4.4   WWW Prefix Handling
4.5   Special Page Requests
4.5.1   Cron Handling
4.5.2   XMLRPC Handling
4.6   Node Link Patterns
4.7   The Domain List
4.8   Batch Updating
4.9  Assigning Users to Domains
4.10  Batch Assignment of Users to Domains
4.10.1  Form Behavior
5.  Blocks
5.1   Block -- Domain Switcher
5.2   Block -- Domain Access Information
6.  Node Access
6.1   Assigning Domain Access
6.2.  Editor Access
6.3   Realms
6.4   Grants
6.5   Warnings
7.  Developer Notes
7.1   Extension Modules
7.2   The $_domain Global
7.3   Database Schema
7.4   API
7.5   Domain Tokens
8.  Drush commands


----
1.  Introduction

Before using the module, you should read the installation instructions found
in INSTALL_QUICKSTART.txt.

The Domain Access module group is designed to run an affiliated network of sites
from a single Drupal installation.  The module allows you to share users,
content, and configurations across a group of sites such as:

  - example.com
  - one.example.com
  - two.example.com
  - my.example.com
  - thisexample.com
  - anothersite.com
  - example.com:3000 <-- non-standard ports are treated as unique domains.

By default, these sites share all tables in your Drupal installation.

The module uses Drupal's node_access() system to determine what content is
available on each site in the network.  Unlike other multi-domain modules for
Drupal, the Domain Access module determines user access based on the active
domain that the user is viewing, rather than which group or site the user
belongs to.

Additionally, when a user creates content, that content will automatically be
assigned to the currently active domain unless the user has specific
privileges to be able to assign domain access.  Under advanced setups, the
ability to edit content for a specific domain can be segregated from the
typical Drupal privilege to 'Bypass content access control.'

For more information about Domain Access privileges, see section 3.

For more information about node_access(), see
http://api.drupal.org/api/group/node_access/6

----
1.1 Use-Case

The module was initially developed for a web site that sold franchises of a
monthly magazine.  The publishing rules were as follows:

  - Content may belong to the national site, one or more affiliates, or to
    all affiliates.
  - National editors may select to promote affiliate content to other
    affiliates, the national site, or to all affiliates.
  - Local editors may only create and edit content for their own affiliate
    sites.

These rules are enforced programmatically by the Domain Access module.  There
was concern that, if given a choice to make, local editors would not assign the
content correctly.  Therefore, the module handles this automatically, and local
editors have no control over which domains their content is published to.

This video from DrupalCon Paris explains the module in detail:

  http://www.archive.org/details/SharingcontentacrossmultiplesiteswithDomainAccess

----
1.2 Examples

For the original example of the module in use, see http://skirt.com/.

For case-studies, see:

  http://drupal.org/node/369398
  http://www.trellon.com/content/blog/sharing-content-domain-access

Sites using Domain Access include:

    * www.barnard.edu (Drupal 7)
    * www.interlochen.org
    * www.skirt.com
    * www.dzone.com
    * www.itgjamaica.com
    * www.rowelevenwines.com
    * www.weecology.org
    * www.komonews.com/communities


----
1.3   Using Multiple Node Access Modules

Node Access is a complex issue in Drupal.  Typically, sites will only use
one node access module at a time.  In some cases, you may require
more advances access control rules.

Two important issues to understand are:

  1. User 1 and users with the 'Bypass node access' permission are not
  subject to node access rules. See section 4.3.3 for more details.

  2. Node Access is a permissive API. If you use more than one Node Access
  module (such as Organic Groups), if either module grants access, users will
  be given access.

Changes to these core behaviors require custom code solutions.

----
1.4   Known Issues

There are some issues that occur when Domain Access is used outside
of its original use case.  These are probably fixable, but may not work
as you expect.  You should pay careful attention to your site behavior.

----
1.4.1   Logging In To Multiple Domains

The Domain Access module allows the creation of domains with different
hosts.  However, security standards dictate that cookies can only be
read from the issuing domain.

As a result, you may configure your site as follows, but when you do so,
users cannot be logged through a single sign in.

  example.com
  one.example.com
  myexample.com
  thisexample.com

While example.com and one.example.com can share a login cookie, the
other two domains cannot read that cookie.  This is an Internet standard,
not a bug.

Note: See the INSTALL.txt for instructions regarding Drupal's default
cookie handling.

----
1.4.2   Cron Handling

When Drupal's cron function runs, it operates on the domain from which
the cron.php script is invoked.  That is, if you setup cron to run from:

  http://one.example.com/cron.php

In this case, Domain Access will check the access settings for that domain.

This behavior has been known to cause issues with other contributed modules.
As a solution, we normally disable Domain Access rules when cron runs.

For more information, see section 4.5.1 Cron Handling.

If you encounter any cron-related issues, please report them at:

http://drupal.org/project/issues/domain

----
1.4.3   Updating Your Site

For upgrade instructions, see the provided UPGRADE.txt.

----
2.  Installation

WARNING: The Domain Access module assumes that you have already installed
and configured your Drupal site.  Please do so before continuing.

Installing the module requires that you share a single copy of settings.php
for all domains that will be registered with Domain Access.

You must also add code to that settings.php file in order to load the domain
handling code. See INSTALL_QUICKSTART.txt for instructions.

For detailed instructions, see INSTALL.txt.

After you have completed the steps outlined by the installer, you may enable
the module normally. When you enable the module, it will create a {domain} table
in your Drupal database.

All existing nodes and users on your site will be assigned to the default domain
for your web site. Existing content will be set to be visible on all new
domains.  If you wish to alter this behavior, see sections 2.4 through 2.6.

----
2.1   Edits to settings.php

Please see the documentation in INSTALL.txt or INSTALL_QUICKSTART.txt

----
2.2 Server Configuration

For the module to work correctly, the DNS record of your server must accept
multiple DNS entries pointing at a single IP address that hosts your Drupal
installation.

The two basic methods for doing this are either to:

  - Setup WildCard DNS, so that *.example.com resolves to your Drupal site.
  - Setup VirtualHosts so that one.example.com, two.example.com, etc. all
    resolve to your Drupal site.

For example, on my local testing machine, I have VirtualHosts to the following
sites setup in httpd.conf:

  - example.com => 127.0.0.1
  - one.example.com => 127.0.0.1
  - two.example.com => 127.0.0.1
  - three.example.com => 127.0.0.1

It is beyond the scope of this document to explain how to configure your DNS
server.  For more information, see:

  - http://en.wikipedia.org/wiki/Wildcard_DNS_record
  - http://en.wikipedia.org/wiki/Virtual_hosting

After you have enabled multiple DNS entries to resolve to your Drupal
installation, you may activate the module and configure its settings.

No matter how many domains resolve to the same IP, you only need one instance
of Drupal's settings.php file.  The sites folder should be named 'default' or
named for your root domain.

NOTE: If you choose the WildCard DNS option, any subdomain not controlled
by the Domain module, including misspelled subdomains, will go to the default
domain without a redirect. To properly redirect invalid subdomains, use the
Domain Alias module and set *.example.com as an alias of your primary domain
with the 'redirect' setting checked. More information can be found in the
README.txt in the domain_alias directory.

----
2.3 Creating Domain Records

After you enable the module, you will have a user interface for registering new
domains with your site.  For these to work correctly, they must also be
configured by your DNS server.

To be clear: creating a new domain record through this module will not alter
the DNS server of your web server.

----
2.4 Setting DOMAIN_INSTALL_RULE

This is an advanced instruction, and may be ignored.

At the top of the domain.module file, you will find this line:

  define('DOMAIN_INSTALL_RULE', TRUE);

This setting controls the default behavior of the module when installing over
an existing installation.  If set to TRUE, the Domain Access module will assign
all existing nodes to be viewable by your primary domain.

If you set this value to FALSE, existing content will not be visible on your
primary domain unless DOMAIN_SITE_GRANT is set to TRUE.

For more details, see section 6.

----
2.5 Setting DOMAIN_SITE_GRANT

At the top of the domain.module file, you will find this line:

  define('DOMAIN_SITE_GRANT', TRUE);

This setting controls the default behavior for viewing affiliate content.
By design, the Domain Access module allows site administrators to assign
content to 'all affiliates.'  If this value is set to TRUE, then content
assigned to all affiliates can be seen by all users on all current domains.

On install, setting this value to TRUE will assign all current content to
be viewable on all domains.

Normally, you will not need to edit this value.

----
2.6   Setting DOMAIN_ASSIGN_USERS

At the top of the domain.module file, you will find this line:

  define('DOMAIN_ASSIGN_USERS', TRUE);

After you install the Domain Access module, all new users who
register will automatically be assign to the domain from which
their account was created. This value is used to determine
advanced editing access and can be used by modules such as
Domain Strict.

On install, setting this value to TRUE will assign all current users
to be members of the default domain. Set the value to FALSE
and the module will not assign users to any domains.

Normally, you will not need to edit this value.

After installation and configuration, users with the appropriate
permissions may batch assign users to domains from
Administer > User Management > Users.

----
3.  Permissions

After enabling the module, go to Access Control to configure the module's
permissions.

----
3.1 Module Permissions

The Domain Access module has the following permissions:

  - 'Administer domain records and settings'
  This permission allows users to create and manage domain records
  and settings.

  - 'Access inactive domains'
  This permission allows users to navigate to domains which are marked
  as inactive. Users with this permission may also assign content to an
  inactive domain.

  'assign domain editors'
  This permission allows users to assign themselves and other users as
  affiliate editors.  For those users to act as editors, their role(s) must also
  have the 'Edit any content on assigned domains' permission.

  - 'Edit any content on assigned domains'
  This permission is for advanced use and substitutes for the normal
  'Bypass content access control' permission for sites that give restricted
  administrative privileges.  See section 3.3 for more information.

  - 'Delete any content on assigned domains'
  This permission is for advanced use and substitutes for the normal
  'Bypass content access control' permission for sites that give restricted
  administrative privileges.  See section 3.3 for more information.

  - 'View unpublished content on assigned domains'
  This permission allows editors to view unpublished content assigned to
  their domain(s). This permission only applies to viewing a single content
  page; it does not affect list views.

  - 'Set domain access status for all content'
  This permission is key.  Users with this permission will be given a user
  interface for assigning users and nodes to specific domains.  Users without
  this permission cannot assign domain access; their nodes will automatically
  be assigned to the currently active domain.

  For example, if a user has this permission and creates a book page on
  one.example.com, the user will be given a series of options to assign that
  book page to any or all of the registered domains on the site.

  If the user does not have this permission, the book page will only be shown
  to users who are on http://one.example.com.

  - 'Publish content only from the default domain'
  - 'Publish content only from assigned domain'
  - 'Publish content to any assigned domain'
  This group of permission provides a limited set of options for users to create
  and edit content on your site.  Users who have this permission will have their
  node editing forms processed according to the following rules:

  -- 'Publish content only from the default domain'
  Before being presented the editing form, users will be taken to the root
  domain.  If the node is not visible on the root domain, the user may not be
  able to edit the node.

  -- 'Publish content only from assigned domain'
  Before being presented the editing form, users will be taken to the
  first domain assigned to their user account.  This function is most useful
  when you users are only allowed to enter content from a single domain.

  Note that for users who have more than one assigned domain, this option
  will take them to the first match and the user will not be allowed to
  change the domain affiliation.

  The advantage of this option is the user cannot modify the URL of a
  content edit form to match the URL of other domains, forcing all of her
  posts to be made to a single domain. Users trying to enter content
  from another domain will always be transferred to their assigned domain.

  In effect, a user assigned to 'one.example.com' will only be able to post
  to that domain, even if she clicks Create Content from two.example.com.

  -- 'Publish content to any assigned domain'
  The node editing form is shown normally, and the user is presented a
  list of checkboxes or a multiple select list.  These options represent the
  affiliate domains that the user is allowed to publish content to, according
  to the domains assigned to their user account.

  Note that if this option is selected, users will also be shown a list of
  affiliates to which the node is  assigned.  This list shows only the
  affiliates that the user cannot edit.

  Warning: If this option is selected and the user has no domain publishing
  options, the user will not be allowed to post or edit!

  NOTE: Users who are assigned _none_ of these permissions and cannot
  'Set domain access status for all content' will have the default form values
  passed as hidden fields. This setting is the default option.  It will assign
  all content to the domain from which the form is entered.

Note also that the user is not given the ability to promote content to
'all affiliates'.  Users who need this ability should be given the 'set domain
access' permission instead.

This feature was added in response to http://drupal.org/node/188275.

----
3.2 Normal Usage

Under a normal Drupal site, a single administrator (or a handful of equally
trusted administrators) typically have the 'Bypass content access control'
permission and individual 'TYPE: edit all content' permissions.

The only choices for permissions would be who gets to administer the module
settings and who gets to assign nodes to specific domains.  Generally, only
users who you trust to 'administer site configuration' should be given the
'Administer domain records and settings' permission.  As for 'set domain
access,' that can be given to any user you trust to use the UI properly.

----
3.3 Advanced Usage

In the event that you wish to segregate which content certain editors can
control, you should not use the normal 'edit any TYPE nodes' and 'delete any
TYPE nodes' permissions provided by Drupal's core Node module.
These permissions grant the ability for a user to edit and delete all nodes of a
given type.

In the Domain Access model, these permissions are not used in favor of the
provided 'Edit any content on assigned domains' and 'Delete any content on
assigned domains' permissions.  These permissions  allow editors only to edit
(and delete) nodes that belong to their domain.

To enable this feature, you should grant the 'Edit any content on assigned
domains' and (optionally) the 'Delete any content on assigned domains'
permission to some roles. Then assign individual users accounts to specific
domains to assign them as Domain Editors.

NOTE: Users with the 'Delete any content on assigned domains' permission must
also be given the 'Edit any content on assigned domains' permission in order to
delete content due to the location of the delete form in Drupal.

----
3.4 Limitations

Due to the way node_access() works, the following limitations should be noted.

  - Any node that is assigned to more than one domain can be edited
    by any editor who belongs to one of the domains.

  - Users who look at the sites and have the 'Bypass content access control'
    permission can always see all content on all sites, which can be confusing.
    To enforce Domain Access rules on these users, you may enable the
    'Enforce rules on administrators' setting described in 4.3.3.

  - Users who have the 'edit any TYPE nodes' permission will be able to edit
    nodes that do not belong to their domain.

These limitations are due to the permissive nature of node_access().  If any
access rule grants you permission, it cannot be taken away.

----
4.  Module Configuration

The settings for Domain Access are listed under Site Building.  The path is
'admin/build/domain'.

----
4.1   Default Domain Settings

These elements define the 'primary' domain for your site.  In the event that a
user tries to access an invalid domain, this domain will be used.

----
4.1.1   Primary Domain Name

The primary domain for your site. Typically example.com or www.example.com.
Do not use http or slashes. This domain will be used as the default URL for your
site.  If an invalid domain is requested, users will be sent to the primary
domain.

Enter the primary domain for your site here.  Typically, you will also enter
this value into settings.php for cookie handling.  Do not use http:// or a
trailing slash when entering this value.

NOTE: If you have installed Drupal in a subfolder, such as
http://example.com/drupal you should not include the folder path
as part of the primary domain.  Simply use example.com -- Drupal
will automatically detect the presence of the subfolder.

NOTE: As of 5.x.1.5 and higher, you may use a port protocol as part
of any domain.  So you could set example.com:8080 as the primary
domain name.  Note that port protocols will not be stripped, so that
example.com and example.com:8080 are two separate domains.

----
4.1.2   Site Name

This value is taken from your system settings and need not be changed.  It is
provided to allow readability in the domain list.

----
4.1.3   Domain URL Scheme

Allows the site to use 'http' or 'https' as the URL scheme.  Default is
'http'.  All links and redirects to root site will use the selected scheme.

----
4.2 Creating domain records

As noted above, this screen does not register DNS records with Apache.

Use this screen to register new allowed domains with your site.  This
process is especially important for sites using Wildcard DNS, as it prevents
non-registered sites from resolving.

The first domain will use the HTTP_HOST value of the request made
when installing the module. This value may be edited by going to
Admin > Structure > Domains and editing the Primary Domain value.

The second domain will be given the value test.example.com, where
example.com is the Primary Domain value. This domain is set to be
'inactive' initially. You will need to edit this domain record in order to
use it.

When you create a new domain record, simply fill in the form:

  - Domain
  This is the full path.example.com, without http:// or a trailing slash.

  - Site name
  This is the name of the site that will be shown when users access this site.

  -- Domain URL scheme
  Allows the domain to use 'http' or 'https' as the URL scheme.  Default is
  'http'.  All links and redirects to the domain will use the selected scheme.

  -- Domain status
  By default, all domains are Active and anyone can navigate to them. Setting
  a domain to Inactive restricts access to users with the 'access inactive
  domains' permission. This feature is useful for staging content and testing
  new domain configurations.

  NOTE: Users who try to access an inactive domain will have the attempt
  reported in the site logs. When this occurs, the module will try to redirect
  the user to the appropriate content on an active domain. If no match is
  found, the user is send to the default home page.

Both the Domain and the Site name are required to be unique values.

After you create a record, you may edit or delete it as you see fit.

NOTE: As a result of module installation, you will never have a Domain with
the domain_id of 1 if you did not use Domain Access prior to 6.x.2.0. This
is by design and will not affect the module.

NOTE: When editing a domain record, Domain Access runs an http request
to see if the domain is responding properly. This test checks for the presence
of the file '200.png' inside the module's 'test' directory.

If a 200 "found" reply is not returned, you will see an message warning you
that your DNS may not be configured properly.  This message is intended
to help you debug your DNS configuration and may be safely ignored.

NOTE: Users who attempt to access an unregistered domain will be
redirected to the primary domain automatically.

----
4.2.1  Restricted Characters in Domains

When creating a domain record, you are restricted to the valid character set
for Internet domain names.  By design, this includes only the ASCII
alphanumeric character set (a-z 0-9) plus the special characters dot (.)
dash (-) and colon (:). A colon may only be followed by a port number.

Domains must be lowercase. Domain matching with HTTP_HOST is not
case-sensitive.

With the advent of Internationalized Domain Names (IDNs), domain servers
are beginning to recognize non-ASCII domain names. To enable support for
non-ASCII domain names, you must add the following lines to the bottom
of your settings.php file:

  // Allow registration of non-ASCII domain strings.
  $conf['domain_allow_non_ascii'] = TRUE;

For background, see the following:

  http://tools.ietf.org/html/rfc819
  http://tools.ietf.org/html/rfc1035
  http://en.wikipedia.org/wiki/Internationalized_domain_name
  http://blog.icann.org/2010/05/idn-cctlds/

----
4.2.2   Altering Domain Name Validation

If you wish to enforce special business rules for domain name validation,
you may implement hook_domain_validate_alter() in your module.

This hook will allow your module to intercept and alter any errors found
by the normal domain validation process.  See domain.api.php for details.

----
4.3   Domain Module Behaviors

These options affect the basic options for how the module behaves.

----
4.3.1   New Content Settings

Defines the default behavior for content added to your site.  By design, the
module automatically assigns all content to the currently active domain.
If this value is set to 'Show on all sites,' then all new content will be
assigned to all sites _in addition to_ the active domain.

If you set this value to 'Only show on selected sites,' you will be shown
configuration options per node type, as described in 4.3.1.1.

----
4.3.1.1   Send to All Affiliates

This setting presents a list of all active node types on your site.  By
checking the box, nodes for that given type will automatically be assigned to
'all affiliate sites' during node creation and editing.

This setting is only used if the New Content Settings are set to "Only
show on selected sites."

----
4.3.2   Debugging Status

If enabled, this will append node access information to the bottom of each
node.  This data is only viewable by uses with the 'Set domain access status for
all content' privilege.  It is provided for debugging, since 'administer nodes'
will make all nodes viewable to some users.

----
4.3.3   Enforce Rules on Administrators

When using Node Access modules, user 1 (the super-admin) and users with
the 'Bypass content access control' permission are not subject to node access
rules. This is a design feature of Drupal, and it can lead to confusion when
viewing your site as an administrator.

To help with this confusion, the 'Enforce rules on administrators' setting can
be enabled. This setting forces Domain Access rules to be applied _even to
users who can Bypass content access control_.

The default setting is OFF, but if you regularly login as user 1 or a user with
the 'Bypass content access control' permission, you may want to enable this
feature.

NOTE: This feature _only_ applies Domain Access rules. if you are using
multiple node access modules, not all rules will be applied.

----
4.3.4   Domain List Size

Sets a break point for the size of domain lists shown to users. If you have a
large number of domains (e.g. more than 20), you may set this value to
allow for pagination and truncation of domain lists.

----
4.3.5   Display in Vertical Tabs

When set to 'Yes', places the Domain options in a vertical tab on the node
editing form.

----
4.3.6   Domain Selection Format

Controls the form element display when choosing a list of domains. By
default, Domain Access shows checkboxes, but if your site has a large
number of domains, checkboxes hinder usability. You may use this setting
to force domain lists to be displayed as multiple select lists instead.

By default, if you have more than 25 domains, a select list will be used
for your forms, but you may use this setting to alter that behavior.

----
4.4   Advanced Settings

These settings control advanced features for the module.  Some of these
features require patches to Drupal core.  Please read the documentation
carefully before implementing these features.

NOTE: Some of these options may be disabled in the event that patches
have not been applied.

By default, these features are all disabled.

----
4.4.1   Search Settings

Allows the admin to decide if content searches should be run across all
affiliates or just the currently active domain.  By design, Drupal will only
find matches for the current domain.

----
4.4.2   Search Engine Optimization

There is a risk with these modules that your site could be penalized by search
engines such as Google for having duplicate content.  This setting controls the
behavior of URLs written for nodes on your affiliated sites.

    - If SEO settings are turned on, all node links are rewritten as absolute
      URLs.
    - If assigned to 'all affiliates' the node link goes to the 'default source
      domain' defined in 4.4.3.  Normally. this is your primary domain.
    - If assigned to a single affiliate, the node link goes to that affiliate.
    - If assigned to multiple affiliates, the node link goes to the first
      matching domain.
      (Determined by the order in which domains were created, with your primary
      domain matched first.)

The optional Domain Source module (included in the download) allows you to
assign the link to specific domains.

----
4.4.3   Default Source Domain

This setting allows you to control the domain to use when rewriting links that
are sent to 'all affiliates.'  Simply select the domain that you wish to use as
the primary domain for URL rewrites.

NOTE: This option only fires if you enable SEO rewrites or use the provided
Domain Source module.

By default this value is your primary domain.

----
4.4.4   WWW Prefix Handling

This setting controls how requests to www.example.com are treated with
respect to example.com.  The default behavior is to process all host names
against the registered domain list.

If you set this value to 'Treat www.*.example.com as an
alias of *.example.com' then all host requests will have the 'www.' string
stripped before the domain lookup is processed.

Users going to a www.one.example.com in this case will not automatically
be sent to one.example.com, but your Drupal site will behave as if they
had requested one.example.com.

This feature was requested by Rick and Matt at DZone.com

----
4.5   Special Page Requests

For this feature to work, you must follow the instructions in INSTALL.txt
regarding custom_url_rewrite_outbound().  If you have not followed the
instructions, you should see a warning at the top of the Admin > Structure >
Domains page.

In normal uses, such as the default home page, you want to restrict access
to content based on the active domain.  However, in certain cases, this
behavior is not desired.

Take the Track page for each user, for example.  The Track page is at
'user/UID/track' and shows a list of all posts by that user.  By design, this
page may show different results if seen from different domains:

  -- one.example.com/user/1/track
  Shows all posts by user 1 assigned to the domain one.example.com

  -- two.example.com/user/1/track
  Shows all posts by user 1 assigned to the domain two.example.com

The behavior we really want is to show ALL posts by the user regardless of
the active domain.

The Special Page Requests setting lets you specify Drupal paths for which
this behavior is active.  These paths are entered in the same way as block
settings for page visibility.

Some sample pages that might require this setting.  Note, some of these
are contributed modules:

  -- user/*/track
  -- blog/* -- the user blog page
  -- mysite/* -- the MySite module
  -- popular/alltime -- a View page
  -- popular/latest -- a View page
  -- taxonomy/term/*  -- to show all taxonomy terms at all times
  -- taxonomy/term/10 -- to show only term 10 at all times
  -- taxonomy/term/*/feed/* -- all taxonomy term feeds

Default and custom Views are often good candidates here as well.

By default, 'user/*/track' is in this list.

The logic for how these links are written is documented in 4.4.2 Search Engine
Optimization.

Note that the 'search' path is handled separately and need not be added here.

----
4.5.1  Cron Handling

When Drupal's cron function runs, it runs on a specific domain.  This forces
Domain Access to invoke its access control rules, which may not be desired.

In most use cases, you will want Domain Access to allow access to all nodes
during cron runs.  For modules such as Subscriptions, this behavior is
required unless all your content is assigned to "all affiliates."

To reflect this, Domain Access provides a configuration option labeled:

  [x] Treat cron.php as a special page request

This option is turned on by default.  In almost all cases, you should leave
this option checked.  Doing so allows Domain Access to ignore access checks
for nodes when cron runs.

Note that this does not affect node access permissions set by other modules.

----
4.5.2   XMLRPC Handling

Similar to the above, you may check this option to disable Domain Access
rules when Drupal is invoked using XMLRPC.

----
4.6   Node Link Patterns

When using this module, there are times when Domain Access will need to
rewrite a node link using custom_url_rewrite_outbound().

Since Drupal is an extensible system, we cannot account for all possible
links to specific nodes.  Node Link Patterns are designed to allow you to
extend the module as you add new contributed modules.

By default, the following core link paths will be rewritten as needed.

  -- node/%n
  -- comment/reply/%n
  -- node/add/book/parent/%n
  -- book/export/html/%n
  -- node/%n/outline

Where %n is a placeholder for the node id.

If you install additional modules such as Forward
  (http://drupal.org/project/forward)
or Print
  (http://drupal.org/project/print)
you will want to add their paths to this list:

  -- forward/%n
  -- print/%n

This is an advanced, but necessary feature.  Please report any core node path
omissions at http://drupal.org/project/issues/domain.

----
4.7 Domain List

This screen shows all active domains registered for use with the site.

From this screen, you may set the default domain, activate or inactivate
domains or view to the settings for individual domains.

----
4.8   Batch Updating

The module provides for batch actions for common tasks.  These actions are
useful for making rapid changes across all domains.  The following actions
are available by default.

  - Edit all domain values
  - Edit all site names
  - Edit all URL schemes
  - Edit all domain status flags

Additional batch actions are made available for the Domain Configuration
module.  Other modules may implement hook_domain_batch() to provide
additional batch actions.

It may be necessary to enter the batch form from the primary domain.

For some settings, you may see an 'Update value for all domains' section
of the form. You may use this value to reset all domains to the same
setting. This option is not available for settings that must be unique
per domain, such as the domain string.

For global settings to apply, you must check the 'Apply to all domains'
box before submitting the form.

----
4.9  Assigning Users to Domains

New in 6.x.2 is the concept of 'user defaults.' These settings are used to
assign users to domains based on the user's site roles.

Click on the 'User defaults' tab to see the settings available.

By design, these settings are always added to a user's domains when a page
is requested. That is, if you assign all 'authenticated users' to your first
domain, one.example.com, then all authenticated users will be assigned to that
domain.

This setting is most useful under the following conditions:

  -- If you let anonymous users post content on your site. In this case, you
  should assign at least one domain to the anonymous user role, so that
  the module will assign anonymous posts to the appropriate domain(s).

  -- If you use Domain Strict, you can use this setting to assign default
  access to specific roles.

Note that there are two options for how this setting behaves:

  -- Add default roles dynamically [default]
  This setting is the normal use and does not write individual records to the
  {domain_editor} table. Use this setting if you want to change options for
  each role quickly, as these are global settings, so taking away a domain
  will instantly apply to all users.

  -- Add default roles to the user account
  Use this setting if you want to automatically register users to specific
  domains or to save changes to a batch of users. When this setting is
  active, domain assignments are saved permanently to the {domain_editor}
  table and can only be removed by editing the user account.

You may also assign default domains for all new users of your site. To do
so, simply select the domains that new users should be assigned to. If you
make no selection, new users will automatically be assigned to the domain
from which they enter the registration form.

Settings for the 'new user' are permanently saved to the user account.

See http://drupal.org/node/313629 for some background about this feature.

----
4.10 Batch Assignment of Users to Domains

In 6.x.2 and higher, users with the 'administer users' and 'assign domain
editors' permissions may use the User administration page to batch assign
users to domains.

This feature is useful if you need to convert a group of editorial users to
become domain editors.

To use this feature, navigate to Administer > User management > Users.
Look for the 'Assign users to domains' option in the 'Update options' select
form. Choose this operation and then use the 'Affiliate editor options'
fieldset to select the domains you wish to assign users to.

Select the desired users and hit the Update button.

Note that this form also shows you a list of domains that a user is
currently assigned to.

If these elements do not appear, you do not have the proper permissions.

----
4.10.1 Form Behavior

In 6.x.2.5 and higher, you may select one of two options when updating domains.

Under the 'Update behavior' form element, you may choose:

  [] Replace old values with new settings
  [] Add new settings to existing values
  [] Remove selected domains from existing values

Choosing 'replace' will erase any current domain affiliation for the selected
users and replace them with those entered into the form. Choosing 'add' will
merge the new values with the existing values. Choosing 'remove' will remove the
new values from the existing ones.

This new feature is helpful when you want to alter domain settings, but do not
want all users to be assigned to the same domains.

----
5.  Blocks

The Domain Access module provides two blocks, which can be used to help you
debug your use of the module.

----
5.1 Block -- Domain Switcher

The Domain Switcher block presents a list of all active domains.  Clicking
on one of the links will take you from your current URL to the same URL on
the selected domain.

For example, if you are looking at example.com/?q=node and click on another
domain, the link will take you to one.example.com/?q=node.

In the Domain Switcher block, domains are listed using their human-readable
sitename variables.

NOTE: This block is for debugging purposes.  The included Domain Navigation
module provides block and menu items intended for end users.

----
5.2 Block -- Domain Access Information

The Domain Access Information block lets you view node access rules for any
node when you are viewing that node.  This block can help you debug the
module for user accounts that do not have the 'Set domain access status for all content' permission.

NOTE: By design, this block is viewable by all users.  However, its content
should only be shown to site developers or during debugging.  You should use
the normal block visibility settings as appropriate to your site.

----
6.  Node Access

The Domain Access module is a node_access() module.  For additional developer
information, see http://api.drupal.org/api/group/node_access/5.

By design, the module sets access to content based on the current domain that
a user is viewing.  If a user is at one.example.com, they can see content that
is assigned to that domain or to all domains.

----
6.1   Assigning Domain Access

Users who have the 'Set domain access status for all content' permission can
assign any node to any or all registered sites.  During node editing, a series
of options will be displayed as checkboxes or a multiple select list under the
heading "Domain access options":

  Publishing options:
    []  Send to all affiliates
    Select if this content can be shown to all affiliates. This setting will
    override the options below.

  Publish to: * (required)
    [] Drupal
    [] One site
    [] Two site
    Select which affiliates can access this content.

If you select 'Send to all affiliates,' the node will be viewable on all domains
for your site.  Even if you select this option, you must select at least one
domain for the node.

If you do not select at least one option, the module will automatically
assign the node to your default domain.

When creating new content, the currently active domain will be selected for you.

For users who do not have the 'Set domain access status for all content'
permission, the assignment will be done through a hidden form element.  The node
will be assigned to the currently active domain or, if configured, to all
domains.

----
6.2.  Editor Access

Whenever a user account is created and the Domain Access module is active, user
accounts will automatically be tagged with the name of the active domain from
which they registered their account.  Users with the 'Set domain access status
for all content' permission may assign individual users to specific domains in
the same way that nodes can be defined.

These user settings are used to determine what domains an editor belongs to.
Users with the 'Edit any content on assigned domains' permission can edit any
node that belongs to the same domain that the user does.  (Remember that users
and nodes can both belong to multiple domains.)  However, nodes that are
assigned to 'all affiliates' do not grant editing privileges to all editors.

----
6.3   Realms

This section contains technical details about Drupal's node access system.

In Domain Access, the following realms are defined:

  - domain_all
  Indicates whether the view grant should be passed for all nodes on
  a given page request.  Used in cases such as Search and MySite to
  enable aggregation of content across affiliates.  The only valid nid
  and gid for this grant are zero (0).

  - domain_site
  Indicates whether a node is assigned to all affiliates.  The only valid
  grant id for this realm is zero (0).

  - domain_id
  Indicates that a node belongs to one or more registered domains.  The
  domain_id key is taken from the {domain} table and is unique.

----
6.4   Grants

In each of the realms, there are specific rules for node access grants, as
follows.

  - domain_all
  In some specific cases, like Search or the user's Tracker page we want people
  to be able to see content across all affiliates.  Only the domain_all grant is
  assigned in these cases.  This grants only 'grant_view'.

  - domain_site
  By design, all site users, including anonymous users, are granted access to
  the gid '0' for realm 'domain_site'.  This grant allows all users to see
  content assigned to 'all affiliates'.  This grants 'grant_view' to all users.
  Users who belong to the current domain and are assigned the
  'Edit any content on assigned domains' or 'Delete any content on assigned
  domains' permissions will be given 'update' and 'delete' grants, respectively.

  - domain_id
  When a user, including anonymous users, views a page, the active domain is
  identified by the registered domain_id.  For that page view, the user is
  granted gid of the active domain_id for the realm 'domain_id'.  This allows
  content to be partitioned to one or many affiliates.  This grants only
  'grant_view', since 'grant_edit' would allow content to appear to some users
  regardless of the active domain.

----
6.5   Warnings

Node access in Drupal is a permissive system.  Once a grant has been issued, it
cannot be revoked.  As a result, it is possible for multiple editors to be able
to edit or delete a single node.  Here's the use case:

  - Node 10 (a book page) is assigned to one.example.com and three.example.com
  - User A is an editor for one.example.com.
  - User B is an editor for two.example.com
  - User C is an editor for three.example.com

Under this scenario, User A and User C will be able to edit node 10.

To be more clear about Drupal permissions:

  - User D has 'Bypass content access control' permission for the site.
  - User E has the 'Book page: edit all content' permission for the site.

In this case, User D and User E can also edit or delete node 10. This is why
only super-admins are given 'Bypass content access control' and 'TYPE: edit all
content' permissions with the Domain Access module.  If you want your affiliate
editors to have limited permissions, only grant them 'Edit any content on
assigned domains'.

However, you still need to give users the 'TYPE: Create new content' permission
normally.  Domain Access does not affect node creation.

Since Domain Access implements node_access() fully, if you uninstall the module
-- using Drupal's uninstall sequence -- all node_access entries should be reset
to grant 'grant_view' to realm 'all' with gid '0'.

----
7.  Developer Notes

The Domain Access module is meant to be the core module for a system of small
modules which add functionality.

----
7.1  Extension Modules

Currently, the following modules are included in the download.  They are not
required, but each adds functionality to the core module.

  - Domain Alias -- Allows advanced handling of domain name matching.  Use
  this module to treat multiple domains as though they were identical.

  - Domain Configuration -- Allows you to change select system variables for
  each domain, such as offline status, footer message and default home
  page.

  - Domain Content -- Provides a content administration page for each domain,
  so that affiliate editors can administer content for their section of the
  site.

  - Domain Navigation -- Supplies a navigation block with three themes. Creates
  menu items for each domain, suitable for using as primary or secondary
  links.

  - Domain Prefix -- A powerful module that allows for selective table prefixing
  for each domain in your installation.

  - Domain Source -- Allows editors to specify a primary "source" domain to be
  used when linking to content from another domain.

  - Domain Strict -- Forces users to be assigned to a domain in order to view
  content on that domain.  Note that anonymous users may only see content
  assigned to "all affiliates" if this module is enabled.

  - Domain Theme -- Allows separate themes for each domain.

  - Domain User -- Allows the creation of specific subdomains for each active
  site user.

  - Domain Views -- Provides a Views filter for the Domain Access module.

----
7.2 The $_domain Global

NOTE: In Drupal 7, this value is deprecated. You should use domain_get_domain()
to return the active domain.

During hook_init(), the Domain Access module creates a new global variable,
$_domain, which can be used by other Drupal elements (themes, blocks, modules).

The $_domain global is an array of data taken from the {domain} table for the
currently active domain. If no active domain is found, default values are used.

The default domain is marked in the {domain} table 'is_default' column.

Some uses for this global variable might include:

  - Block placement based on active domain (using PHP for block visibility).
  - Ad tags inserted based on active domain.
  - Theme switching based on domain.

The 'error' element is new in 6.x.2 and is used to signal installation problems.
Normally the 'error' element should not be set. See the API documentation of
hook_domain_bootstrap_full() for details.

----
7.3 Database Schema

The Domain Access module creates two tables in a Drupal installation.  {domain}
contains the following structure:

  - domain_id
  Integer, unique, auto-incrementing.
  The primary key for all domain records.

  - subdomain
  Varchar, 80, unique (enforced by code)
  'Domain' is a sql-reserved word, so subdomain is used.  This value must match
  the url 'host' string derived from parse_url() on the current page request.

  - sitename
  Varchar, 80, unique (enforced by code)
  The name for this affiliate, used for readability.

  - scheme
  Varchar, 8 default 'http'
  Indicates the URL scheme to use when accessing this domain.  Allowed values,
  are currently 'http' and 'https'.

  - valid
  Char, 1 default 1
  Indicates that this domain is active and can be accessed by site users.

  - weight
  Integer, default 0
  The sort value for the domain. Signed integer, with lower being higher in a
  list.

  - is_default
  Char, 1 default 0
  Indicates that this domain is the default.

The {domain_access} table is a partial mirror of the {node_access} table and
stores information specific to Domain Access.  Its structure is:

  - nid
  Integer, unsigned NOT NULL default '0,

  - gid
  Integer, unsigned NOT NULL default '0'

  - realm
  Varchar, 255 NOT NULL default ''

The {domain_editor} table stores information about which users can edit and
delete content on specific domains.  Its structure is:

  - uid
  Integer, unsigned NOT NULL default '0,
  A foreign key to the {users} table.

  - domain_id
  Integer, unsigned NOT NULL default '0'
  A foreign key to the {domain} table.
----
7.4 API

The Domain Access module has an API for internal module hooks.  Documentation is
included in the download as domain.api.php and can be viewed online at:

  http://therickards.com/api

The most important developer functions are the internal module hooks:

  http://therickards.com/api/group/hooks/Domain

----
7.5   Domain Tokens

The module provides the following replacement tokens.

  'current-domain:id'
    The current domain ID.
  'current-domain:name'
    The current domain name, lower-cased and with only alphanumeric characters.
  'current-domain:url'
    The current domain's URL, lower-cased and with only alphanumeric characters.
  'current-domain:subdomain'
    The current subdomain, lower-cased and with only alphanumeric characters.
    Only works with *.example.com formats
  'default-domain:id'
    The default domain ID.
  'default-domain:name'
    The default domain name, lower-cased and with only alphanumeric characters.
  'default-domain:url'
    The default domain\'s URL, lower-cased and with only alphanumeric characters.
  'default-domain:subdomain'
    The default subdomain, lower-cased and with only alphanumeric characters.
    Only works with *.example.com formats

----
8.  Drush commands

Domain Access supports Drush version 3.x. The following commands are available.
Type 'drush help' for more information.

  'drush domain-list'
    Shows a table of the domains registered for your site. You may use 'drush
    domains' as a shortcut command.

  'drush domain-add DOMAIN SITENAME --options'
    Add a new domain to your site. The DOMAIN parameter is required and must be
    unique and validly formed (e.g. example.com). Possible options are:
      --inactive=1/0
      Set the domain to inactive by passing 1. Default is 0.
      --https=1/0
      Set the domain to use https instead of http by passing 1. Default is 0.
      --weight=X
      Set the weight of the domain to an integer value. Default is 0.
      --is_default=1/0
      Set the domain as the default domain.

    Sample command:

      drush domain-add example.com 'My New Site' --https=1

    Will create the domain:
      sitename: My New Site
      subdomain: example.com
      valid: yes
      scheme: https://
      weight: 0
      is_default: 0

  'drush generate-domains BASE_DOMAIN --count=15'
    Auto-generate a set of domains for testing. Aliased to 'drush gend'. Will use
    the provided BASE_DOMAIN as the primary domain, defaulting to 'example.com'.

    This command creates domains in the format *.BASE_DOMAIN. The BASE_DOMAIN
    must be properly formed. By default, the command will create 15 new domains,
    but you may specify the number using --count=X.

    The domains created are the words one through ten (1-10), foo, bar, baz, and
    the non-matching domain 'myBASE_DOMAIN', which is used for cookie testing.
    Site names are simple uppercase versions of the 3rd-level domain element.
    All domains are set to use http:// and are set as valid. Weighting is
    auto-incremented by creation order.

    Creating more than 15 domains will begin incrementing domains with numeric
    3rd-level elements, such as 20.BASE_DOMAIN.

    The purpose of this command is to help me in UX testing, since many aspects
    of the user interface must change to accommodate large numbers of domains.
    The defaults are optimized for my development environment, and will not be
    altered.

    Sample command:

      drush gend example.com --count=20

  'drush domain-delete DOMAIN|DOMAIN_ID'
    Deletes a domain record, unless it is set as the primary domain.
    You may pass either the domain string (e.g. example.com) or the domain_id
    as an argument.

    Sample command:

      drush domain-delete 3
      drush domain-delete three.example.com

  'drush domain-test DOMAIN|DOMAIN_ID'
    Checks for a valid HTTP response from the specified domain. This test is
    used when trying to set default domains, since the default domain must
    always resolve.

    If you do not pass a domain string or domain_id, all domains will be
    tested. Note that if you run Drupal in a subdirectory, you must pass a
    --uri value with this command.

    Sample command:

      drush domain-test 3
      drush domain-test three.example.com
      drush domain-test three.example.com --uri=http://example.com/subdir/

  'drush domain-default DOMAIN|DOMAIN_ID'
    Sets the specified domain as the default domain. By design, this command
    will test the requested domain for a valid HTTP response. You may disable
    this check by passing --skip_check=1 to the command.

    Note that if you run Drupal in a subdirectory, you must pass a
    --uri value with this command in order to test the HTTP response.

    Sample command:

      drush domain-default 3
      drush domain-default three.example.com
      drush domain-default three.example.com --uri=http://example.com/subdir/

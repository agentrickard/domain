// $Id$

/**
 * @file
 * README file for Domain Access.
 */

Domain Access
A subdomain-based access control system.

CONTENTS
--------

1.  Introduction
1.1   Use-Case
1.2   Examples
1.3   Sponsors
1.4   Using Multiple Node Access Modules
1.5   Known Issues
1.5.1   Logging In To Multiple Domains
1.5.2   Cron Handling
1.5.3   Updating Your Site
2.  Installation
2.1   Patches to Drupal Core
2.1.1   multiple_node_access.patch
2.2   Server Configuration
2.3   Creating Subdomain Records
2.4   Setting DOMAIN_INSTALL_RULE
2.5   Setting DOMAIN_EDITOR_RULE
2.6   Setting DOMAIN_SITE_GRANT
3.  Permissons
3.1   Module Permissions
3.2   Normal Usage
3.3   Advanced Usage
3.4   Limitations
4.  Module Configuration
4.1   Default Domain Settings
4.1.1   Primary Domain Name
4.1.2   Site Name
4.1.3   Domain URL Scheme
4.2   Domain Module Behaviors
4.2.1   New Content Settings
4.2.2   Content Editing Forms
4.2.3   Debugging Status
4.2.4   Sort Domain Lists
4.3   Advanced Settings
4.3.1   Domain-based Editing Controls
4.3.2   Search Settings
4.3.3   Search Engine Optimization
4.3.4   Default Source Domain
4.3.5   WWW Prefix Handling
4.3.6   Node Access Settings
4.4   Special Page Requests
4.4.1   Cron Handling
4.5   Node Link Patterns
4.6   The Domain List
4.7   Creating Domain Records
4.8   Node Settings
4.8.1   Domain Node Editing
4.8.2   Domain Node Types
4.9   Batch Updating
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


----
1.  Introduction

The Domain Access module group is designed to run an affiliated network of sites
from a single Drupal installation.  The module thus allows you to share users,
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
subdomain that the user is viewing, rather than which group or site the user
belongs to.

Additionally, when a user creates content, that content will automatically be
assigned to the currently active subdomain unless the user has specific
privileges to be able to assign domain access.  Under advanced setups, the
ability to edit content for a specific subdomain can be segregated from the
typical Drupal privilege to 'administer nodes.'

For more information about Domain Access privileges, see section 3.

For more information about node_access(), see
http://api.drupal.org/api/group/node_access/5

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
editors have no control over which subdomains their content is published to.

----
1.2 Examples

For the original example of the module in use, see http://skirt.com/

----
1.3 Sponsors

Domain Access is sponsored by Morris DigitalWorks.
  http://morrisdigitalworks.com

----
1.4   Using Multiple Node Access Modules

Node Access is a complex issue in Drupal.  Typically, sites will only use
one node access module at a time.  In some cases, you may require
more advances acceess control rules.

Domain Access attempts to integrate with other node access modules
in two ways:

  -- First, the multiple_node_access patch allows you to configure the
      Domain Access module to use AND logic instead of OR logic when
      adding Domain Access controls to your site.
  -- Second, Domain Access does not use db_rewrite_sql in any way.
      The module lets Drupal's core node access system handle this.

As a result, there may exist conflicts between Domain Access and other
contributed modules that try to solve this issue.

Domain Access has been tested to work with the Organic Groups module,
but may require the solution in http://drupal.org/node/234087.

If you experience conflicts with other node access modules, you
should uninstall the multiple_node_access patch.  This will restore the
default Drupal behavior that your other modules are expecting.

For background, see:

  -- http://drupal.org/node/196922
  -- http://drupal.org/node/191375
  -- http://drupal.org/node/122173
  -- http://drupal.org/node/201156
  -- http://drupal.org/node/234087

----
1.5   Known Issues

There are some issues that occur when Domain Access is used outside
of its original use case.  These are probably fixable, but may not work
as you expect.  You should pay careful attention to your site behavior.

----
1.5.1   Logging In To Multiple Domains

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
other two domains cannot read that cookie.  This is an internet standard,
not a bug.

The single sign-on module is a good solution to this limitation:
  http://drupal.org/project/singlesignon

Note: See the INSTALL.txt for instructions regarding Drupal's default
cookie handling.

----
1.5.2   Cron Handling

When Drupal's cron function runs, it operates on the domain from which
the cron.php script is invoked.  That is, if you setup cron to run from:

  http://one.example.com/cron.php

In this case, Domain Access will check the access settings for that domain.

This behavior has been known to cause issues with other contributed modules.
As a solution, we normally disable Domain Access rules when cron runs.

For more information, see section 4.4.1 Cron Handling.

If you encounter any cron-related issues, please report them at:

http://drupal.org/project/issues/domain

----
1.5.3   Updating Your Site

This issue only occurs if you use the Domain Prefix module.  It is possible
that database updates will not be applied to prefixed tables.

For more information, see the Drupal Upgrades section of the Domain Prefix
README.txt file.

----
2.  Installation

WARNING: The Domain Access module assumes that you have already installed
and configured your Drupal site.  Please do so before continuing.

For detailed instructions, see INSTALL.txt.

To install the module, simply untar the download and put it in your site's
modules directory.  After reading this document, enable the module normally.

When you enable the module, it will create a {domain} table in your Drupal
database.

All existing nodes on your site will be assigned to the default domain for your
web site and to all affiliates.  If you wish to alter this behavior, see
sections 2.4 through 2.6.

----
2.1 Patches to Drupal Core

The following patches are optional.  They affect advanced behavior of the
Domain Access module.

Patches are distributed in the 'patches' folder of the download.

To apply these patches, place them in your root Drupal folder.
Then follow the instructions at: http://drupal.org/patch/apply

----
2.1.1 multiple_node_access.patch

You should apply this patch only if you use Domain Access along with
another Node Access module, such as Organic Groups (OG).

The multiple_node_access.patch allows Drupal to run more than one
node access control scheme in parallel.  Instead of using OR logic to
determine node access, this patch uses subselects to enable AND logic
for multiple node access rules.

WARNING: This patch uses subselect statements and requires pgSQL or
MySQL 4.1 or higher.

Developers: see http://drupal.org/node/191375 for more information.

This patch is being submitted to Drupal core for version 7.

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

----
2.3 Creating Subdomain Records

After you enable the module, you will have a user interface for registering new
subdomains with your site.  For these to work correctly, they must also be
configured by your DNS server.

To be clear: creating a new subdomain record through this module will not alter
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
2.5 Setting DOMAIN_EDITOR_RULE

This is an advanced instruction, and may be ignored.

At the top of the domain.module file, you will find this line:

  define('DOMAIN_EDITOR_RULE', FALSE);

This setting controls the default behavior for affiliate editors.  If
DOMAIN_INSTALL_RULE is set to FALSE, you may change this value to TRUE if you
intend to use editing controls.

If this is set to TRUE, all existing nodes on your site will be editable by
users who are assigned as editors of your root domain.

See section 3 and section 5 for more information.

----
2.6 Setting DOMAIN_SITE_GRANT

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
3.  Permissions

After enabling the module, go to Access Control to configure the module's
permissions.

----
3.1 Module Permissions

The Domain Access module has three standard permissions.

  - 'administer domains'
  This permission allows users to create and manage subdomain records
  and settings.

  'assign domain editors'
  This permission allows users to assign themselves and other users as
  affiliate editors.  For those users to act as editors, their role(s) must also
  have the 'edit domain nodes' permission.

  - 'edit domain nodes'
  This permission is for advanced use and substitutes for the normal
  'administer nodes' permission for sites that give restricted administrative
  privileges.  See section 3.3 for more information.

  - 'set domain access'
  This permission is key.  Users with this permission will be given a user
  interface for assigning users and nodes to specific domains.  Users without
  this permission cannot assign domain access; their nodes will automatically
  be assigned to the currently active domain.

  For example, if a user has this permission and creates a book page on
  one.example.com, the user will be given a series of options to assign that
  book page to any or all of the registered domains on the site.

  If the user does not have this permission, the book page will only be shown
  to users who are on http://one.example.com.

  - 'view domain publishing'
  This permission provides a limited set of options for users to create and
  edit content on your site.  Users who have this permission will have their
  node editing forms processed according to the "Content Editing Form"
  settings described in section 4.2.2.

  This feature was added in response to http://drupal.org/node/188275.

----
3.2 Normal Usage

Under a normal Drupal site, a single administrator (or a handful of equally
trusted administrators) typically have the 'administer nodes' permission and
individual 'edit TYPE nodes' permissions.

If your site follows this method, you will not need to enable the advanced
editing controls provided by Domain Access.  Under the module settings, leave
the setting 'Domain-based editing controls' as 'Do not use access control for
editors'.  In this case, the 'edit domain nodes' permission becomes irrelevant.

The only choices for permissions would be who gets to administer the module
settings and who gets to assign nodes to specific domains.  Generally, only
users who you trust to 'administer site configuration' should be given the
'administer domains' permission.  As for 'set domain access,' that can be given
to any user you trust to use the UI properly.

----
3.3 Advanced Usage

In the event that you wish to segregate which content certain editors can
control, you should not use the normal 'edit TYPE nodes' permission provided
by Drupal's core Node module.  This permisson grants the ability for a user
to edit all nodes of a given type.

In the Domain Access model, this permission is not used in favor of the provided
'edit domain nodes' permission.  This permission allows editors only to edit
(and delete) nodes that belong to their subdomain.

To enable this feature, you should grant the 'edit domain nodes' permission to
some roles.  Then you should enable the 'Use access control for editors' setting
under the Domain Access configuration screen.

----
3.4 Limitations

Due to the way node_access() works, the following limitations should be noted.

  - Any node that is assigned to more than one subdomain can be edited
    by any editor who belongs to one of the subdomains.

  - Users who look at the sites and have the 'administer nodes' permission
    can always see all content on all sites, which can be confusing.  This is
    unavoidable.  It is best to preview your site as an anonymous or
    authenticated user who does not have special permissions.

  - Users who have the 'edit TYPE nodes' permission will be able to edit nodes
    that do not belong to their subdomain.

These limitations are due to the permissive nature of node_access().  If any
access rule grants you permission, it cannot be taken away.

----
4.  Module Configuration

The settings for Domain Access are listed under Site Building.  The path is
'admin/build/domain'.

----
4.1   Default Domain Settings

These elements define the 'root' domain for your site.  In the event that a
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
provided to allow readbility in the domain list.

----
4.1.3   Domain URL Scheme

Allows the site to use 'http' or 'https' as the URL scheme.  Default is
'http'.  All links and redirects to root site will use the selected scheme.

----
4.2   Domain Module Behaviors

These options affect the basic options for how the module behaves.

----
4.2.1   New Content Settings

Defines the default behavior for content added to your site.  By design, the
module automatically assigns all content to the currently active subdomain.
If this value is set to 'Show on all sites,' then all new content will be
assigned to all sites _in addition to_ the active subdomain.

----
4.2.2   Content Editing Forms

Defines how to present the forms for node creation and editing to users
who do not have permission to 'set domain access' but need some control
over where their content is published.

Users with the 'view domain publishing' permission will be subject to the
rules defined below.

  -- Pass the default form values as hidden fields
  The default option.  It will assign all content to the root domain and to
  the domain from which the form is entered.

  -- Take user to the default domain
  Before being presented the editing form, users will be taken to the root
  domain.  If the node is not visible on the root domain, the user may not be
  able to edit the node.

  -- Take user to their assigned domain
  Before being presented the editing form, users will be taken to the
  first domain assigned to their user account.  This function is most useful
  when you users are only allowed to enter content from a single domain.

  Note that for users who have more than one assigned domain, this option
  will take them to the first match and the user will not be allowed to
  change the domain affiliation.

  -- Show user their publishing options
  The node editing form is shown normally, and the user is presented a
  list of checkboxes.  These options represent the affilaite domains that
  the user is allowed to publish content to, according to the domains
  assigned to their user account.

  Note that if this option is selected, users with the 'view domain publshing'
  permission will also be shown a list of affilates to which the node is
  assigned.  This list shows only the affiliates that the user cannot edit.

  Warning: If this option is selected and the user has no domain publishing
  options, the user will not be allowed to post or edit!

Note also that the user is not given the ability to promote content to
'all affiliates'.  Users who need this ability should be given the 'set domain
access' permission instead.

----
4.2.3   Debugging Status

If enabled, this will append node access information to the bottom of each
node.  This data is only viewable by uses with the 'set domain access'
privilege.  It is provided for debugging, since 'adminiseter nodes' will make
all nodes viewable to some users.

----
4.2.4   Sort Domain Lists

Both the Domain Switcher block and the Domain Nav module provide an
end-user visible list of domains.  The domain sorting settings control how
these lists are generated and presented to the user.

----
4.3   Advanced Settings

These settings control advanced features for the module.  Some of these
features require patches to Drupal core.  Please read the documentation
carefully before implementing these features.

NOTE: Some of these options may be disabled in the event that patches
have not been applied.

By default, these features are all disabled.

----
4.3.1   Domain-based Editing Controls

Uses the Domain Access module to control which editors can edit content.
See section 3.3 for a full discussion of this feature.

----
4.3.2   Search Settings

For this feature to work, you must follow the instructions in INSTALL.txt
regarding custom_url_rewrite_outbound().  If you have not followed the
instructions, you should see a warning at the top of the Admin > Build > Domains
page.

Allows the admin to decide if content searches should be run across all
affiliates or just the currently active domain.  By design, Drupal will only
find matches for the current domain.

----
4.3.3   Search Engine Optimization

For this feature to work, you must follow the instructions in INSTALL.txt
regarding custom_url_rewrite_outbound().  If you have not followed the
instructions, you should see a warning at the top of the Admin > Build > Domains
page.

There is a risk with these modules that your site could be penalized by search
engines such as Google for having duplicate content.  This setting controls the
behavior of URLs written for nodes on your affiliated sites.

    - If SEO settings are turned on, all node links are rewritten as absolute
      URLs.
    - If assigned to 'all affiliates' the node link goes to the 'default source
      domain' defined in 4.3.4.  Normally. this is your primary domain.
    - If assigned to a single affiliate, the node link goes to that affiliate.
    - If assigned to multiple affiliates, the node link goes to the first
      matching domain.
      (Determined by the order in which domains were created, with your primary
      domain matched first.)

The optional Domain Source module (included in the download) allows you to
assign the link to specific domains.

----
4.3.4   Default Source Domain

This setting allows you to control the domain to use when rewriting links that
are sent to 'all affiliates.'  Simple select the domain that you wish to use as
the primary domain for URL rewrites.

By default this value is your primary domain.

----
4.3.5   WWW Prefix Handling

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
4.3.6  Node Access Settings

This setting controls how you want Domain Access to interact with other
node access modules.

If you _are not_ using a module such as Organic Groups or Taxonomy
Access Control, this setting may be disabled.  This setting is only
required IF:

  -- You are using more than one node access control module.
  -- You want to strictly enforce access permissions by requiring
  both Domain Access and your other module to grant permission.


By design, the node access system in Drupal 5 is a permissive system.
That is, if you are using multiple node access modules, the permissions
are checked using an OR syntax.

As a result, if any node access module grants access to a node, the user
is granted access.

The included multiple_node_access.patch (discussed in 2.1.1) alters this
behavior.  The patch allows Drupal to use AND logic when running more
than one node access module.

For example, when using OG and DA, Drupal's default behavior is:

  -- Return TRUE if OG is TRUE -or- DA is TRUE.

This patch allows you to enforce the rule as:

  -- Return TRUE if OG is TRUE -and- DA is TRUE.

By design, the default behavior is to use Drupal's OR logic.

For more information, see http://drupal.org/node/191375.

Enabling this feature requires the multiple_node_access patch discussed
in 2.1.1.

----
4.4   Special Page Requests

For this feature to work, you must follow the instructions in INSTALL.txt
regarding custom_url_rewrite_outbound().  If you have not followed the
instructions, you should see a warning at the top of the Admin > Build > Domains
page.

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

The logic for how these links are written is documented in 4.3.3 Search Engine
Optimization.

Note that the 'search' path is handled separately and need not be added here.

----
4.4.1  Cron Handling

When Drupal's cron function runs, it runs on a specific domain.  This forces
Domain Access to invoke its access control rules, which may not be desired.

In most use cases, you will want Domain Access to allow access to all nodes
during cron runs.  For modules such as Subscriptions, this behavior is
required unless all your content is assigned to "all affiliates."

To reflect this, Domain Access provides a configuration option labelled:

  [x] Treat cron.php as a special page request

This option is turned on by default.  In almost all cases, you should leave
this option checked.  Doing so allows Domain Access to ignore access checks
for nodes when cron runs.

Note that this does not affect node access permissions set by other modules.

----
4.5   Node Link Patterns

When using this module, there are times when hook_url_alter() will need
to rewrite a node link.

Note that these settings are not available if the hook_url_alter() patch
is not applied.

Since Drupal is an extensible system, we cannot account for all possible
links to specific nodes.  Node Link Patterns are designed to allow you to
extend the module as you add new contributed modules.

By default, the following core link paths will be rewritten as needed if you
have installed the hook_url_alter() patch.

  -- node/%n
  -- comment/reply/%n
  -- node/add/book/parent/%n
  -- book/export/html/%n

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
4.6 Domain List

This screen shows all active subdomains registered for use with the site.

Record zero (0) is hardcoded to refer to the "root" site defined as your
Primary domain name.

----
4.7 Create domain record

As noted above, this screen does not register DNS records with Apache.

Use this screen to register new allowed subdomains with your site.  This
process is especially important for sites using Wildcard DNS, as it prevents
non-registered sites from resolving.

When you create a new domain record, simply fill in the two fields:

  - Domain
  This is the full path.example.com, without http:// or a trailing slash.

  - Site name
  This is the name of the site that will be shown when users access this site.

  -- Domain URL scheme
  Allows the domain to use 'http' or 'https' as the URL scheme.  Default is
  'http'.  All links and redirects to the domain will use the selected scheme.

Both the Domain and the Site name are required to be unique values.

After you create a record, you may edit or delete it as you see fit.

----
4.8 Node Settings

The Node settings page is divided into two parts, each with a different purpose.

----
4.8.1 Domain Node Editing

The top section 'Domain node editing' is required for those sites that use the
advanced editing techniques outlined in section 3.

For users without the 'administer nodes' permission, certain elements of the
node editing form are hidden. These settings allow the site administrator to
enable users with the 'edit domain nodes' permission to have access to those
restricted fields.

By default, 'Comment settings', 'Delete node', 'Publshing options', and 'Path
aliasing' are enabled.

----
4.8.2 Domain Node Types

The lower section 'Domain node types' is used to extend the 'New content
settings' described in 4.1.

Domain node types presents a list of all active node types on your site.  By
checking the box, nodes for that given type will automatically be assigned to
'all affiliate sites' during node creation and editing.

----
4.9   Batch Updating

The module provides for batch actions for common tasks.  These actions are
useful for making rapid changes across all domains.  The following actions
are available by default.

  - Edit all domain values
  - Edit all site names
  - Edit all URL schemes
  - Edit all domain status flags

Additional batch actions are made available for the Domain Configuration
module.  Other modules may implement hook_domainbatch() to provide
additional batch actions.

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
module for user accounts that do not have the 'set domain access' permission.

NOTE: By design, this block is viewable by all users.  However, its content
should only be shown to site developers or during debugging.  You should use
the normal block visiblity settings as appropriate to your site.

----
6.  Node Access

The Domain Access module is a node_access() module.  For additional developer
information, see http://api.drupal.org/api/group/node_access/5.

By design, the module sets access to content based on the current domain that
a user is viewing.  If a user is at one.example.com, they can see content that
is assigned to that domain or to all domains.

----
6.1   Assigning Domain Access

Users who have the 'set domain access' permission can assign any node to any or
all registered sites.  During node editing, a series of options will be
displayed as checkboxes under the heading "Domain access options":

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
for your site.  If you do not select this option, you must select at least one
domain for the node.

If you do not select at least one option, the module will automatically
assign the node to your default domain.

When creating new content, the currently active domain will be selected for you.

For users who do not have the 'set domain access' permission, the assignment
will be done through a hidden form element.  The node will be assigned to the
currently active domain or, if configured , to all domains.

----
6.2.  Editor Access

Whenever a user account is created and the Domain Access module is active, user
accounts will automatically be tagged with the name of the active domain from
which they registered their account.  Users with the 'set domain access'
permission may assign individual users to specific domains in the same way that
nodes can be defined.

These user settings are used to determine what domains an editor belongs to.
Users with the 'edit domain nodes' permission can edit any node that belongs to
the same domain that the user does.  (Remember that users and nodes can both
belong to multiple domains.)  However, nodes that are assigned to 'all
affiliates' do not grant editing privileges to all editors.

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
  Indicates whether a node is assigned to all affliaites.  The only valid
  grant id for this realm is zero (0).

  - domain_id
  Indicates that a node belongs to one or more registered domains.  The
  domain_id key is taken from the {domain} table and is unique.

  - domain_editor
  Indicates that a node can be edited or deleted by an editor for a specific
  domain.  This advanced usage is optional.

----
6.4   Grants

In each of the realms, there are specific rules for node access grants, as
follows.

  - domain_all
  In some specific cases, like Search, or MySite, or the user's Tracker page
  we want people to be able to see content across all affiliates.  Only the
  domain_all grant is assigned in these cases.  This grants only 'grant_view'.

  - domain_site
  By design, all site users, including anonymous users, are granted access to
  the gid '0' for realm 'domain_site'.  This grant allows all users to see
  content assigned to 'all affliates'.  This grants only 'grant_view'.

  - domain_id
  When a user, including anonymous users, views a page, the active domain is
  identified by the registered domain_id.  For that page view, the user is
  granted gid of the active domain_id for the realm 'domain_id'.  This allows
  content to be partitioned to one or many affilaites.  This grants only
  'grant_view', since 'grant_edit' would allow content to appear to some users
  regardless of the active domain.

  - domain_editor
  Advanced.  If used, this sets the access for users who have the 'edit domain
  nodes' permission.  This grant works like the domain_id grant, but only grants
  editors access if the node belongs to one of their assigned domains.  This
  grants both the 'grant_edit' and 'grant_delete' permission.

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

  - User D has 'administer nodes' permission for the site.
  - User E has 'edit book nodes' permission for the site.

In this case, User D and User E can also edit or delete node 10. This is why
only super-admins are given 'administer nodes' and 'edit TYPE nodes'
permissions with the Domain Access module.  If you want your affiliate editors
to have limited permissions, only grant them 'edit domain nodes'.

However, you still need to give users the 'create TYPE nodes' permission
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

  - Domain Configuration -- Allows you to change select system variables for
  each subdomain, such as files directory, offline status, footer message and
  default home page.

  - Domain Content -- Provides a content administration page for each subdomain,
  so that affiliate editors can administer content for their section of the
  site.

  - Domain Navigation -- Supplies a navigation block with three themes. Creates
  menu items for each subdomain, suitable for using as primary or secondary
  links.

  - Domain Prefix -- A powerful module that allows for selective table prefixing
  for each subdomain in your installation.

  - Domain Source -- Allows editors to specify a primary "source" domain to be
  used when linking to content from another domain.

  - Domain Strict -- Forces users to be assigned to a domain in order to view
  content on that domain.  Note that anonymous users may only see content
  assigned to "all affiliates" if this module is enabled.

  - Domain Theme -- Allows separate themes for each subdomain.

  - Domain User -- Allows the creation of specific subdomains for each active
  site user.

  - Domain Views -- Provides a Views filter for the Domain Access module.

----
7.2 The $_domain Global

During hook_init(), the Domain Access module creates a nwe global variable,
$_domain, which can be used by other Drupal elements (themes, blocks, modules).

The $_domain global is an array of data taken from the {domain} table for the
currently active domain. If no active domain is found, default values are used:

  $_domain['domain_id'] = 0;
  $_domain['sitename'] = variable_get('domain_sitename',
    variable_get('sitename', 'Drupal'))
  $_domain['subdomain'] = variable_get('domain_root', '')
  $_domain['scheme'] = 'http'
  $_domain['valid'] = TRUE
  $_domain['path'] = http://example.com

Some uses for this global variable might include:

  - Block placement based on active subdomain (using PHP for block visibility).
  - Ad tags inserted based on active subdomain.
  - Theme switching based on subdomain.

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

The {domain_access} table is a partial mirror of the {node_access} table and
stores information specific to Domain Access.  Its structure is:

  - nid
  Integer, unsigned NOT NULL default '0,

  - gid
  Integer, unsigned NOT NULL default '0'

  - realm
  Varchar, 255 NOT NULL default ''

----
7.4 API

The Domain Access module has an API for internal module hooks.  Documentation is
included in the download as API.php and can be viewed online at:

  http://therickards.com/api

The most important developer functions are the internal module hooks:

  http://therickards.com/api/group/hooks/Domain

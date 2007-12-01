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
2.  Installation
2.1   Patches to Drupal Core
2.1.1   multiple_node_access.patch
2.1.2   url_alter.patch
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
4.2.1   Debugging Status
4.2.2   New Content Settings
4.2.3   Sort Domain Lists
4.3   Advanced Settings
4.3.1   Domain-based Editing Controls
4.3.2   Search Settings
4.3.3   Search Engine Optimization
4.3.4   Node Access Settings
4.4   The Domain List
4.5   Creating Domain Records
4.6   Node Settings
4.6.1   Domain Node Editing
4.6.2   Domain Node Types
4.7   Block -- Domain Switcher
5.  Node Access
5.1   Assigning Domain Access
5.2.  Editor Access
5.3   Realms
5.4   Grants
5.5   Warnings
6.  Developer Notes
6.1   Extension Modules
6.2   The $_domain Global
6.3   Database Schema
6.4   API
7.  To Do



----
1.  Introduction

The Domain Access module group is designed to run an affiliated network of sites
from a single Drupal installation.  The module thus allows you to share users, 
content, and configurations across a group of sites such as:

  - example.com
  - one.example.com
  - two.example.com
  - my.example.com

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
2.  Installation

For detailed instructions, see INSTALL.txt.

To install the module, simply untar the download and put it in your site's
modules directory.  After reading this document, enable the module normally.

When you enable the module, it will create a {domain} table in your Drupal
database.

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
2.1.2 url_alter.patch

This patch is only needed if:

  -- You wish to allow searching of all domains from any domain.
  -- You use a content aggregation module such as MySite.
  -- You get "access denied" errors when linking to items on a 
  user's Track page.
  
This patch allows modules to edit the path to a Drupal object.  In the
above cases, some content can only be viewed from certain domains, so
we must write absolute links to that content.  

Developers: see http://drupal.org/node/189797 for more information.

This patch introduces hook_url_alter(), which is being submitted to
Drupal core for version 7.

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
sites setup in httpd.conf (and my hosts file, to allow the .test TLD):

  - ken.test => 127.0.0.1
  - one.ken.test => 127.0.0.1
  - two.ken.test => 127.0.0.1
  - three.ken.test => 127.0.0.1
  
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
all existing nodes to be viewable by all affiliate sites.

If you set this value to FALSE, existing nodes will only be visible to users on
your root domain.

For more details, see section 5.

----
2.5 Setting DOMAIN_EDITOR_RULE

This is an advanced instruction, and may be ignored.

At the top of the domain.module file, you will find this line:

  define('DOMAIN_EDITOR_RULE', FALSE);

This setting controls the default behavior for affiliate editors.  If 
DOMAIN_INSTALL_RULE is set to FALSE, you may change this value to TRUE if you
intend to use editing controls.

If this is set to TRUE and DOMAIN_INSTALL_RULE is set to FALSE, all existing 
nodes on your site will be editable by users who are assigned as editors of your
root domain.

See section 3 and section 5 for more information.

----
2.6 Setting DOMAIN_SITE_GRANT

At the top of the domain.module file, you will find this line:

  define('DOMAIN_SITE_GRANT', TRUE);

This setting controls the default behavior for viewing affiliate content.
By design, the Domain Access module allows site administrators to assign
content to 'all affiliates.'  If this value is set to TRUE, then content
assigned to all affiliates can be seen by all users on all current domains.

Normally, you will not need to edit this value.

----
3.  Permissions

After enabling the module, go to Access Control to configure the module's 
permissions.

----
3.1 Module Permissions

The Domain Access module has three standard permissions.

  - 'adminster domains'
  This permission allows users to create and manage subdomain records
  and settings.  
  
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
  
----
3.2 Normal Usage

Under a normal Drupal site, a single administrator (or a handful of equally
trusted administrators) typically have the 'administer nodes' permission and
individual 'edit TYPE nodes' permissions.

If your site follows this method, you will not need to enable the advanced
editing controls provided by Domain Access.  Under the module settings, leave 
the setting 'Domain-based editing controls' as 'Do not use access control for
editors'.  In this case, the 'edit domain nodes' permission becomes irrelevant.

The only choices for permissions would be who gets to adminster the module 
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

Enter the primary domain for your site.  Typically, you will also enter this
value into settings.php for cookie handling.  Do not use http:// or a trailing
slash when entering this value.

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
4.2.1   Debugging Status

If enabled, this will append node access information to the bottom of each   
node.  This data is only viewable by uses with the 'set domain access'
privilege.  It is provided for debugging, since 'adminiseter nodes' will make
all nodes viewable to some users.

----
4.2.2   New Content Settings

Defines the default behavior for content added to your site.  By design, the 
module automatically assigned all content to the currently active subdomain.  
If this value is set to 'Show on all sites,' then all new content will be 
assigned to all sites _in addition to_ the active subdomain.

----
4.2.3   Sort Domain Lists

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

Allows the admin to decide if content searches should be run across all 
affiliates or just the currently active domain.  By design, Drupal will only
find matches for the current domain.  

Enabling this feature requires the hook_url_alter() patch discussed in 2.1.2

----
4.3.3   Search Engine Optimization

  There is a risk with these modules that your site could be penalized by search engines
  such as Google for having duplicate content.  This setting controls the behavior of
  URLs written for nodes on your affiliated sites.
  
    - If SEO settings are turned on, all node links are rewritten as absolute URLs.
    - If assigned to 'all affiliates' the node link goes to the root domain.
    - If assigned to a single affiliate, the node link goes to that affiliate.
    - If assigned to multiple affiliates, the node link goes to the first matching domain.  

Enabling this feature requires the hook_url_alter() patch discussed in 2.1.2.

----
4.3.4   Node Access Settings

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
4.4 Domain List

This screen shows all active subdomains registered for use with the site.

Record zero (0) is hardcoded to refer to the "root" site defined as your 
Primary domain name.

----
4.5 Create domain record

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
4.6 Node Settings

The Node settings page is divided into two parts, each with a different purpose.

----
4.6.1 Domain Node Editing

The top section 'Domain node editing' is required for those sites that use the
advanced editing techniques outlined in section 3.

For users without the 'administer nodes' permission, certain elements of the 
node editing form are hidden. These settings allow the site administrator to
enable users with the 'edit domain nodes' permission to have access to those 
restricted fields.

By default, 'Comment settings', 'Delete node', 'Publshing options', and 'Path
aliasing' are enabled.  

----
4.6.2 Domain Node Types

The lower section 'Domain node types' is used to extend the 'New content 
settings' described in 4.1.

Domain node types presents a list of all active node types on your site.  By
checking the box, nodes for that given type will automatically be assigned to
'all affiliate sites' during node creation and editing.  

----
4.7 Block -- Domain Switcher

The Domain Access module provides on block, which can be used to help you
debug your use of the module.

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
5.  Node Access

The Domain Access module is a node_access() module.  For additional developer
information, see http://api.drupal.org/api/group/node_access/5.

By design, the module sets access to content based on the current domain that
a user is viewing.  If a user is at one.example.com, they can see content that
is assigned to that domain or to all domains.

----
5.1   Assigning Domain Access

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
5.2.  Editor Access

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
5.3   Realms

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
5.4   Grants

In each of the realms, there are specific rules for node access grants, as follows.

  - domain_all
  In some specific cases, like Search, or MySite, or the user's Tracker page
  we want people to be able to see content across all affiliates.  Only the domain_all
  grant is assigned in these cases.  This grants only 'grant_view'.

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
5.5   Warnings

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
6.  Developer Notes

The Domain Access module is meant to be the core module for a system of small
modules which add functionality.  

----
6.1  Extension Modules

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
  
  - Domain Theme -- Allows separate themes for each subdomain.

  - Domain User -- Allows the creation of specific subdomains for each active
  site user.

----
6.2 The $_domain Global

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
6.3 Database Schema

The Domain Access module creates one table in a Drupal installation.  It 
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
  
----
6.4 API

The Domain Access module has an API for internal module hooks.  Documentation is
included in the download as API.php and can be viewed online at:

  http://therickards.com/api

----
7. To Do

Currently, the module does not support logins across more than one top-level 
domain.  That is, it will only work for the following:

  - example.com
  - one.example.com
  - two.example.com

Even though domains are registered with fully-qualifed names, this setup will
not work, since Drupal's login cookie is domain-specific.

  - example.com
  - one.example.com
  - myexample.com [will fail login because it cannot read *.example.com cookie]

Possible soultions for this issue are welcome -- the Single SignOn module may
work, with some modification.  Solutions should be rolled as separate 
sub-modules.

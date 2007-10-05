// $Id$

Domain Access
A subdomain-based access control system.

CONTENTS
--------

1.  Introduction
1.1   Use-Case
1.2   Examples
1.3   Sponsors
2.  Installation
2.1   Server Configuration
2.2   Creating Subdomain Records
2.3   Setting DOMAIN_INSTALL_RULE
2.4   Setting DOMAIN_EDITOR_RULE
3.  Permissons
3.1   Module Permissions
3.2   Normal Usage
3.3   Advanced Usage
3.4   Limitations
4.  Module Configuration
4.1   Domain Access Options
4.2   The Domain List
4.3   Creating Domain Records
4.4   Node Settings
4.4.1   Domain node editing
4.4.2   Domain node types
4.5   Block -- Domain Switcher
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

To install the module, simply untar the download and put it in your site's
modules directory.  After reading this document, enable the module normally.

When you enable the module, it will create a {domain} table in your Drupal
database.

----
2.1 Server Configuration

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
2.2 Creating Subdomain Records

After you enable the module, you will have a user interface for registering new
subdomains with your site.  For these to work correctly, they must also be 
configured by your DNS server.

To be clear: creating a new subdomain record through this module will not alter
the DNS server of your web server.

----
2.3 Setting DOMAIN_INSTALL_RULE

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
2.4 Setting DOMAIN_EDITOR_RULE

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
4.1 Domain Access Options

This screen is split into two sections.

  1. Default domain settings
  These elements define the 'root' domain for your site.  In the event that a 
  user tries to access an invalid domain, this domain will be used.
  
  -- Primary domain name
  Enter the primary domain for your site.  Typically, you will also enter this
  value into settings.php for cookie handling.  Do not use http:// or a trailing
  slash when entering this value.
  
  -- Site name
  This value is taken from your system settings and need not be changed.  It is 
  provided to allow readbility in the domain list.
  
  2. Domain module behaviors
  These options affect how the module behaves.
  
  -- Debugging status
  If enabled, this will append node access information to the bottom of each   
  node.  This data is only viewable by uses with the 'set domain access'
  privilege.  It is provided for debugging, since 'adminiseter nodes' will make
  all nodes viewable to some users.
  
  -- Domain-based editing controls
  Uses the Domain Access module to control which editors can edit content.
  See section 3.3 for a full discussion of this feature.
  
  -- New content settings
  Defines the default behavior for content added to your site.  By design, the 
  module automatically assigned all content to the currently active subdomain.  
  If this value is set to 'Show on all sites,' then all new content will be 
  assigned to all sites _in addition to_ the active subdomain.
  
  This setting is especially useful when you restrict editorial permissions.
  
  Note that this setting can be extended through the Node settings described 
  in section 4.4.

----
4.2 Domain List

This screen shows all active subdomains registered for use with the site.

Record zero (0) is hardcoded to refer to the "root" site defined as your 
Primary domain name.

----
4.3 Create domain record

As noted above, this screen does not register DNS records with Apache.

Use this screen to register new allowed subdomains with your site.  This 
process is especially important for sites using Wildcard DNS, as it prevents 
non-registered sites from resolving.

When you create a new domain record, simply fill in the two fields:

  - Domain
  This is the full path.example.com, without http:// or a trailing slash.
  
  - Site name
  This is the name of the site that will be shown when users access this site.
  
Both the Domain and the Site name are required to be unique values.  

After you create a record, you may edit or delete it as you see fit.

----
4.4 Node settings

The Node settings page is divided into two parts, each with a different purpose.

----
4.4.1 Domain node editing

The top section 'Domain node editing' is required for those sites that use the
advanced editing techniques outlined in section 3.

For users without the 'administer nodes' permission, certain elements of the 
node editing form are hidden. These settings allow the site administrator to
enable users with the 'edit domain nodes' permission to have access to those 
restricted fields.

By default, 'Comment settings', 'Delete node', 'Publshing options', and 'Path
aliasing' are enabled.  

----
4.4.2 Domain node types

The lower section 'Domain node types' is used to extend the 'New content 
settings' described in 4.1.

Domain node types presents a list of all active node types on your site.  By
checking the box, nodes for that given type will automatically be assigned to
'all affiliate sites' during node creation and editing.  

----
4.5 Block -- Domain Switcher

The Domain Access module provides on block, which can be used to help you
debug your use of the module.

The Domain Switcher block presents a list of all active domains.  Clicking
on one of the links will take you from your current URL to the same URL on
the selected domain.

For example, if you are looking at example.com/?q=node and click on another
domain, the link will take you to one.example.com/?q=node.

In the Domain Switcher block, domains are listed using their human-readable
sitename variables.

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
-- using Drupal's uninstall sequence -- all node_access entries should be reset to grant 'grant_view' to realm 'all' with gid '0'.

----
6.  Developer Notes

The Domain Access module is meant to be the core module for a system of small
modules which add functionality.  

----
6.1  Extension Modules

Currently, the Domain Conf module is also included in the distribution.  It 
provides separate site configuration options for registered domains.

----
6.2 The $_domain Global

During hook_init(), the Domain Access module creates a nwe global variable,
$_domain, which can be used by other Drupal elements (themes, blocks, modules).

The $_domain global is an array of data taken from the {domain} table for the 
currently active domain. If no active domain is found, default values are used:

  $_domain['domain_id'] = 0;
  $_domain['sitename'] = variable_get('domain_sitename',
    variable_get('sitename', 'Drupal'));
  $_domain['subdomain'] = variable_get('domain_root', '');

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

Drupal user 'canen' is already working on Theme support, which would allow each
domain to have a separate theme and settings.

This module has not been tested with other node_access() modules, and strange 
behavior may result when used with OG and similar modules.

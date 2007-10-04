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
5.  Node Access
5.1   Assigning Domain Access
5.2.  Editor Access
5.3   Realms
5.4   Grants
5.5   Warnings
6.  To Do


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

For more information about node_access(), see
http://api.drupal.org/api/group/node_access/5

Additionally, when a user creates content, that content will automatically be
assigned to the currently active subdomain unless the user has specific 
privileges to be able to assign domain access.  Under advanced setups, the 
ability to edit content for a specific subdomain can be segregated from the 
typical Drupal privilege to 'administer nodes.'

For more information about Domain Access privileges, see section 3.

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
modules directory.

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

----
2.2 Creating Subdomain Records

After you enable the module, you will have a user interface for registering new
subdomains with your site.  For these to work correctly, they must also be 
configured by your DNS server.

To be clear: creating a new subdomain record through this module will not alter
the DNS server of your web server.

----
3.  Permissions

After enabling the module, go to Access Control to configure the module's 
permissions.

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
individual 'edit {type} nodes' permissions.

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
    unavoidable.
    
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
  These elements define the "root" domain for your site.  In the event that a 
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
  below.

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


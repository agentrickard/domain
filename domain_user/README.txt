/**
 * @file
 * README file for Domain User.
 */

Domain User
Creates unique subdomains for registered users.

CONTENTS
--------

1.  Introduction
2.  Installation
2.1   Dependencies
2.2   Domain User and User Registration
3.  Permissions
3.1   Edit Domain Nodes
4.   Configuration Options
4.1   Module Behavior
4.2   Root Domain Name
4.3   User Domain URL Scheme
4.4   User Login Behavior
4.5   Assigned User Domains
4.6   Domain Table Prefixing *
4.7   Reserved Usernames
4.8   Domain Settings Page
5.  Developer Notes
5.1   hook_user() Implementation
5.2   Domain API Hooks
5.3   Database Schema

 * Requires the Domain Prefix module.

----
1.  Introduction

The Domain User module is a node access module that allows for the automatic
creation of subdomains for your users.

By design, this module will allow user "JohnDoe" to create the following active
domain:

  johndoe.example.com

Note that all non-alphanumeric characters will be replaced with a dash, so that
the user "John Doe 222" will create the subdomain:

  john-doe-222.example.com

I recommend that you set the "Content editing forms" setting in the main Domain
module to "Take user to their assigned domain."  Doing so will force all content
that  a user creates to be assigned to his or her personal domain.

Please read the documentation for the main Domain Access module carefully before
you attempt to install and use this module.

This module is not intended for use if you do not wish to perform node access
control.  It is not suitable simply for creating subdomains for your users.

----
2.  Installation

Domain User is included in the Domain Access download.  To install,
untar the domain package and place the entire folder in your modules directory.

When you enable the module, it will create a {domain_user} table in your
Drupal database.

----
2.1  Dependencies

Domain User requires the Domain Access module be installed and active.

----
2.2  Domain User and User Registration

Because Domain User tries to create subdomains for your users, the module could
cause a conflict if you have previously created domains for your site.

For example, you have used the Domain Access module to create the following:

  -- boston.example.com
  -- newyork.example.com
  -- sydney.example.com

If a user tries to register for your site using the username "boston" or
"sydney," the Domain User module would try to create a domain that already
exists.

To prevent this issue, previously created domains also create records in the
Drupal {access} table.  This table stores the rules for creating usernames when
registering for your site.

In effect, any domain that exists places a username restriction rule into your
Drupal configuration.  You can see the list of these "Reserved Usernames" on
the Domain User settings page.

----
3.  Permissions

Domain User adds one permission to your Access Control page:

  -- 'create personal domain'
  Allows users to create a personal domain when registering or
  updating their accounts.

  -- 'create user domains'
  Allows a site administrator to create user domains on behalf
  of some other user.

Only roles that have this permission can create personal subdomains.

If you want users to be able to create subdomains when they register, you must
give this permission to the 'anonymous users' role and to the 'authenticated
users' role.

If you are assigning additional roles during account creation, you may also need
to grant the 'create personal domain' permission to those roles as well.

Users with this permission will have their personal domain created either:
on registration or on updating their account.

----
3.1   Edit Domain Nodes

By design, all users will be assigned to the Domain that they create.  This
grants them access to edit nodes posted to their domain.  To enable this
feature, a user must have the "edit domain nodes" permission granted by
the core Domain Access module.

----
4.   Configuration Options

Domain User has its own settings page at Admin > Build > Domains > User
domain settings.  The following options are available.

----
4.1   Module Behavior

Controls when (and if) user domains should be created.  The following options
are available:

  -- Do not create domains for users [Default]
  -- Automatically create domains for new users
  -- Ask users if they would like to create a domain

Note that "do not create" is the default setting.  This prevents domains from
being created before you finish configuring the module.

If you select "ask users," then a checkbox option will appear to users during
registration (or account editing) if the following conditions are true:

  -- The user has permission to create a domain.
  -- The user has not created a personal domain.

Note that currently there is no way for a user to delete their own domain.  If
a user account is blocked, access to the user domain is blocked.  If a user
account is deleted, the user domain record is also deleted.

----
4.2   Root Domain Name

The root domain to use for creating user domains, typically example.com.  No
http or slashes.

When users create domains, their username will be added to the root domain to
create a custom domain.  For example, user.example.com or administrator.example.com.

All user domains follow the pattern:

  username.example.com

Where example.com is the "Root domain name" configured here.

In theory, you may use a multi-level domain scheme here, such as:

  username.personal.example.com

When entering your root domain, you should not include the username
string.

WARNING: Your web server must be configured to recognize these domains
for them to function.  Wildcard DNS is the preferred solution for handling
user domains.

----
4.3   User Domain URL Scheme

Allows user domains to be prefixed with either http:// or https:// protocols.

Note that all user domains will be created using the same scheme.

Changing this setting will _not_ affect domains created prior to the change.

----
4.4   User Login Behavior

Controls the behavior of the module when a user with an existing personal
domain logs in to the site.  Options are:

  -- On login, go to personal domain [Default]
  -- Do not go to personal domain on login

Because of how Drupal login works, this feature uses a session variable to
trigger the redirect.

----
4.5   Assigned User Domains

Controls which domains a user is assigned to for editing purposes. The options
are:

  -- Assign only to the user domain [default]
  -- Assign to both user domain and active domain

NOTE: If a user domain is deleted, the user will be assigned to the primary
domain.

----
4.6   Domain Table Prefixing *

This setting is only available if you have the Domain Prefix module turned on.
Since Domain Prefix is a powerful module that creates extra database tables,
you have the option to selectively disable that module for user-created domains.

Options are:

  -- Never create prefixed tabled for user domains [Default]
  -- Obey the settings in Domain Prefix

Note that "obey the settings" may not create tables when the domain is created,
since Domain Prefix has its own behavior settings.

----
4.7   Reserved Usernames

At the bottom of the Domain User settings page is a list of all reserved
usernames.  This list is derived from the administrator-created list of domains.

Users should not be able to register or login with any username listed here.

----
4.8   Domain List Page

Note that on the main Domain list page at Admin > Build > Domains > Domain
list, a new column is added to the domain table.  This column shows the username
of the

----
5.  Developer Notes

This module has been tested for base functionality.  There may be edge cases
that it does not properly address.

Please file bug reports if you encounter any problems.

----
5.1   hook_user() Implementation

Domain User implements hook_user() to perform many of its functions.  The
module also will add a 'Personal web site' element to the user profile of any
user who has created a personal domain.

----
5.2   Domain API Hooks

Domain User leverages some of the internal APIs of the Domain Access module.

  -- domain_user_domainload() adds the UID to the global $_domain array and
      to all $domain lookups.

  --  domain_user_domainupdate() implements the 'delete' hook in order to
      delete records from {domain_user} in the event that the administrator
      deletes a user domain record.
  -- domain_user_domainview() adds the additional information column to the
      Domain list page.

For more information, see http://therickards.com/api/group/hooks/Domain

----
5.3   Database Schema

Installing the module creates a {domain_user} table that contains:

  - domain_id
  Integer, unique (enforced by code)
  The lookup key for this record, foreign key to the {domain} table.

  - uid
  Integer, unique (enforced by code)
  The lookup key for this record, foreign key to the {users} table.

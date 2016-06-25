/**
 * @file
 * README file for Domain Alias
 */

Domain Access: Domain Alias
Advanced domain matching methods for Domain Access.

CONTENTS
--------

1.  Introduction
1.1   Use-Case
1.2   Example
1.3   Developers
2.  Installation
2.1   Dependencies
2.2   Configuration Options
3.  Alias Management
3.1   Creating Aliases
3.2   Updating Aliases
3.3   Pattern Matching Options
3.4   Redirecting Aliases
4.  Developer Notes
4.1   Database Schema

----
1.  Introduction

The Domain Access: Domain Alias module, is an optional extension of the
Domain Access module.  Domain Alias provides advanced options
for configuring domain request handling by your site.

----
1.1 Use-Case

Some sites have very specific rules for displaying their urls to users.
For example, yahoo.com redirects all site visitors to www.yahoo.com,
whereas drupal.org redirects all requests to www.drupal.org to the
canonical url drupal.org.

Supporting these conflicting rules led to the creation of Domain Alias,
a system for managing domain handling for multiple domains that
should be treated as a single domain by the Domain Access module.

This module is useful for cases where wildcard DNS is supported, or
when you cannot modify your DNS hosts file.

----
1.2 Example

Let us assume that our main site is example.com and we run two
sub sites at users.example.com and testing.example.com.

Out sample site allows wildcard DNS, so any request to
*.example.com will be passed to our Drupal site.  We would like the
following rules to be obeyed:

-- www.example.com should redirect to example.com.
-- exmpl.com, which we also own, should be treated as a request to
    example.com.
-- *.users.example.com should inherit the settings for users.example.com.
-- *.testing.example.com should be treated as invalid and directed to
    example.com.

Under this complex scenario, we would configure the following domains:

-- Primary domains == example.com
-- Domain 1 == users.example.com
-- Domain 2 == testing.example.com

Under Domain Alias, we would then enable the following settings for each domain.

== example.com ==
exmple.com [no redirect]
www.example.com [redirect]
*.testing.example.com [redirect]

== users.example.com ==
*.users.example.com [no redirect]

== testing.example.com ==
No aliases needed.

See section 3 for more information about configuring aliases.

----
1.3 Developers

Original code by bforchhammer -- -http://drupal.org/user/216396.
See http://drupal.org/node/284422 for bacjground.

----
2.  Installation

The Domain Alias module is included in the Domain Access download.  To install,
untar the domain package and place the entire folder in your modules directory.

When you enable the module, it will create a {domain_alias} table in your Drupal
database.

----
2.1   Dependencies

Domain Alias requires the Domain Access module be installed and active.

----
3.  Alias Management

The Domain Alias module adds a new column to the Domain List table.  Go to the
Domain List at Admin > Structure > Domains (admin/structure/domain). Click on
edit domain of the desired domain. click on "aliases" tab. You should see the
aliases on this page.

----
3.1  Creating Aliases

To create a new alias, go to the Domain List at Admin > Structure > Domains
(/admin/structure/domain). click on edit domain of the desired domain. Click on
"Aliases: tab. You should see the aliases on this page.

On this page You will be presented with a form divided into two parts. The top
section, 'Registered aliases for *' will be empty initially.

Under 'Add new aliases,' you may add up to five (5) aliases at a time. (If you
need to add more, enter the first five and save the form.)

Check the 'redirect' box only if you wish to redirect requests made to the alias
to go to the registered domain for that alias.

Enter the pattern(s) that you wish to match and click 'Save aliases.'

With the advent of Internationalized Domain Names (IDNs), domain servers
are beginning to recognize non-ASCII domain names. To enable support for
non-ASCII domain names, you must add the following lines to the bottom
of your settings.php file:

  // Allow registration of non-ASCII domain strings.
  $conf['domain_allow_non_ascii'] = TRUE;

----
3.2   Updating Aliases

Once you have created a set of aliases, the 'Aliases' tab on the selected Domain
edit form(admin/structure/domain/view/[domain_id]/alias)will show the current
aliases. The top section of the form will show your current registered aliases.

To modify an alias, simply change the pattern text or toggle the redirect
option.

To delete an alias, check the 'Delete' box on the right side of the form.

Click 'Save aliases' to make your changes.

----
3.3   Pattern Matching Options

The patterns that you may enter can be simple strings, like one.example.com.

You may also use wildcard characters for advanced pattern matching.

You may specify a pattern for your domains by using * (asterisk) to match any
number of random characters and ? (question mark) to match exactly one random
character.

For example: *.example.com would match any HTTP request made to a subdomain of
example.com to the domain record for example.com.

Using wildcards is a good way to reduce the number of aliases that you need to
maintain.

NOTE: Only one wildcard is allowed per alias.

----
3.4   Redirecting Aliases

For each alias that you create, you have the option of forcing a redirect when
users make a request to that domain.  If enabled, redirects will send the user
to the registered domain.

This setting is unique to each alias.

For example, you may want to handle requests to example.com as follows:

-- Leave www.example.com alone.
-- Direct all other requests to example.com.

In this case, example.com is the 'registered domain,' and you would create the
following aliases:

-- www.example.com [no redirect]
-- *.example.com [redirect]

This feature can be used in conjunction with the Domain Access setting for WWW
Prefix Handling (see 4.3.5 WWW Prefix Handling in the main README.txt). However,
you must take care not to set up an infinite redirect loop when configuring your
aliases.

----
4.  Developer Notes

For information on the development of Domain Alias, see:

  -- http://drupal.org/node/284422
  -- http://drupal.org/node/306495
  -- http://drupal.org/node/293453


----
4.1  Database Schema

Installing the module creates a {domain_conf} table that contains:

  - alias_id
  Integer, unique
  The lookup key for the record.

  - domain_id
  Integer
  The matching key for this record, foreign key to the {domain} table.

  - pattern
  Varchar (255)
  The alias pattern to match against inbound requests.

  - redirect
  Integer (tiny)
  A boolean flag indicating that requests made to this alias should be
  redirected to the assigned domain_id.

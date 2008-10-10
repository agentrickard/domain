// $Id$

/**
 * @file
 * README file for Domain Prefix
 */

Domain Access: Table Prefixing
Dynamic table prefixing for Domain Access.

CONTENTS
--------

1.  Introduction
1.1   WARNING!
1.2   Use-Case
1.3   Example
2.  Installation
2.1   Dependencies
2.2   Configuration Options
3.  Table Prefix Options
4.  Drupal Upgrades
5.  Developer Notes
5.1   Database Schema
5.2   PostgreSQL Users

----
1.  Introduction

The Domain Access: Table Prefixing module (called Domain Prefix), is an
optional extension of the Domain Access module.  Domain Prefix provides options
for dynamically creating and selecting different database tables for affiliate sites.

The Domain Prefix module allows you to create, copy, and drop tables that are
used by a specific domain.  These tables are dynamically selected inside your
site's settings.php file.

----
1.1 WARNING!

Table prefixing is an advanced Drupal option; it should only be performed by
experienced admins or by those willing to learn how table prefixing functions.

For more information, see http://drupal.org/node/2622.

This module may cause unexpected behavior.  Test any changes to your database
carefully.

To test basic functionality, I recommend prefixing the 'watchdog' table.  Then
test Drupal's error logging on variuous domains.

THIS MODULE WILL NOT WORK CORRECTLY ON pgSQL.  See 4.2 for details.

----
1.2 Use-Case

For affiliated sites, there are times when you want to use a different
configuration or data set for each site (or for a select site).

In the original use-case, we needed to have different block settings for each
affiliate.

----
1.3 Example

To have different block settings for each affiliate, you would set the following
tables to 'copy':

  - blocks
  - blocks_roles
  - boxes

When you create a new domain, these root tables will be copied, using the
pattern:

  - domain_ID_tablename

In the Domain Prefix UI, tables are grouped by module (or by default function).
This grouping should help you decide which tables must be kept together to
ensure proper functionality.

----
2.  Installation

The Domain Prefix module is included in the Domain Access download.  To install,
untar the domain package and place the entire folder in your modules directory.

When you enable the module, it will create a {domain_prefix} table in your
Drupal database.

----
2.1   Dependencies

Domain Prefix requires the Domain Access module be installed and active.

Domain Prefix requires a change to your settings.php file, as indicated by the
directions in INSTALL.txt

----
2.2   Configuration Options

Clicking on the 'Table prefixing' tab takes you to a screen with configuration
options:

 Domain creation options: *
   [] Generate tables as defined below
   [] Do not generate any tables
   Determines what actions to take when creating new domain records.

This setting controls the behavior of newly created domain records.  If set to
'Generate', then the module will attempt to create prefixed tables as defined.

When selecting options for table prefixing, you can now select which data source
to use when copying tables.  Use the select list to determine the source for
data.

----
3.  Table Prefix Options

When using Domain Prefix, you have the following options for table creation.

  - Source
  Indicates the origin of the table structure and data.  This element defaults
  to your primary domain.  After you have prefixed tables for additional
  domains, you may choose a different source domain to use.

  - Ignore
  If selected, no table prefix actions will be taken for the specified table.

  - Create
  If selected, a prefixed table will be created for the active domain if none
  exists.  The new table will copy the schema from its designated source table.
  If a table has been created, this field will be selected by default.  When a
  table is created, no data is copied from the source table to the new table.

  - Copy
  If selected, a prefixed table will be created for the active domain if none
  exists.   The new table will copy the schema from its designated source
  table.  Data from the source table will also be copied to the new table.  If
  a table has been copied, this field will be selected by default.

  - Drop
  If a table has been created or copied, a "Drop" option appears.  Selecting
  this option will cause the selected table to be dropped (deleted) from the
  database. This action only affects the prefixed table, not its source.

  - Update
  If a table has been created or copied, an "Update" option appears.  Selecting
  this option will cause the selected table to be truncated (emptied) and the
  current data from the source table will be copied into the now-empty table.
  This action is useful for re-synchronizing tables with the primary domain.
  If a table has been updated, the "copy" option will be selected by default.

----
4. Drupal Upgrades

Running Drupal's upgrade script [update.php] respects the table prefixing provided
by Domain Prefix.  That is, if you run the script from one.example.com, it will update
tables prefixed for that domain.

However, without hacking the update script, we cannot force ths script to update tables
for all domains.  In order to update you site correctly you must follow these steps.

  - Go to update.php on your root domain [example.com].
  - Click 'run the database upgrade script'
  - Expand the 'Select versions' fieldset.
  - Make a record of each update to be performed. That is:
      - If a module indicates "no updates available", ingore it.
      - If a module indicates a number, write down the module name and the number.
  - Run the update script.
  - Check for errors.

Then you must follow these instructions for _each_ domain that uses table prefixing.

  - Go to update.php on that domain [one.example.com]
  - Click 'run the database upgrade script'
  - Expand the 'Select versions' fieldset.
  - Select the appropriate updates that you wrote down.
  - Run the update script.
  - Check for errors.

There does not appear to be an automated way of doing this process.

----
5.  Developer Notes

Some issues:

  - I have not found a way to run a function any time hook_uninstall() is run.
  Attempts to add a #submit handler using hook_form_alter() failed.  As a result
  I may have to create an admin page for uninstalling domain_prefix tables.

  - I also failed to find a way to automate the update.php process -- hook_form_alter()
  also fails to add a #submit handler in that case.

----
5.1   Database Schema

Installing the module creates a {domain_prefix} table that contains:

  - domain_id
  Integer, unique
  The lookup key for this record, foreign key to the {domain} table.

  - status
  Small integer
  The status of this row.  Values are:
      1 == No table created.
      2 == Table created, structure only.
      4 == Table created and data copied from source table.

  - tablename
  Varchar (80)
  The name of the root table -- e.g. 'cache' or 'watchdog'.

  - module
  Varchar (80)
  The name of the module that "owns" the root table.

  - source
  Small integer
  Indicates the source of data copied for this domain.  This value is
  the domain_id of the source domain.

----
5.2 PostgreSQL Users

Apologies.  This module is not fully functional on pgSQL.  Here are the
features that work, and the issues that remain.

  -- Table creation works.
  -- Table data copying works.
  -- Table dropping works.

However, the following issues make the above functions useless:

  -- Copying table definitions (such as default values, NOT NULL)
      does not work.
  -- Creating indexes does not work.
  -- Creating sequences (and copying sequence values when necessary)
      does not work.

I did research these issues and, frankly, they are delaying the release of the
module.  I would love to support these features fully, but I suspect that it
will have to wait for Drupal 6, when we can simply use the information
contained in schema files.

To contribute, see http://drupal.org/node/200279

/**
 * @file
 * README file for Domain Conf
 */

Domain Access: Site Configuration
Advanced site configuration options for Domain Access.

CONTENTS
--------

1.  Introduction
1.1   Use-Case
1.2   Example
2.  Installation
2.1   Dependencies
2.2   Configuration Options
2.3   Menu Blocks
3.  Batch Updates
4.  Developer Notes
4.1   Extending Options with hook_domain_conf()
4.2   Setting Variables for Domains
4.3   Using domain_conf.inc
4.4   Database Schema

----
1.  Introduction

The Domain Access: Site Configuration module (called Domain Conf), is an
optional extension of the Domain Access module.  Domain Conf provides options
for configuring basic settings of your affiliate sites to be different.

----
1.1 Use-Case

In the original build for the Domain Access module, we had a problem.  Most
of our affiliates were on the East coast of the U.S.  But a few were further
West, in different time zones.

Using a single time zone configuration for all affiliates simply would not work.
So the Domain Conf module was written as an optional extension for Domain
Access.

This module allows each affiliate site to set specific configuration options,
which will override the default site settings as needed.  See section 2.2 for
more details.

----
1.2 Example

For an example, see http://skirt.com/map.  Note that some of the affiliates
may be in "offline" mode.  This is accomplished using the Domain Conf module.

----
2.  Installation

The Domain Conf module is included in the Domain Access download.  To install,
untar the domain package and place the entire folder in your modules directory.

When you enable the module, it will create a {domain_conf} table in your Drupal
database.

For the module to function correctly, you must follow the instructions in INSTALL.txt.

----
2.1   Dependencies

Domain Conf requires the Domain Access module be installed and active.

----
2.2   Configuration Options

When active, the Domain Conf module provides a 'settings' tab when editing an
entry in your Domain Access list (found at path 'admin/structure/domain').

For each registered domain, you have the option of saving settings that will
replace the system settings for your root site.  The currently available
settings are:

  - Name of site
  - E-mail address
  - Slogan
  - Mission
  - Footer message
  - Default front page
  - Anonymous user
  - Administrative theme
  - Default menu for content (if Menu module is used)
  - Primary links menu (if Menu module is used)
  - Secondary links menu (if Menu module is used)
  - Default time zone
  - Default language (if Locale module is used)
  - Caching mode
  - Minimum cache lifetime
  - Page compression
  - Site status
  - Site off-line message

On page load, these values are dynamically loaded to replace your site's
defaults. If you do not adjust these settings, defaults will be used for all
affiliates.

----
2.3  Menu Blocks

This section only applies if you set separate Primary or Secondary links
for your domains.

Drupal's default blocks for Primary and Secondary links do not respect
the variables set by Domain Configuration. To work around this limitation,
we instead create two new blocks, named:

  -- Domain primary links
  -- Domain secondary links

These blocks respond to domain-specific settings and may be used instead
of (or in addition to) the default Primary and Secondary links blocks.

----
3.  Batch Updates

Domain Conf allows you to make batch changes to settings for all domains.

All of the current settings are available in batch mode.

You may also choose to remove domain-specific configurations.  This feature
is useful if you wish to roll back custom changes.

NOTE: If you make batch changes from a domain other than the primary
domain, the default variable value may be taken from the active domain.
Be sure to check the values in this form before saving.

----
4.  Developer Notes

The Domain Conf module is the model for extending Domain Access.

The following form elements were removed during beta testing:

  - File system path
  - Temporary directory

See http://drupal.org/node/197692 for the reasons.

----
4.1   Extending Options with hook_domain_conf()

The module works by applying hook_form_alter() to the form:
'system_settings_form' and then adding additional fields from other forms.

hook_domain_conf() allows developers to add additional form elements.

Note that you may use this hook to create variables that are independent
of other Drupal modules.  To do so, be sure to set the '#domain_setting' flag
to TRUE before returning your $form array.

 - $form['myform']['#domain_setting'] = TRUE;

Please see the full documentation in the API.

http://therickards.com/api/function/hook_domain_conf/Domain

----
4.2   Setting Variables for Domains

If you need to change the value of a domain-specific setting from another
module, you can now use the function domain_conf_variable_set().

  domain_conf_variable_set($domain_id, $variable, $value = NULL)

Complete documentation of this function is in API.php.

----
4.3   Using domain_conf.inc

This functionality has been deprecated. Use hook_domain_conf() normally.

----
4.4   Database Schema

Installing the module creates a {domain_conf} table that contains:

  - domain_id
  Integer, unique
  The lookup key for this record, foreign key to the {domain} table.

  - settings
  Blob (bytea)
  A serialized array of settings for this domain.

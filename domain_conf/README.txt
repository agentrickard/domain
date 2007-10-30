// $Id$
  
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
1.3   Sponsors
2.  Installation
2.1   Dependencies
2.2   Configuration Options
3.  Developer Notes
3.1   Additional Form Elements
3.2   Database Schema

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
1.3 Sponsors

Domain Conf is sponsored by Morris DigitalWorks.
  http://morrisdigitalworks.com

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

Domain Conf requires a change to your settings.php file, as indicated by the
directions in INSTALL.txt

----
2.2   Configuration Options

When active, the Domain Conf module provides a 'settings' link next to each
entry in your Domain Acccess list (found at path 'admin/build/domain/list').

For each registered domain, you have the option of saving settings that will
replace the system settings for your root site.  The currently available
settings are:

  - Name [of site]
  - E-mail address 
  - Slogan
  - Mission
  - Footer message
  - Default front page [untested]
  - Anonymous user
  - Default time zone
  - File system path
  - Temporary directory
  - Site status
  - Site off-line message

On page load, these values are dynamically loaded to replace your site's 
defaults. If you do not adjust these settings, defaults will be used for all 
affiliates.

----
3.  Developer Notes

Some working notes on the module, which uses hook_init() to load variable
overrides.  Hat tip to Adrian Rossouw's Multidomain module, which uses the
same trick.

The Domain Conf module is the model for extending Domain Acccess.

----
3.1   Additional Form Elements

The module works by applying hook_form_alter() to the form: 
'system_settings_form' and then adding addiitonal fields from other forms.

A hook for adding new form elements will be developed shortly.

----
3.2   Database Schema

Installing the module creates a {domain_conf} table that contains:

  - domain_id
  Integer, unique
  The lookup key for this record, foreign key to the {domain} table.
  
  - settings
  Blob (bytea)
  A serialized array of settings for this domain.

  
// $Id$

/**
 * @file
 * README file for Domain Theme
 */
 
Domain Access: Theme
Assign themes to domains created by the Domain Access modules.

CONTENTS
--------

1.  Introduction
1.1   Contributors
2.  Installation
2.1   Dependencies
3.  Configuration Options
3.1   Theme Settings
3.2   Domain-Specific Themes
4.  Batch Updates
5.  Developer Notes
5.1   Database Schema

----
1.  Introduction

The Domain Theme module is a small module that allows you to assign 
different themes for each active domain created with the Domain Access
module.

----
1.1  Contributors

Drupal user 'canen' http://drupal.org/user/16188 wrote the first implementation
of this module.  The current release version is based on that work.

----
2.  Installation

The Domain Theme module is included in the Domain Access download.  
To install, untar the domain package and place the entire folder in your modules 
directory.

When you enable the module, it will create a {domain_theme} table in your Drupal
database.

----
2.1 Dependencies

Domain Theme requires the Domain Access module be installed and active.

----
3.  Configuration Options

The Domain Theme modules adds configuration options to the main module and
to each of your subdomains.

----
3.1 Theme Settings

This module edits the global $custom_theme variable for your site.  Other modules
-- especially the Organic Groups module -- may also attempt to modify this variable.

If you use other modules that allow custom user or group themes, you may experience
conflicts with the Domain Theme module.  Use this setting to vary the execution order 
of the Domain Theme module.  Lower (negative) values will execute earlier in the Drupal 
page building process.

You may need to experiment with this setting to get the desired result.

----
3.2 Domain-Specific Themes

When active, the Domain Theme module will add a 'theme' link to the Domain List
screen.

When you click the 'theme' link for a domain record, you can set the default 
theme for use by that domain.  This form works just like the default system 
theme selection form, with the following notes:

  -- You cannot enable themes from this screen.
  -- You must configure each theme's settings globally.  There are currently
      no domain-specific settings for themes.

----
4.  Batch Updates

Domain Theme allows you to make batch changes to settings for all domains.

You may also choose to remove domain-specific theme settings.

This feature is useful if you wish to roll back custom changes.

----
5.  Developer Notes

We intend to enable domain-specific theme settings in a later release.  If you 
are interested in helping, see http://drupal.org/node/180264.

----
5.1  Database Schema

Installing the module creates a {domain_theme} table that contains:

  - domain_id
  Integer, unique
  The lookup key for this record, foreign key to the {domain} table.
  
  - theme
  String, unique
  The theme name assigned as the default for this domain.
  
  - settings
  Blob (bytea)
  A serialized array of theme settings for this domain.  Currently not used.

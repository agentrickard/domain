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
4.  Developer Notes
4.1   Database Schema

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

When active, the Domain Theme module will add a 'theme' link to the Domain List
screen.

When you click the 'theme' link for a domain record, you can set the default 
theme for use by that domain.  This form works just like the default system 
theme selection form, with the following notes:

  -- You cannot enable themes from this screen.
  -- You must configure each theme's settings globally.  There are currently
      no domain-specific settings for themes.

----
4.  Developer Notes

We intend to enable domain-specific theme settings in a later release.  If you 
are interested in helping, see http://drupal.org/node/180264.

----
4.1  Database Schema

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
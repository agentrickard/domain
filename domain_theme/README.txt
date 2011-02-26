/**
 * @file
 * README file for Domain Theme
 */

Domain Access: Theme
Assign themes to domains created by the Domain Access modules.

CONTENTS
--------

1.  Introduction
1.1   Upgrading
1.2   Contributors
2.  Installation
2.1   Dependencies
2.2   Upgrading to Drupal 7
3.  Configuration Options
3.1   Theme Settings
3.2   Domain-Specific Themes
3.3   Domain-Specific Theme Settings
3.4   Color Module Notes
3.5   Conflicts With Other Modules
4.  Batch Updates
5.  Developer Notes
5.1   Database Schema

----
1.  Introduction

The Domain Theme module is a small module that allows you to assign
different themes for each active domain created with the Domain Access
module.

You may also set domain-specific settings for each theme.

----
1.1 Upgrading

If you used Domain Theme prior to 6.x.2.0rc7, you will need to run the
Drupal upgrade script.

----
1.2  Contributors

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
2.2   Upgrading to Drupal 7

When you upgrade the module to Drupal 7, you may need to reset your theme
settings for each domain. There are internal changes to the theme system that
cannot be automated during the update process. 

You should check your logo and favicon files after performing an upgrade.

----
3.  Configuration Options

The Domain Theme modules adds configuration options to the main module and
to each of your domains.

----
3.1 Theme Settings

This module edits the global $custom_theme variable for your site.  Other
modules -- especially the Organic Groups module -- may also attempt to modify
this variable.

If you use other modules that allow custom user or group themes, you may
experience conflicts with the Domain Theme module.  Use this setting to vary the
execution order of the Domain Theme module.  Lower (negative) values will
execute earlier in the Drupal page building process.

You may need to experiment with this setting to get the desired result.

----
3.2 Domain-Specific Themes

When active, the Domain Theme module will add a 'theme' link to the Domain List
screen.

When you click the 'theme' link for a domain record, you can set the default
theme for use by that domain.  This form works just like the default system
theme selection form, with the following notes:

  -- You cannot enable themes from this screen. Themes must be enabled globally.

You may configure domain-specific theme settings by clicking on the 'configure'
link.

NOTE: When viewing this configuration page, the theme's domain-specific
settings will be displayed and the page's theme will change.

----
3.3 Domain-Specific Theme Settings

New in versions 6.x.2.0rc7 and higher, you may configure custom theme settings
per domain. This can be very useful in swapping out logo files per domain, or
changed the color of Garland for each domain.

To enable theme-specific setting, click the configure link on the Domain Theme
configure page. You will be presented with the standard Drupal theme
configuration form.

On page load, your domain-specific theme settings will be loaded automatically.

If you configure a theme's settings without having selected a default theme for
the domain, that theme will be made the default.

NOTE: When viewing this configuration page, the theme's domain-specific
settings will be displayed.

NOTE: In Drupal 7, the logo and favicon files you upload per theme are no
longer renamed to match the theme name. In order to have custom logos
and favicons, you may need to name the files appropriately before they are
uploaded.

----
3.4 Color Module Notes

The core Color module allows theme elements to have their colors reset, using
CSS files and image transformations to copy necessary files to create subthemes.
The primary use of Color module is by the default Garland theme.

Color module is a difficult case, and this module works as expected in Garland
and Minelli (both core Drupal themes). You may experience issues with custom
themes, or with modules that dynamically add additional CSS files to the Color
module.

----
3.5   Conflicts With Other Modules

Due to how Drupal handles theme switching, you may experience a conflict if
more than one module tries to alter the site theme. To work around this issue,
Domain Theme provides a settings that allows you to adjust the module's
execution order.

The form is found at the bottom of the settings page for Domain Access,
under the 'Theme settings' header.

You may need to experiment with various weights before finding the correct
setting for your site. Lower weights execute first. Zero is the default weight.

By design, Domain Theme should not reset the theme for a page if another
module has already done so.

----
4.  Batch Updates

Domain Theme allows you to make batch changes to settings for all domains.

You may also choose to remove domain-specific theme selections. Note that
the batch editing form only allows you to change the active theme for a domain.
You cannot use the batch edit screen to modify theme settings per-domain.

Using the batch screen sets the active theme for all domains to the selected
value(s).

This feature is useful if you wish to roll back custom changes.

----
5.  Developer Notes

This module may not work as expected with custom or contributed themes.

Use at your own risk.

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

  - status
  Integer (tiny)
  A boolean flag indicating that this is the active theme for the given domain.

  - filepath
  Varchar (255)
  A string containing the file location for Color module files for this theme.

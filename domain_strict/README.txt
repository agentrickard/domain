/**
 * @file
 * README file for Domain Strict.
 */

Domain Strict
Forces users to be assigned to a domain in order to view content on that domain.

CONTENTS
--------

1.  Introduction
2.  Installation
3.  Configuration
4.  Anonymous and Authenticated Users

----
1. Introduction

The Domain Strict module is a small extension with two purposes:

First, it changes the default Domain Access behavior.  This module
makes the grants given to a user specific to the domains that the user
is registered to see.  Normally, all users are granted the same permission
to view content.  In the case of Domain Strict, individual users can
only see content on domains that they belong to, or content that is
assigned to 'all affiliates'.

Second, it shows module developers how to alter the behavior of
the Domain Access module by using the API.  In this case, we only
use the function hook_domaingrants() to change the default module
behavior.

For developers looking to extend the Domain Access module, this
small module is a guide.

See http://drupal.org/node/199846 for the history of this module.

----
2. Installation

The Domain Strict module comes with the Domain Access download.

To install, you simply enable the module at Admin > Modules.

No database tables are installed and the module itself has no
configuration options.

----
3.  Configuration

Since this module changes the way the Domain Access module behaves,
you may want to alter some of the default Domain Access settings.

Specifically, read up on the 'Special page requests' section of the main
README.

If you want this module to restrict all content viewing, you should:

  1) Set the 'Search settings' to the default value:
      'Search content for the current domain only'

  2) Clear out any rules in the 'Special page requests' settings.

Both these options allow users to see all nodes on specific pages on
any active domain.

----
4.  Anonymous and Authenticated Users

Under Domain Strict, only authenticated users (those who have registered)
are given any domain-specific privileges.

Anonymous users will only be able to view content that is assigned to "all
affiliates."

As a result, enabling this module may cause content to disappear from your
site for users who are not logged in.  This is by design.

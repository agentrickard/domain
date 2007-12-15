// $Id$

/**
 * @file
 * README file for Domain Views.
 */
 
Domain Views
Provides a Views filter for the Domain Access module.

CONTENTS
--------

1.  Introduction
1.1   Authors
2.  Installation
3.  Arguments
4.  Filters

----
1. Introduction

The Domain Views module is a small extension to the Domain Access module.

It provides a Views argument and filter for Domain Access.  This feature allows
Views to filter content based on the access privlieges set by Domain Access.

This module is most useful for site administrators, who can normally see all
nodes at all times.  Using the provides filters can alter this behavior.

This code implements views integration for the Domain Access module by adding
a views filter and argument handler. This allows you to restrict your view content 
to only show content from a specified domain (or set of domains) either by using 
a predefined filter or by passing arguments to the view e.g. example.com/myview/6 
(where 6 is the id for one one of your domains).

----
1.1 Authors

This module was written by Drupal user mrichar1.  http://drupal.org/user/60123

See http://drupal.org/node/200714 for the background on this module.

This module is maintained by agentrickard as part of the Domain package.

----
2. Installation

The Domain Views module comes with the Domain Access download.

To install, you simply enable the module at Admin > Build > Modules.

No database tables are installed and no configuration is required.

----
3.  Arguments

The module provides a default Domain Access argument that can be added
to any View.

If the argument value is not set in your View, it will automatically use the
currently active domain.

For more on Views Arguments, see the documentation on argument handlers for 
views at http://drupal.org/node/99566.   

----
4.  Filters

Using the Domain Access filter lets you restrict a View to only content assigned
to the selected domains.  

However, for users without the 'administer nodes' permission, the content must
be viewable on the active domain.  If you wish to make all content in the View
available to all domains, you should configure the 'Special page requests'
setting provided by the Domain Access module.
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
5.  Access Plugin

----
1. Introduction

The Domain Views module is a small extension to the Domain Access module.
The module requires Views 6.x.2 or higher.

It provides a Views argument and filter for Domain Access.  This feature allows
Views to filter content based on the access privlieges set by Domain Access.

This module is most useful for site administrators, who can normally see all
nodes at all times.  Using the provided filters can alter this behavior.

This code implements Views integration for the Domain Access module by adding
a Views filter and argument handler. This allows you to restrict your view
content to only show content from a specified domain (or set of domains) either
by using a predefined filter or by passing arguments to the view e.g.
example.com/myview/6 (where 6 is the id for one one of your domains).

----
1.1 Authors

This module was written by Drupal user mrichar1.  http://drupal.org/user/60123
It was updgraded to Views 2 by nonsie.  http://drupal.org/user/29899

See http://drupal.org/node/200714 for the background on this module.

This module is maintained by nonsie as part of the Domain package.

----
2. Installation

The Domain Views module comes with the Domain Access download.

To install, you simply enable the module at Admin > Build > Modules.

No database tables are installed and no configuration is required.

----
3. Arguments

This module provides a Domain Access argument that can be added to any View.
Arguments can be thought of as "dynamic filters" that are applied to the view at
run-time.

The Domain Views Argument currently accepts two types of arguments:

    -- a numerical domain_id
    -- the string "current" [without quotation marks]

Assume we have page view that shows a listing of all nodes of node-type "car",
and you enable the "Domain Access" argument type:

    -- If you go to www.example.com/cars/3 you will see a listing of all nodes
    of type car that are assigned to domain 3.

    -- If you go to domain4.example.com/cars/current you will see a listing of
    all nodes of type car that are assigned to domain4.

    -- If the argument value is not set in your View, it will display the
    default view which can be "page not found", a summary view or any of the
    other defaults.

You can get the view to filter by the current domain by default by pasting the
following code in "Argument Handling Code" text box . This will cause the view
to always see the id of the current domain as first argument if no argument has
been passed in.

    // Make the first argument "current" if it is not already set
    if (!$args[0]) {
      $args[0] = 'current';
    }
    return $args;

For more on Views Arguments, see the documentation on argument handlers for
views at:

    http://drupal.org/node/54455.

----
4.  Filters

Using the Domain Access filter lets you restrict a View to only content assigned
to the selected domains.

However, for users without the 'administer nodes' permission, the content must
be viewable on the active domain.  If you wish to make all content in the View
available to all domains, you should configure the 'Special page requests'
setting provided by the Domain Access module.

When using Views filters, you must select at least one criteria for the filter
to be applied.

The filters available to Domain Access include all active domains plus currently
active domain.

----
5. Access Plugin

Domain Views offers an access plugin to all Views that allow you to select
on which domains a View may be accessed. There are three settings to consider.

  -- The domain(s) on which the content should be available.
  -- Whether the user can access content on the active domain. This setting
      mirrors the behavior of the Domain Strict module in that it gathers the
      data from hook_node_grants() before appying access rules.
  -- Whether the user is assigned to the active domain. This setting can be
      used to provide members-only or editors-only Views. This setting is more
      liberal than the strict setting, and they two may be used at the same time.

This access setting is valuable for cases where certain Views are not appropriate
on all domains.

Domain
======

The Domain module suite lets you share users, content, and configuration across a group of domains from a single installation and database.

Current Status
------

Domain module for Drupal port to Drupal 8, under active development.

Domain required Drupal 8.5 or higher.

Active branch is the 8-x.1-x branch in GitHub. Begin any forks from there.

The underlying API is stable, and it's currently usable for access control.
The configuration supports manual editing. Themes should work. Views and Bulk
Operations are not yet supported.

For a complete feature status list, see [CHANGELOG.md](https://github.com/agentrickard/domain/blob/8.x-1.x/CHANGELOG.md)

Included modules
-------

* *Domain*
  The core module. Domain provides means for registering multiple domains within a
  single Drupal installation. It allows users to be assigned as domain administrators,
  provides a Block and Views display context, and creates a default entity reference
  field for use by other modules.

* *Domain Access*
  Provides node access controls based on domains. (This module contains much of the
  Drupal 7 functionality). It allows users to be assigned as editors of content per-domain,
  sets content visibility rules, and provides Views integration for content.

* *Domain Alias*
  Allows multiple hostnames to be pointed to a single registered domain. These aliases
  can include wildcards (such as *.example.com) and may be configured to redirect to
  their canonical domain. Domain Alias also allows developers to register aliases per
  `environment`, so that different hosts are used consistently across development
  environments. See the README file for Domain Alias for more information.

* *Domain Config*
  Provides a means for changing configuration settings on a per-domain basis. See the
  README for Domain Config for more information.

* *Domain Content*
  Provides content overview pages on a per-domain basis, so that editors may review
  content assigned to specific domains. This module is a series of Views.

* *Domain Source*
  Allows content to be assigned a canonical domain when writing URLs. Domain Source will
  ensure that content that appears on multiple domains always links to one URL. See
  the module's README for more information.


Implementation Notes
======

Cross-domain logins
------

To use cross-domain logins, you must now set the *cookie_domain* value in
*sites/default/services.yml*.

To do so, clone  `default.services.yml` to `services.yml` and change the
`cookie_domain` value to match the root hostname of your sites. Note that
cross-domain login requires the sharing of a top-level domain, so a setting like
`.example.com` will work for all `example.com` subdomains.

Example:

```
cookie_domain: '.example.com'
```

See https://www.drupal.org/node/2391871.

Cross-Site HTTP requests (CORS)
------
As of Drupal 8.2, it's possible to allow a particular site to enable CORS for responses
served by Drupal.

In the case of Domain, allowing CORS may remove AJAX errors caused when using some forms,
particularly entity references, when the AJAX request goes to another domain.

This feature is not enabled by default because there are security consequences. See
https://www.drupal.org/node/2715637 for more information and instructions.

To enable CORS for all domains, copy `default.services.yml` to `services.yml` and enable
the following lines:

```
   # Configure Cross-Site HTTP requests (CORS).
   # Read https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
   # for more information about the topic in general.
   # Note: By default the configuration is disabled.
  cors.config:
    enabled: false
    # Specify allowed headers, like 'x-allowed-header'.
    allowedHeaders: []
    # Specify allowed request methods, specify ['*'] to allow all possible ones.
    allowedMethods: []
    # Configure requests allowed from specific origins.
    allowedOrigins: ['*']
    # Sets the Access-Control-Expose-Headers header.
    exposedHeaders: false
    # Sets the Access-Control-Max-Age header.
    maxAge: false
    # Sets the Access-Control-Allow-Credentials header.
    supportsCredentials: false
```

The key here is setting `enabled` to `false`.

Trusted host settings
------

If using the trusted host security setting in Drupal 8, be sure to add each domain
and alias to the pattern list. For example:

```
$settings['trusted_host_patterns'] = array(
  '^.+\.example\.org$',
  '^myexample\.com$',
  '^myexample\.dev$',
  '^localhost$',
);
```

We *strongly encourage* the use of trusted host settings. When Domain issues a redirect,
it will check the domain hostname against these settings. Any redirect that does not
match the trusted host settings will be denied and throw an error.

See https://www.drupal.org/node/1992030 for more information.

Configuring domain records
-------
To create a domain record, you must provide the following information:

* A unique *hostname*, which may include a port. (Therefore, example.com and
example.com:8080 are considered different.) The hostname may only contain
alphanumeric characters, dashes, dots, and one colon. If you wish to use
international domain names, toggle the `Allow non-ASCII characters in domains
 and aliases.` setting.
* A *machine_name* that must be unique. This value will be autogenerated and
cannot be edited once created.
* A *name* to be used in lists of domains.
* A URL scheme, used for writing links to the domain. The scheme may be `http`,
`https`, or `variable`. If `variable` is used, the scheme will be inherited from
the server or request settings. This option is good if your test environments
do not have secure certificates but your production environment does.
* A *status* indicating `active` or `inactive`. Inactive domains may only be
viewed by users with permission to `view inactive domains` all other users will
be redirected to the default domain (see below).
* The *weight* to be used when sorting domains. These values autoincrement as
new domains are created.
* Whether the domain is the *default* or not. Only one domain can be set as
 `default`. The default domain is used for redirects in cases where other
 domains are either restricted (inactive) or fail to load. This value can be
 reassigned after domains are created.

Domain records are *configuration entities*, which means they are not stored in
the database nor accessible to Views by default. They are, however, exportable
as part of your configuration.

Domains and caching
------

If some variable changes are not picked up when the page renders, you may need
add domain-sensitivity to the site's cache.

To do so, clone  `default.services.yml` to `services.yml` (if you have not
already done so) and change the `required_cache_contexts` value to:

```YAML
    required_cache_contexts: ['languages:language_interface', 'theme', 'user.permissions', 'url.site']
```

The addition of `url.site` should provide the domain context that the cache
layer requires.

For developers, see also the information in the Domain Alias README.

Contributing
====

If you'd like to contribute, please do. Github forks and pull requests are preferable.
If you prefer a patch-based workflow, you can attach patches to GitHub issues or Drupal.org
issues. If you open a Drupal.org issue, please link to it from the appropriate GitHub
issue.

The GitHub issues are grouped under three milestones:

1. Alpha -- items required for a test release. When this is complete, we will roll an
alpha1 release on Drupal.org.
2. Beta -- items considered critical features for a release. When complete, we will roll
a beta release on Drupal.org.
3. Final -- items required for a stable, secure release on Drupal.org.

We would like to tackle issues in that order, but feel free to work on what motivates you.

Testing [![Build Status](https://travis-ci.com/agentrickard/domain.svg?branch=8.x-1.x)](https://travis-ci.com/agentrickard/domain)
====

@zerolab built a Travis definition file for automated testing! That means all pull requests will automatically run tests!

If you file a pull request or patch, please (at a minimum) run the existing tests to check
for failures. Writing additional tests will greatly speed completion, as I won't commit
code without test coverage.

New tests should be written in PHPUnit as Functional, Kernel, or Unit tests.

Because Domain requires varying http host requests to test, we can't normally use the Drupal.org
testing infrastructure. (This may change, but we are not counting on it.)

To setup a proper environment locally, you need multiple or wilcard domains configured to
point to your drupal instance. I use variants of `example.com` for local tests. See
`DomainTestBase` for documentation. Domain testing should work with root hosts other than
`example.com`, though we also expect to find the subdomains `one.*, two.*, three.*, four.*, five.*`
in most test cases. See `DomainTestBase::domainCreateTestDomains()` for the logic.

When running tests, you normally need to be on the default domain.

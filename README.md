Domain
======

Domain module for Drupal port to Drupal 8.

Active branch is 8-x.1-x. Begin any forks from there.

The underlying API is stable, and it's currently usable for access control.
The configuration supports manual editing. Themes should work. Views and Bulk
Operations are not yet supported.

For a complete feature status list, see [CHANGELOG.md](https://github.com/agentrickard/domain/blob/8.x-1.x/CHANGELOG.md)

Implementation Notes
======

Cross-domain logins
------

To use cross-domain logins, you must now set the *cookie_domain* value in
*sites/default/services.yml*.

To do so, clone  `default.services.yml` to `services.yml` and change the
`cookie_domain` value to match the root hostname of your sites. Note that
cross-domain login requires the sharing of a top-level domain, so a setting like
`*.example.com` will work for all `example.com` subdomains.

See https://www.drupal.org/node/2391871.

Trusted host settings
------

If using the trusted host security setting in Drupal 8, be sure to add each domain
and alias to the pattern list. For example:

```
$settings['trusted_host_patterns'] = array(
  '^*\.example\.com$',
  '^myexample\.com$',
  '^localhost$',
);
```

See https://www.drupal.org/node/1992030 for more information.

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

Testing
====

@zerolab built a Travis definition file for automated testing! That means all pull requests will automatically run tests!

[![Build Status](https://travis-ci.org/agentrickard/domain.svg?branch=8.x-1.x)](https://travis-ci.org/agentrickard/domain)

The module does have solid test coverage, and complete coverage is required for release.
Right now, we mostly use SimpleTest, because it is most familiar, and much of our
testing is about browser and http behavior.

If you file a pull request or patch, please (at a minimum) run the existing tests to check
for failures. Writing additional tests will greatly speed completion, as I won't commit
code without test coverage.

I use SimpleTest, though unit tests would also be welcome -- as would kernel tests. Those
might take longer to review.

Because Domain requires varying http host requests to test, we can't normally use the Drupal.org
testing infrastructure. (This may change, but we are not counting on it.)

To setup a proper environment locally, you need multiple or wilcard domains configured to
point to your drupal instance. I use variants of `example.com` for local tests. See
`DomainTestBase` for documentation. Domain testing should work with root hosts other than
`example.com`, though we also expect to find the subdomains `one.*, two.*, three.*, four.*, five.*`
in most test cases. See `DomainTestBase::domainCreateTestDomains()` for the logic.

When running tests, you normally need to be on the default domain.

If anyone is capable of building a vagrant box to simplify testing, that would be ideal.


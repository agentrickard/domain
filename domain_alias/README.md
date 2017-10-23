About aliases
====

Aliases define unique domains or patterns that map to existing domain records.
For example, you may want to have the following setup:

* example.com (primary domain)
* my.example.com (active domain record)
* myexample.com (alias of the my.example.com record)

In effect, myexample.com and my.example.com behave the same way from a Drupal
perspective. Aliases may also be made to redirect to their parent domain, if
preferred.

Alias Patterns
====

The Drupal 8 version of Domain Alias supports multi-character wildcards as part
of the hostname. You can register an alias to a domain record in any of the
following patterns.
```
    *.example.com
    example.*.com
    example.com.*
    *.*.example.com
    example.*.*.com
    example.*
    example.*.*
    example.*.*.*
    *.com
    *.example.*
    example.com:8080
    example.com:*
    example.*:*
    *.com:*
```
A maximum of three wildcards are supported. At least one element must not be a
wildcard. Ports may be wildcards as well, but are optional.

Alias Matching
====

When a request is sent to Drupal, the domain negotiation system will look for a
matching record. The order of precedence is as follows.

Example request: `one.example.com`

1. Exact domain record match (`one.example.com`)
1. Exact alias match (`one.example.com`)
1. Wildcard match
```
    one.example.com
    one.example.*
    *.example.com
    one.*.com
    *.example.*
    *.*.com
    one.*.*,
```
Note that wildcard matching happens _in the listed order_. The number of
wildcards is equal to the number of hostname parts minus 1. That is, you cannot register
an alias that is all wildcards.

Port Matching
===

Port matching (e.g. example.com:8080) works exactly as hostname matching, with
one significant change. If the inbound request is on port 80, matches to the
base hostname are permitted, since port 80 is the default port for HTTP.

For example, a request to example.com:80 will match the following aliases:
```
    example.com:80
    example.com
    example.com:*
    example.*
    example.*:80
    example.*:*
    *.com
    *.com:80
    *.com:*
```
Whereas a request to example.com:8080 will not match the hostname without a port
specified.
```
    example.com:8080
    example.com:*
    example.*:8080
    example.*:*
    *.com:8080
    *.com:*
```

Development Workflow
====

Aliases can be used to support development across different environments, with unique
URLs. To support this feature, there is now an `environment` field for each alias. The
default environment list is:

* default
* local
* development
* staging
* testing

This list may be overridden by setting the `domain_alias.environments` configuration in
settings.php.

The operation of these environments is as follows:

* If alias matching the environment is `default`, no changes occur.
* Else, matching aliases are loaded for all domains, so that links are rewritten to be
specific to the specified environment. (See `domain_alias_domain_load()` for the logic.)

For instance, consider the following configuration. Your site's canonical domains are:

* example.com
* foo.example.com
* bar.example.com

When developing locally, developers use `.local` instead of `.com`. These should be
aliased to each domain as set as the `local` environment.

* example.local > alias to example.com
* foo.example.local > alias to foo.example.com
* bar.example.local > alias to bar.example.com

When pushing changes to the cloud, we use a development server. These are tied to a
specific cloud host (dev.mycloud.com). You can alias these to the `development`
environment.

* example.dev.mycloud.com > alias to example.com
* foo.example.dev.mycloud.com > alias to foo.example.com
* bar.example.dev.mycloud.com > alias to bar.example.com

The pattern can repeat for each of the environments listed above. The intended use of the
default set of environments is:

* default -- indicates a canonical URL. No changes will be made.
* local -- for local development environments.
* development -- for a development integration server (such as those provided by Acquia,
Pantheon, and Platform.sh)
* staging -- for a pre-deployment server (such as those provided by Acquia, Panthon, and
Platform.sh)
* testing -- for continuous integration services (such as TravisCI or CircleCI).

None of these environments are required. You may safely set all aliases to default if
your workflow does not span multiple server environments.

How does it work?
----

This feature works by mapping each alias to an environment. If the active request matches
an alias that is set as an environment other than `default`, then matching environment
aliases are loaded for each domain. If a match is found, the `hostname` value for each
domain is overwritten.

This overwrite affects the base path and request url that Domain module (and Domain Source)
use for writing links.

Because the environments are specific to hostnames, this feature will only work if the
site's cache recognizes `url.site` as a required cache context. Without that, the render
system will cache the output of a request incorrectly.

Configuration
----

To use this feature, the following steps must be followed:

* `url.site` must be added as a required_cache_context to your `services.yml` file.
* Aliases must be mapped to a server environment. Default value is `default`.
* All aliases should be listed as part of `trusted_host_settings` in `settings.php`.

Technical Notes
====

The matching follows an explicit sort order shown in DomainAliasSortTest.

The code will attempt to match domains of different lengths when doing wildcard
matching in an environment. That is, an alias to `example.*` assigned to `local` should
return `example.local` if the active domain is `one.example.local`, assigned to `local`.

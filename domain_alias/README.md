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

A maximum of three wildcards are supported. At least one element must not be a
wildcard.

Alias Matching
====

When a request is sent to Drupal, the domain negotiation system will look for a
matching record. The order of precedence is as follows.

Example request: `one.example.com`

1. Exact domain record match (`one.example.com`)
1. Exact alias match (`one.example.com`)
1. Wildcard match
  - one.example.com
  - one.example.*
  - *.example.com
  - one.*.com
  - *.example.*
  - *.*.com
  - one.*.*,

Note that wildcard matching happens _in the listed order_. The number of
wildcards is equal to the number of hostname parts minus 1.

See DomainAliasSortTest for the logic.

---------------
ALIAS PATTERNS
---------------

    *.example.com
    example.*.com
    example.com.*
    *.*.example.com
    example.*.*.com
    example.*
    example.*.*
    example.*.*.*

---------------
LOAD MATCH
---------------

Example: `one.example.com`

1. domain match
1. alias match
1. wildcard match
   - `*.example.com`
   - `one.*.com`
   - `one.example.*`
   - `one.*`
   - `one.*.*`
   - (with ports in all cases, if set)

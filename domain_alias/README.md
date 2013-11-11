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

one.example.com
- domain match
- alias match
- wildcard match
  - *.example.com
  - one.*.com
  - one.example.*
  - one.*
  - one.*.*
(with port in all cases, if set)

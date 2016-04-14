Domain Config
=============

This module provides a per-domain configuration option so that you can change
settings like the site name for each domain.

By default, these settings are also language-aware. An override may exist per
domain, or per-language per domain, based on the prefixes used in the config
file.

Usage
=====

Currently, there is no user interface for changing settings, but you can load
in new files that contain settings configuration per domain.

The pattern for override files is
`domain.config.DOMAIN_MACHINE_NAME.LANGCODE.setting`, with the backup for
language-insensitive files as `domain.config.DOMAIN_MACHINE_NAME.setting`.

To override the site name, for instance, you have a file like the following:

```YAML
uuid: 536db351-7aec-407e-a746-70ba9cc9f190
name: Three
mail: admin@example.com
slogan: ''
page:
  403: ''
  404: ''
  front: /user
admin_compact_mode: false
weight_select_max: 100
langcode: en
default_langcode: en
```

We want this to apply when the domain `three.example.com` is active and English
is the active language. Therefore, this file would be named
`domain.config.three_example_com.en.system.site`. If we wanted this file to
apply whenever the domain `three.example.com` is active, we would leave off the
language prefix: `domain.config.three_example_com.system.site`.

For further examples, see the files in
`\domain\domain_config\tests\modules\domain_config_test\config\install`.

Import that file's contents at the Configuration Synchronization screen:
`admin/config/development/configuration/single/import`

Installation
============

Previously the module relied on manually cloning `default.services.yml`
to change `required_cache_contexts` to:

```YAML
    required_cache_contexts: ['languages:language_interface', 'theme', 'user.permissions', 'url.site']
```

Dependencies
============

- Domain
- The patch from [#2662196 Cache route by Uri and not just Query+Path](https://www.drupal.org/node/2662196)
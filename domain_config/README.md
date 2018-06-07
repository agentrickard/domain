Domain Config
=============

This module provides a per-domain configuration option so that, for each
domain, you can change configuration items of any type, such as settings like
the site name.

By default, these overrides are also language-aware. An override may exist per
domain, or per-language per domain, based on the prefixes used in the config
file.

Usage
=====

Currently, there is no user interface for changing settings, but you can load
in new files that contain configuration per domain.

The pattern for override files is
`domain.config.DOMAIN_MACHINE_NAME.LANGCODE.item.name`, with the backup for
language-insensitive files as `domain.config.DOMAIN_MACHINE_NAME.item.name`,
where `item.name` is the name of the configuration item being overridden.

To override the site name, for instance, you begin by exporting the
corresponding configuration item at the Configuration Synchronization screen
for a single export:
`admin/config/development/configuration/single/export`

In this case, the type of configuration to be exported is "Simple
configuration" and the particular item is named `system.site`.

You should have a file like the following:

```YAML
uuid: 536db351-7aec-407e-a746-70ba9cc9f190
name: Example
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

An override file should contain only the specific data that you want to
override. To override the name, the only line needed is the one beginning
 ```name:```. The resulting override looks like this:

```YAML
name: Three
```

To override both the name and the slogan:

```YAML
name: Three
slogan: 'My site slogan'
```

*Warning: do not copy over the `uuid` value into the new file. Doing so may
cause the configuration import to fail.*

We want this to apply when the domain `three.example.com` is active and English
is the active language. Therefore, this file would be named
`domain.config.three_example_com.en.system.site`. If we wanted this file to
apply whenever the domain `three.example.com` is active, we would leave off the
language prefix: `domain.config.three_example_com.system.site`.

For further examples, see the files in
`\domain\domain_config\tests\modules\domain_config_test\config\install`.

Import that file's contents at the Configuration Synchronization screen for a
single import:
`admin/config/development/configuration/single/import`

Note that, unlike when you exported the item, here you always select "Simple
configuration" as the configuration type to import, independent of the type of
configuration you're overriding.

settings.php overrides
----------------------

For environment-specific or sensitive overrides, use the settings.php method.
In the above case, add:

`$config['domain.config.three_example_com.en.system.site']['name'] = "My special site";`

to your local settings.php file. This will ensure that the `three.example.com`
domain gets the correct value regardless of other module overrides. If you do
not have a language set the config system will fallback on the domain-specific
setting (note the missing '.en'):

`$config['domain.config.three_example_com.system.site']['name'] = "My special site";`

To set nested values you need to nest the config array:

`$config['domain.config.three_example_com.system.site']['page']['front'] = '/node/1;`

Read more about the "Configuration override system" at
https://www.drupal.org/node/1928898.

Installation
============

If some variable changes are not picked up when the page renders, you may need
add domain-sensitivity to the site's cache.

To do so, clone  `default.services.yml` to `services.yml` and change the
`required_cache_contexts` value to:

```YAML
    required_cache_contexts: ['languages:language_interface', 'theme', 'user.permissions', 'url.site']
```

Dependencies
============

- Domain

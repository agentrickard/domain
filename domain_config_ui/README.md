Domain Config UI
================

This module allows configuration to be saved for a selected domain. It is intended to be used for simple settings (like the site name string). Complex settings like dates and languages may not be covered by this module.

The module allows select editors to save settings on a per-domain and per-language basis. It removes some of the need to edit domain.config files manually.

## Permissions

The module provides four permissions:

* 'Administer Domain Config UI settings'
    - Allows administrators to determine what forms are available for domain-specific configuration. Give only to administrators.
* 'Manage domain-specific configurations'
    - Allows domain administrators to use configuration forms specific to their managed domains.
* 'Set the default configuration for all sites'
    - Allows domain administrators to set the default value for configuration. The default value is used for all sites without a domain-specific configuration.
* 'Translate domain-specific configurations'
    - Allows domain administrators to use language-specific configuration forms specific to their managed domains.

Different form options will be provided to users based on these permissions.

This behavior is covered by the *DomainConfigUIPermissionsTest* and the *DomainConfigUIOptionsTest*.

## Form usage

By default, the Appearance page and the Basic Site Settings page are enabled for domain-specific forms. Administrators may inspect and expand the form list at the settings page (admin/config/domain/config-ui).

On admin forms that contain configuration, the administrator should see a buttom to 'Enable / Disable domain configuration'. This button can be used to add or remove a form from domain-sensitivity.

*Note: removing a form will not remove its configuration files. See below.*

When a form is domain-enabled, users with the *Manage domain-specific configurations* permission who are assigned to that domain and can access the form will be given the option to save the form for their domain. If the language module is enabled and the user has the *Translate domain-specific configurations* permission, then all language options will be shown as well.

This behavior is covered by the *DomainConfigUiSettingsTest*.

## Inspecting and deleting domain configuration

Administrators can inspect stored domain configuration on the 'Saved configuration' page (admin/config/domain/config-ui/list). From here, you may inspect individual configuration files or delete those files.

This behavior is covered by the *DomainConfigUiSavedConfigTest*.

## An example

In a case where we want to have a different Site slogan per domain, we can do the following:

* Log in as an administrator with *Administer Domain Config UI settings*.
* Go to admin/config/system/site-information.
* Enable the form if it is not already enabled.
* Select a domain -- note that when you do, the page will reload and the settings values may change.
* Update the *Slogan* field.
* Save the form.
* Select another domain.
* Note that the slogan field is different than the one you saved.

This behavior is covered by the *DomainConfigUIOverrideTest*.

# Limitations

As noted above, some administrative forms have complex handling that cannot be covered by this module. The color settings for the Bartik theme are a good example. Default language handling is another. Due to the extra processing required by these settings, we do not recommend using Domain Config UI for these settings.

The proper function of any settings is *at the administrator's own risk*. Always test before deploying configuration to the live site. If a configuration override does not work, there may be numerous core reasons why. The most common is caching, addressed below.

# Installation

If some variable changes are not picked up when the page renders, you may need
add domain-sensitivity to the site's cache.

To do so, clone  `default.services.yml` to `services.yml` and change the
`required_cache_contexts` value to add the *url.site* context:

```YAML
    required_cache_contexts: ['languages:language_interface', 'theme', 'user.permissions', 'url.site']
```

## Dependencies

- Domain
- Domain Config

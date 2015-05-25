Domain
======

Domain module for Drupal port to Drupal 8.

Active branch is 8.0.x. Begin any forks from there.

When the module is more stable, we will move it back to drupal.org.

This branch is unstable, as we are moving to config entities.

Implementation Notes
======

To use cross-domain logins, you must now set the *cookie_domain* value in *sites/default/services.yml*. See https://www.drupal.org/node/2391871.

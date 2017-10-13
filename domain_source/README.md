Domain Source
======

Allows content to be assigned a canonical domain when writing URLs. Domain Source will
ensure that content that appears on multiple domains always links to one URL.

When links are written to content from another domain, the links
will go to the "source" domain specified for the node.

Domain Source does not issue redirects. It rewrites links. The only time redirects may
be involved is when content is saved.

Domain Source Fields
-----

Domain Source adds a field to each content type that works with or without Domain Access.
The field is optional. If Domain Access is present, only a selected domain may be
assigned as the source domain.

Domain Source configuration
-----

The module has a configuration option that allows administrators to disable link rewrites
for specific Drupal paths. These paths are defined by Drupal's routing system. The default'
set of routes are:

* `canonical` -- the View page of a node
* `delete_form` -- the delete form
* `edit_form` -- the edit form
* `version_history` -- the revisions page
* `revision` -- the revision edit form
* `create` -- not enforced

Additional routes may be defined by other modules. Most notable Content Translation,
which adds:

* `content_translation_overview` -- the overview page
* `content_translation_add` -- the add translation link
* `content_translation_edit` -- the edit translation form
* `content_translation_delete` -- the delete translation form

Additional Entities
-----

Domain Source is designed to work for content (nodes) but should work with other content
entities. You will need to configure the field for each entity type manually.

Developer Notes
-----

Domain Source changes core's `redirect_response_subscriber` service to the
`DomainSourceRedirectResponseSubscriber` class. This allows us to issue redirects to
registered domains and aliases that would otherwise not be recognizes as internal Drupal
links. These redirects typically occur on entity save when the source domain varies from
the current domain.

Domain Source also implements `OutboundPathProcessorInterface` to rewrite links to an
entity assigned to a source domain.

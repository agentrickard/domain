<?php

namespace Drupal\domain\Plugin\EntityReferenceSelection;

use Drupal\domain\Plugin\EntityReferenceSelection\DomainSelection

/**
 * Provides entity reference selections for the domain entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:domain_admin",
 *   label = @Translation("Domain administrator selection"),
 *   entity_types = {"domain"},
 *   group = "default",
 *   weight = 1
 * )
 */
class DomainAdminSelection extends DomainSelection {

  /**
   * Sets the context for the alter hook.
   *
   * The only difference between this selector and its parent are the
   * permissions used to restrict access. Since the field information is not
   * available through the DefaultSelector class, we have to coerce that
   * information to pass it to our hook.
   *
   * We could do this by reading the id from the annotation, but setting an
   * explicit variable seems more obvious for developers.
   *
   * @var string
   */
  protected $field_type = 'admin';

}

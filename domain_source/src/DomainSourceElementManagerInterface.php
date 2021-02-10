<?php

namespace Drupal\domain_source;

use Drupal\domain\DomainElementManagerInterface;

/**
 * Handles hidden field options for domain source references.
 */
interface DomainSourceElementManagerInterface extends DomainElementManagerInterface {

  /**
   * Defines the name of the source domain field.
   */
  const DOMAIN_SOURCE_FIELD = 'field_domain_source';

}

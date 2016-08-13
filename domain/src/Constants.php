<?php
/**
 *
 *
 * PHP Version 5
 *
 * @author Karl DeBisschop <karl.debisschop@fen.com>
 */

namespace Drupal\domain;


class Constants {
  /**
   * Defines record matching types when dealing with request alteration.
   *
   * @see hook_domain_request_alter().
   */
  const DOMAIN_MATCH_NONE = 0;
  const DOMAIN_MATCH_EXACT = 1;
  const DOMAIN_MATCH_ALIAS= 2;

  /**
   * Defines the name of the node access control field.
   */
  const DOMAIN_ACCESS_FIELD = 'field_domain_access';

  /**
   * Defines the name of the all affiliates field.
   */
  const DOMAIN_ACCESS_ALL_FIELD = 'field_domain_all_affiliates';

  /**
   * Defines the name of the source domain field.
   */
  const DOMAIN_SOURCE_FIELD = 'field_domain_source';
}
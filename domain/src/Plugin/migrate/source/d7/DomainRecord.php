<?php

namespace Drupal\domain\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 Domain source from database.
 *
 * @MigrateSource(
 *   id = "d7_domain",
 *   source_module = "domain"
 * )
 */
class DomainRecord extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $fields = [
      'domain_id',
      'subdomain',
      'sitename',
      'scheme',
      'valid',
      'weight',
      'is_default',
      'machine_name',
    ];
    return $this->select('domain', 'd')->fields('d', $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'domain_id' => $this->t('Domain ID.'),
      'subdomain' => $this->t('Subdomain.'),
      'sitename' => $this->t('Sitename.'),
      'scheme' => $this->t('Scheme.'),
      'valid' => $this->t('Valid.'),
      'weight' => $this->t('Weight.'),
      'is_default' => $this->t('Is default.'),
      'machine_name' => $this->t('Machine name.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['domain_id' => ['type' => 'integer']];
  }

}

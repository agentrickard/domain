<?php

namespace Drupal\domain_access\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Symfony\Component\Console\Input\InputInterface;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Config\StorageException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Html;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainStorageInterface;
use Drupal\domain\Commands\DomainCommands;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\InputOption;

/**
 * Drush commands for the domain access module.
 *
 * These commands mainly extend base Domain commands.
 */
class DomainAccessCommands extends DomainCommands {

  /**
   * Registers additional information to domain:info.
   *
   * @hook init domain:info
   */
  public function initDomainInfo(InputInterface $input, AnnotationData $annotationData) {
    // To add a field label, append to the 'field-labels' item.
    // @TODO: watch https://github.com/consolidation/annotated-command/pull/174
    $annotationData['field-labels'] .= "\n" . 'domain_access_entities: Domain access entities';
  }

  /**
   * Provides additional information to domain:info.
   *
   * @hook alter domain:info
   */
  public function alterDomainInfo($result, CommandData $commandData) {
    // Display which entities are enabled for domain by checking for the fields.
    $result['domain_access_entities'] = $this->getFieldEntities(DOMAIN_ACCESS_FIELD);

    return $result;
  }

}

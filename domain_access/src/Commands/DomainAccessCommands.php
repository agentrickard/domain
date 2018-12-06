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
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\InputOption;

/**
 * Drush commands for the domain access module.
 *
 * These commands mainly extend base Domain commands.
 */
class DomainAccessCommands extends DrushCommands {

  /**
   * Registers additional information to domain:info.
   *
   * @hook init domain:info
   */
  public function initDomainInfo(InputInterface $input, AnnotationData $annotationData) {
    // To add a field label, append to the 'field-labels' item.
    // Note that \ArrayObject::offsetGet()/Set() are used here.
    $data = $annotationData->offsetGet('field-labels');
    $annotationData->offsetSet('field-labels', $data . "\n" . 'domain_access: Domain Access');
  }

  /**
   * Provides additional information to domain:info.
   *
   * @hook alter domain:info
   * @option $alteration Alter the result of the command in some way.
   * @default $alteration TRUE
   * @usage domain:info --alteration
   */
  public function alterDomainInfo($result, CommandData $commandData) {
    if ($commandData->input()->getOption('alteration')) {
      $result['domain_access'] = 'hey';
    }
    return $result;
  }

}

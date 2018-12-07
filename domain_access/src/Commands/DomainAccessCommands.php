<?php

namespace Drupal\domain_access\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\domain\Commands\DomainCommands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Drush commands for the domain access module.
 *
 * These commands mainly extend base Domain commands. See the documentation at
 * https://github.com/consolidation/annotated-command for details.
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

/**
 * @hook option domain:delete
 */
  public function deleteOptions(Command $command, AnnotationData $annotationData) {
    $command->addOption(
        'content-assign',
        '',
        InputOption::VALUE_OPTIONAL,
        'Reassign content for Domain Access',
        null
    );
  }

}

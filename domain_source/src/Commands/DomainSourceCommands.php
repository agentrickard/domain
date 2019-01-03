<?php

namespace Drupal\domain_source\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\domain\Commands\DomainCommands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Drush commands for the domain source module.
 *
 * These commands mainly extend base Domain commands. See the documentation at
 * https://github.com/consolidation/annotated-command for details.
 */
class DomainSourceCommands extends DomainCommands {

  /**
   * Registers additional information to domain:info.
   *
   * @hook init domain:info
   */
  public function initDomainInfo(InputInterface $input, AnnotationData $annotationData) {
    // To add a field label, append to the 'field-labels' item.
    // @TODO: watch https://github.com/consolidation/annotated-command/pull/174
    $annotationData['field-labels'] .= "\n" . 'domain_source_entities: Domain source entities';
  }

  /**
   * Provides additional information to domain:info.
   *
   * @hook alter domain:info
   */
  public function alterDomainInfo($result, CommandData $commandData) {
    // Display which entities are enabled for domain by checking for the fields.
    $result['domain_source_entities'] = $this->getFieldEntities(DOMAIN_SOURCE_FIELD);

    return $result;
  }

  /**
   * @hook option domain:delete
   */
  public function deleteOptions(Command $command, AnnotationData $annotationData) {
    $command->addOption(
        'source-assign',
        '',
        InputOption::VALUE_OPTIONAL,
        'Reassign content for Domain Source',
        null
    );
  }

  /**
   * @hook on-event domain-delete
   */
  public function domainSourceDomainDelete($target_domain, $options) {
    // Run our own deletion routine here.
    if (is_null($options['content-assign'])) {
      $policy_content = 'prompt';
    }
    if (!empty($options['content-assign'])) {
      if (in_array($options['content-assign'], $this->reassignment_policies, TRUE)) {
        $policy_content = $options['content-assign'];
      }
    }

    $delete_options = [
      'entity_filter' => 'node',
      'policy' => $policy_content,
      'field' => DOMAIN_SOURCE_FIELD,
    ];

    return $this->doReassign($target_domain, $delete_options);
  }

}

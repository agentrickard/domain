<?php

namespace Drupal\domain\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Drupal\Core\Config\StorageException;
use Drush\Commands\DrushCommands;
use Drupal\Component\Utility\Html;
use Drupal\domain\DomainInterface;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;


/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class DomainCommands extends DrushCommands {

  /** @var \Drupal\domain\DomainStorageInterface $domain_storage */
  protected $domain_storage = NULL;

  /** @var string[] Array of special-case policies for reassigning content. */
  protected $reassignment_policies = ['prompt', 'default', 'ignore']; // + machine name;

  /**
   * Get a domain storage object or throw an exception.
   *
   * @return \Drupal\domain\DomainStorageInterface
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function getStorage() {
    if ($this->domain_storage) {
      return $this->domain_storage;
    }

    try {
      $this->domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    }
    catch (PluginNotFoundException $e) {
      throw new DomainCommandException('Unable to get domain: no storage', $e);
    }
    catch (InvalidPluginDefinitionException $e) {
      throw new DomainCommandException('Unable to get domain: bad storage', $e);
    }

    return $this->domain_storage;
  }

  /**
   * Lookup a string representing a domain config into that config, or throw.
   * 
   * @param string $argument
   *   A domain-id representing domain, or the domain hostname.
   *
   * @return \Drupal\domain\DomainInterface
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function getDomainFromArgument($argument) {
    $domain_storage = $this->getStorage();
    $domain = $domain_storage->load($argument);
    if (!$domain) {
      $domain = $domain_storage->loadByHostname($argument);
    }
    if (!$domain) {
      throw new DomainCommandException(
        dt('Domain record could not be found from "!a".', ['!a' => $argument])
      );
    }
    return $domain;
  }

  /**
   * Filter a list of domains by excluding
   *
   * @param DomainInterface[] $domains
   *   List of domains.
   * @param int[] $exclude
   *   List of domain ID numbers to exclude from the list.
   *
   * @return array
   */
  protected function filterDomains($domains, $exclude, $initial = []) {
    $list = $initial;
    foreach ($domains as $one) {
      // Not the domain we're deleting, and not the default.
      if (!in_array($one->id(), $exclude)) {
        $list[$one->getDomainId()] = $one;
      }
    }
    return $list;
  }

  /**
   * Check the domain response.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *
   * @return string
   */
  protected function checkHTTPResponse(DomainInterface $domain, $validate_url) {
    // Ensure the url is rebuilt.
    if ($validate_url) {
      $this->validateDomain($domain->getHostname());
    }
  }

  /**
   * Helper function: check a domain is responsive and create it.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function checkCreatedDomain(DomainInterface $domain, array $values) {
    if ($error = $this->checkHTTPResponse($domain, $values['validate_url'])) {
      throw new DomainCommandException(dt('Nobody\'s listening on domain !d: (!e)', [
        '!d' => $domain->getDomainId(),
        '!e' => $error,
      ]));
    }
    else {
      try {
        $domain->save();
      }
      catch (EntityStorageException $e) {
        throw new DomainCommandException('Unable to save domain', $e);
      }

      if ($domain->getDomainId()) {
        $this->logger()->info(dt('Created @name at @domain.',
          ['@name' => $domain->label(), '@domain' => $domain->getHostname()]));
      }
      else {
        $this->logger()->error(dt('The request could not be completed.'));
      }
    }
  }

  /**
   * Validates a domain.
   *
   * @param $hostname
   *   The domain name to validate for syntax and uniqueness.
   *
   * @return array
   *   An array of errors encountered.
   *
   * @see domain_validate()
   */
  protected function validateDomain($hostname) {
    /** @var \Drupal\domain\DomainValidatorInterface $validator */
    $validator = \Drupal::service('domain.validator');
    return $validator->validate($hostname);
  }

  /**
   * Deletes a domain record.
   *
   * @param DomainInterface[] $domains
   *   The domain_id to delete. Pass 'all' to delete all records.
   * @param string[] $delete_options
   *   Array containing who to reassign content and users to.
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   * @throws \UnexpectedValueException
   */
  protected function deleteDomain($domains, $delete_options = []) {
    foreach ($domains as $domain) {
      if (!$domain instanceof DomainInterface) {
        throw new StorageException('deleting domains: value is not a domain');
      }

      $hostname = $domain->getHostname();
      try {
        $domain->delete();
      }
      catch (EntityStorageException $e) {
        throw new DomainCommandException(dt('Unable to delete domain: @dom',
          ['@dom' => $hostname]), $e);
      }
      $this->logger()->info(dt('Domain record @domain deleted.',
        ['@domain' => $hostname]));
    }
  }

  /**
   * Reassign entities of the supplied type to the $policy domain.
   *
   *
   * @param $entity_type
   *   The name of an entity type, e.g. 'node'.
   * @param DomainInterface[] $domains
   *   List of the domains to reassign content away from.
   * @param $policy
   *   One of the domain policy values (ignore, default) or the machine name of
   *   the domain to assign to.
   * @param $multidomain
   *   When an entity is part of multiple domains, should it be treated the same
   *   as single-domain content (FALSE), or just removec (TRUE).
   */
  protected function reassignLinkedEntities($entity_type, $domains, array $options = []) {
    $entity_manager = \Drupal::entityTypeManager();
    $entity_definition = $entity_manager->getDefinition($entity_type, FALSE);

    // Domain Access can only use entities that have bundles.
    if ($entity_definition->getBundleEntityType()) {
      $list_builder = $entity_manager->getListBuilder($entity_type);
      $query        = $list_builder->getStorage()->getQuery();
      $query->exists(DOMAIN_ACCESS_FIELD);
      $query->sort($this->entityType->getKey('id'));

      $entity_manager;
    }
  }

  // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

  /**
   * List active domains for the site.
   *
   * @option inactive
   *   Show only the domains that are inactive/disabled.
   * @option active
   *   Show only the domains that are active/enabled.
   * @usage drush domain:list
   *   List active domains for the site.
   * @usage drush domains
   *   List active domains for the site.
   *
   * @command domain:list
   * @aliases domains,domain-list
   *
   * @field-labels
   *   weight: Weight
   *   name: Name
   *   hostname: Hostname
   *   scheme: Scheme
   *   status: Status
   *   is_default: Default
   *   domain_id: Domain Id
   *   id: Machine name
   * @default-fields domain_id,id,name,hostname,status,is_default
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function listDomains(array $options = ['inactive' => null, 'active' => null]) {
    $domain_storage = $this->getStorage();

    // Load all domains:
    $domains = $domain_storage->loadMultipleSorted(NULL);

    if (empty($domains)) {
      $this->logger()->warning(dt('No domains have been created. Use "drush domain:add" to create one.'));
      return new RowsOfFields([]);
    }

    $keys = [
      'weight',
      'name',
      'hostname',
      'scheme',
      'status',
      'is_default',
      'domain_id',
      'id',
    ];
    $rows = [];
    /** @var \Drupal\domain\DomainInterface $domain */
    foreach ($domains as $domain) {
      $row = [];
      foreach ($keys as $key) {
        $v = $domain->get($key);
        switch($key) {
          case 'status':
            if (($options['inactive'] && $v) || ($options['active'] && !$v)) {
              continue 3; // switch, for, for
            }
            $v = ($v) ? dt('Active') : dt('Inactive');
            break;
          case 'is_default':
            $v = ($v) ? dt('Default') : '';
            break;
        }

        $row[$key] = Html::escape($v);
      }
      $rows[] = $row;
    }
    return new RowsOfFields($rows);
  }

  /**
   * List general information about the domains on the site.
   *
   * @usage drush domain:info
   *
   * @command domain:info
   * @aliases domain-info,dinf
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   * @field-labels
   * count: All Domains
   * count_active: Active Domains
   * default_id: Default Domain ID
   * default_host: Default Domain Hostname
   * scheme: Domain entity fields
   * @list-orientation true
   * @format table
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function infoDomains() {
    $domain_storage = $this->getStorage();

    // Load all domains:
    $all_domains = $domain_storage->loadMultiple(NULL);
    $active_domains = [];
    foreach ($all_domains as $domain) {
      if ($domain->status()) {
        $active_domains[] = $domain;
      }
    }

    $keys = [
      'count',
      'count_active',
      'default_id',
      'default_host',
      'scheme',
    ];
    $rows = [];
    foreach ($keys as $key) {
      $v = '';
      switch($key) {
        case 'count':
          $v = count($all_domains);
          break;
        case 'count_active':
          $v = count($active_domains);
          break;
        case 'default_id':
          $v = $domain_storage->loadDefaultDomain()->getDomainId();
          break;
        case 'default_host':
          $v = $domain_storage->loadDefaultDomain()->getHostname();
          break;
        case 'scheme':
          $v = implode(', ', array_keys($domain_storage->loadSchema()));
          break;
      }

      $rows[$key] = $v;
    }
    return new PropertyList($rows);
  }

  /**
   * Add a new domain to the site.
   *
   * @param $hostname
   *   The domain hostname to register (e.g. example.com).
   * @param $name
   *   The name of the site (e.g. Domain Two).
   * @param array $options An associative array of options whose values come
   *   from cli, aliases, config, etc.
   * @option inactive
   *   Set the domain to inactive status if set.
   * @option https
   *   Use https protocol for this domain if set.
   * @option weight
   *   Set the order (weight) of the domain.
   * @option is_default
   *   Set this domain as the default domain.
   * @option validate
   *   Force a check of the URL response before allowing registration.
   * @usage drush domain-add example.com 'My Test Site'
   * @usage drush domain-add example.com 'My Test Site' --inactive=1 --https==1
   * @usage drush domain-add example.com 'My Test Site' --weight=10
   * @usage drush domain-add example.com 'My Test Site' --validate=1
   *
   * @command domain:add
   * @aliases domain-add
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function add($hostname, $name, array $options = ['inactive' => null, 'https' => null, 'weight' => null, 'is_default' => null, 'validate' => null]) {
    $domain_storage = $this->getStorage();
    $records_count = $domain_storage->getQuery()->count()->execute();
    $start_weight = $records_count + 1;
    $hostname = mb_strtolower($hostname);
    $values = array(
      'hostname' => $hostname,
      'name' => $name,
      'status' => (!$options['invalid']) ? 1 : 0,
      'scheme' => (!$options['https']) ? 'http' : 'https',
      'weight' => ($weight = $options['weight']) ? $weight : $start_weight + 1,
      'is_default' => ($is_default = $options['is_default']) ? $is_default : 0,
      'id' => $domain_storage->createMachineName($hostname),
      'validate_url' => ($options['validate']) ? 1 : 0,
    );
    $domain = $domain_storage->create($values);
    $this->checkCreatedDomain($domain, $values);
  }

  /**
   * Delete a domain from the site.
   *
   * Deletes the domain from the Drupal configuration and optionally reassign
   * content and/or profiles associated with the deleted domain to another.
   * The domain marked as default cannot be deleted: to achieve this goal,
   * mark another, possibly newly created, domain as the default domain, then
   * delete the old default.
   *
   * The usage example descriptions are based on starting with three domains:
   *   - id:19476, machine: example_com, domain: example.com
   *   - id:29389, machine: example_org, domain: example.org  (default)
   *   - id:91736, machine: example_net, domain: example.net
   *
   * @param $domain_id
   *   The numeric id, machine name, or hostname of the domain to delete. The
   *   value "all" is taken to mean delete all except the default domain.
   * @param array $options
   *    An associative array of options whose values come from cli, aliases,
   *    config, etc.
   *
   * @usage drush domain:delete example.com
   *   Delete the domain example.com, assigning its content and users to
   *   the default domain, example.org.
   *
   * @usage drush domain:delete --content-as=ignore example.com
   *   Delete the domain example.com, leaving its content untouched but
   *   assigning its users to the default domain.
   *
   * @usage drush domain:delete --content-as=example_net --users-as=example_net example.com
   *   Delete the domain example.com, assigning its content and users to the
   *   example.net domain.
   *
   * @usage drush domain:delete --dryrun 19476
   *   Show the effects of delete the domain example.com and assigning its
   *   content and users to the default domain, example.org, but not doing so.
   *
   * @usage drush domain:delete --chatty example_net
   *   Verbosely Delete the domain example.net and assign its content and users
   *   to the default domain, example.org.
   *
   * @usage drush domain-delete --chatty all
   *   Verbosely Delete the domains example.com and example.net and assign
   *   their content and users to the default domain, example.org.
   *
   * @option chatty
   *   Document each step as it is performed.
   * @option dryrun
   *   Do not do anything, but explain what would be done. Implies --chatty.
   * @option content-as
   *   Values "prompt", "ignore", "default", <name>, Reassign content
   *   associated with the the domain being deleted to the default domain, to
   *   a specified domain, or leave the content alone (&hence inaccessible in
   *   the normal way). The default value is 'prompt': ask which domain to use.
   * @option users-as
   *   Values "prompt", "ignore", "default", <name>, Reassign user accounts
   *   associated with the the domain being deleted to the default domain,
   *   to the domain whose machine name is <name>, or leave the user accounts
   *   alone (&hence inaccessible in the normal way). The default value is
   *   'prompt': ask which domain to use.
   *
   * @command domain:delete
   * @aliases domain-delete
   * 
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function delete($domain_id, $options = ['content-as' => null, 'users-as' => null, 'dryrun' => null, 'chatty' => null ]) {
    $policy_content = 'prompt';
    $policy_users = 'prompt';

    //region Get current domain list and perform sanity checks.
    $domain_storage = $this->getStorage();
    $default_domain = $domain_storage->loadDefaultDomain();
    $all_domains = $domain_storage->loadMultipleSorted(NULL);

    if (empty($all_domains)) {
      throw new DomainCommandException('There are no configured domains.');
    }
    if (is_null($domain_id)) {
      throw new DomainCommandException('You must specify a domain to delete.');
    }
    //endregion

    //region Determine which domains to be deleted.
    if ($domain_id == 'all') {
      $domains = $all_domains;
      if (empty($domains)) {
        $this->logger()->info(dt('There are no domains to delete.'));
        return;
      }
      $really = $this->io()->confirm(dt('This action cannot be undone. Continue?:'), FALSE);
      if (empty($really)) {
        return;
      }
    }
    elseif ($domain = $this->getDomainFromArgument($domain_id)) {
      if ($domain->isDefault()) {
        throw new DomainCommandException('The primary domain may not be deleted. '
                                         .'Use drush domain:default to set a new default domain.');
      }
      $domains = [$domain];
    }
    else {
      $this->logger()->info(dt('Nothing to do?'));
      return;
    }
    // Create list of domain (entity) ids to delete.
    $delete_domain_ids = array_map(function($v){return $v->id;}, $domains);
    //endregion

    //region Get content disposition from configuration and validate.
    if ($options['content-as']) {
      if (in_array($options['content-as'], $this->reassignment_policies)) {
        $policy_content = $options['content-as'];
      }
      elseif ($this->getDomainFromArgument($domain_id)) {
        $policy_content = $options['content-as'];
      }
    }
    if ($options['users-as']) {
      if (in_array($options['users-as'], $this->reassignment_policies)) {
        $policy_users = $options['users-as'];
      }
      elseif ($this->getDomainFromArgument($domain_id)) {
        $policy_users = $options['users-as'];
      }
    }
    //endregion

    //region Perform the 'prompt' for a destination domain.
    if ($policy_content === 'prompt' || $policy_users === 'prompt') {
      // Make a list of the eligible destination domains in form id -> name.
      $noassign_domain_ids = $delete_domain_ids + [$default_domain->id()];
      $reassign_list = [
        '0' => dt('Do not reassign'),
        '1' => dt('Default domain'),
      ];
      $reassign_list = array_map(
        function ($v) {
          return $v->getHostname();
        },
        $this->filterDomains($all_domains, $noassign_domain_ids, $reassign_list)
      );

      if ($policy_content === 'prompt') {
        $reassign_content = $this->io()
                                 ->choice(dt('Reassign content to:'), $reassign_list);
        if (empty($reassign_content)) {
          throw new DomainCommandException('Cancelled.');
        }
        $policy_content = ($reassign_content == 0) ? 'ignore' : $reassign_content;
      }
      if ($policy_users === 'prompt') {
        $reassign_users = $this->io()
                              ->choice(dt('Reassign users to:'), $reassign_list);
        if (empty($reassign_users)) {
          throw new DomainCommandException('Cancelled.');
        }
        $policy_users = ($reassign_users == 0) ? 'ignore' : $reassign_users;
      }
    }
    if ($policy_content === 'default') {
      $policy_content = $default_domain->getDomainId();
    }
    if ($policy_users === 'default') {
      $policy_users = $default_domain->getDomainId();
    }
    //endregion

    //region Reassign content as required.
    $options = [
      'entity_filter' => 'node',
      'policy' => $policy_content,
      'multidomain' => FALSE,
    ];
    $this->reassignLinkedEntities($domains, $options);
    $options = [
      'entity_filter' => 'user',
      'policy' => $policy_users,
      'multidomain' => FALSE,
    ];
    $this->reassignLinkedEntities($domains, $options);
    //endregion
    
    //$this->deleteDomain($domains);
    //$this->logger()->info(dt('Domain record deleted.'));
  }

  /**
   * Tests domains for proper response.
   *
   * If run from a subfolder, you must specify the --uri.
   *
   * @param $domain_id
   *   The numeric id or hostname of the domain to test. If no value is passed,
   *   all domains are tested.
    * @param array $options An associative array of options whose values come
    *   from cli, aliases, config, etc.
   * @option base_path
   *   The subdirectory name if Drupal is installed in a folder other than
   *   server root.
   * @usage drush domain-test
   * @usage drush domain-test example.com
   * @usage drush domain-test 1
   *
   * @command domain:test
   * @aliases domain-test
   * 
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function test($domain_id, array $options = ['base_path' => null]) {
    $domain_storage = $this->getStorage();

    // TODO: This won't work in a subdirectory without a parameter.
    if ($base_path = $options['base_path']) {
      $GLOBALS['base_path'] = '/' . $base_path . '/';
    }
    if (is_null($domain_id)) {
      $domains =$domain_storage->loadMultiple(NULL);
    }
    else {
      if ($domain = $this->getDomainFromArgument($domain_id)) {
        $domains = [$domain];
      }
      else {
        return;
      }
    }
    foreach ($domains as $domain) {
      if ($domain->getResponse() != 200) {
        $this->logger()->error(dt('Fail: !error. Please pass a --uri parameter or a --base_path to retest.' ,
          ['!error' => $domain->getResponse()]));
      }
      else {
        $this->logger()->info(dt('Success: !url tested successfully.',
          ['!url' => $domain->getPath()]));
      }
    }
  }

  /**
   * Sets the default domain. If run from a subfolder, specify the --uri.
   *
   * @param $domain_id
   *   The numeric id or hostname of the domain to make default.
    * @param array $options
   *    An associative array of options whose values come from cli, aliases,
   *    config, etc.
   * @option validate
   *   Force a check of the URL response before allowing registration.
   * @usage drush domain-default example.com
   * @usage drush domain-default 1
   * @usage drush domain-default 1 --validate=1
   *
   * @command domain:default
   * @aliases domain-default
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function defaultDomain($domain_id, array $options = ['validate' => null]) {
    $domain_storage = $this->getStorage();
    // Resolve the domain.
    if (empty($domain_id)) {
      $domain = $domain_storage->loadDefaultDomain();
    }
    elseif ($domain = $this->getDomainFromArgument($domain_id)) {
      $validate = ($options['validate']) ? 1 : 0;
      $domain->addProperty('validate_url', $validate);
      if ($error = $this->checkHTTPResponse($domain)) {
        throw new DomainCommandException(dt('Unable to verify domain !dom: !error',
          ['!dom' => $domain->getHostname(), '!error' => $error]));
      }
      else {
        $domain->saveDefault();
      }
    }
    $this->logger()->info(dt('!domain set to primary domain.',
      ['!domain' => $domain->getHostname()]));
  }

  /**
   * Deactivates the domain.
   *
   * @param $domain_id
   *   The numeric id or hostname of the domain to disable.
   * @usage drush domain-disable example.com
   * @usage drush domain-disable 1
   *
   * @command domain:disable
   * @aliases domain-disable
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function disable($domain_id) {
    // Resolve the domain.
    if ($domain = $this->getDomainFromArgument($domain_id)) {
      if ($domain->status()) {
        $domain->disable();
        $this->logger()->info(dt('!domain has been disabled.',
          ['!domain' => $domain->getHostname()]));
      }
      else {
        $this->logger()->info(dt('!domain is already disabled.',
          ['!domain' => $domain->getHostname()]));
      }
    }
  }

  /**
   * Activates the domain.
   *
   * @param $domain_id
   *   The numeric id or hostname of the domain to enable.
   * @usage drush domain-disable example.com
   * @usage drush domain-enable 1
   *
   * @command domain:enable
   * @aliases domain-enable
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function enable($domain_id) {
    // Resolve the domain.
    if ($domain = $this->getDomainFromArgument($domain_id)) {
      if (!$domain->status()) {
        $domain->enable();
        $this->logger()->info(dt('!domain has been enabled.',
          ['!domain' => $domain->getHostname()]));
      }
      else {
        $this->logger()->info(dt('!domain is already enabled.',
          ['!domain' => $domain->getHostname()]));
      }
    }
  }

  /**
   * Changes a domain label.
   *
   * @param $domain_id
   *   The numeric id or hostname of the domain to relabel.
   * @param $name
   *   The name to use for the domain.
   * @usage drush domain-name example.com Foo
   * @usage drush domain-name 1 Foo
   *
   * @command domain:name
   * @aliases domain-name
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function renameDomain($domain_id, $name) {
    // Resolve the domain.
    if ($domain = $this->getDomainFromArgument($domain_id)) {
      $domain->saveProperty('name', $name);
    }
  }

  /**
   * Changes a domain name.
   *
   * @param $domain_id
   *   The numeric id or hostname of the domain to rename.
   * @param $machine_name
   *   The machine-readable name to use for the domain.
   * @usage drush domain-machine-name example.com foo
   * @usage drush domain-machine-name 1 foo
   *
   * @command domain:machine-name
   * @aliases domain-machine-name
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function machineName($domain_id, $machine_name) {
    $domain_storage = $this->getStorage();
    $machine_name = $domain_storage->createMachineName($machine_name);
    // Resolve the domain.
    if ($domain = $this->getDomainFromArgument($domain_id)) {
      $results = $domain_storage->loadByProperties(['machine_name' => $machine_name]);
      foreach ($results as $result) {
        if ($result->id() == $machine_name) {
          $this->logger()->warning(dt('The machine_name !machine_name is being used by domain !hostname.',
            ['!machine_name' => $machine_name, '!hostname' => $result->getHostname()]));
          return;
        }
      }
      $domain->saveProperty('id', $machine_name);
    }
  }

  /**
   * Changes a domain scheme.
   *
   * @param $domain_id
   *   The numeric id or hostname of the domain to change.
   * @param $scheme (deprecated)
   *   The URL schema (http or https) to use for the domain.
   * @param array $options
   *    An associative array of options whose values come from cli, aliases,
   *    config, etc.
   *
   * @usage drush domain-scheme example.com --https
   * @usage drush domain-scheme 681 https
   *
   * @option http
   *   Set the domain access scheme to http (no SSL).
   * @option https
   *   Set the domain access scheme to https (with SSL).
   *
   * @command domain:scheme
   * @aliases domain-scheme
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function scheme($domain_id, $scheme, $options = ['http' => null, 'https' => null ]) {
    // Resolve the domain.
    if ($domain = $this->getDomainFromArgument($domain_id)) {
      if (empty($scheme)) {
        if ($options['http'] || $options['http']) {
          if ($options['https']) {
            $scheme = 'https';
          }
          else {
            $scheme = 'http';
          }
        }
        else {
          $scheme = $this->io()->choice(dt('Select the default http scheme:'),
            [
              1 => dt('http'),
              2 => dt('https'),
            ]);
        }
      }

      switch($scheme) {
        case 'http':
        case 1:
          $scheme = 'http';
          break;

        case 'https':
        case 2:
          $scheme = 'https';
          break;

        default:
          return;
      }

      $domain->saveProperty('scheme', $scheme);
    }
  }

  /**
   * Generate domains for testing.
   *
   * @param $primary
   *   The primary domain to use. This will be created and used for
   *   *.example.com hostnames.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   * @option count
   *   The count of extra domains to generate. Default is 15.
   * @option empty
   *   Pass empty=1 to truncate the {domain} table before creating records.
   * @usage drush domain-generate example.com
   * @usage drush domain-generate example.com --count=25
   * @usage drush domain-generate example.com --count=25 --empty=1
   * @usage drush gend
   * @usage drush gend --count=25
   * @usage drush gend --count=25 --empty=1
   *
   * @command domains:generate
   * @aliases gend,domgen,domains-generate
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function generate($primary, array $options = ['count' => null, 'empty' => null]) {
    // Check the number of domains to create.
    $domain_storage = $this->getStorage();

    $count = $options['count'];
    $domains = $domain_storage->loadMultiple(NULL);
    if (empty($count)) {
      $count = 15;
    }
    // Ensure we don't duplicate any domains.
    $existing = array();
    if (!empty($domains)) {
      /** @var DomainInterface $domain */
      foreach ($domains as $domain) {
        $existing[] = $domain->getHostname();
      }
    }
    // Set up one.* and so on.
    $names = array(
      'one',
      'two',
      'three',
      'four',
      'five',
      'six',
      'seven',
      'eight',
      'nine',
      'ten',
      'foo',
      'bar',
      'baz',
    );
    // Set the creation array.
    $new = array($primary);
    foreach ($names as $name) {
      $new[] = $name . '.' . $primary;
    }
    // Include a non hostname.
    $new[] = 'my' . $primary;
    // Filter against existing so we can count correctly.
    $prepared = array();
    foreach ($new as $key => $value) {
      if (!in_array($value, $existing)) {
        $prepared[] = $value;
      }
    }
    // Add any test domains that have numeric prefixes. We don't expect these URLs to work,
    // and mainly use these for testing the user interface.
    $needed = $count - count($prepared);
    for ($i = 1; $i <= $needed; $i++) {
      $prepared[] = 'test' . $i . '.' . $primary;
    }

    // Get the initial item weight for sorting.
    $start_weight = count($domains);
    $prepared = array_slice($prepared, 0, $count);

    // Create the domains.
    foreach ($prepared as $key => $item) {
      $hostname = mb_strtolower($item);
      $values = array(
        'name' => ($item != $primary) ? ucwords(str_replace(".$primary", '', $item)) : \Drupal::config('system.site')->get('name'),
        'hostname' => $hostname,
        'scheme' => 'http',
        'status' => 1,
        'weight' => ($item != $primary) ? $key + $start_weight + 1 : -1,
        'is_default' => 0,
        'id' => $domain_storage->createMachineName($hostname),
      );
      $domain = $domain_storage->create($values);
      $this->checkCreatedDomain($domain, $values);
    }

    // If nothing created, say so.
    if (empty($new)) {
      $this->logger()->info(dt('No new domains were created.'));
    }
  }

}

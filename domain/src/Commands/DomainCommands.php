<?php

namespace Drupal\domain\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
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
 * Drush commands for the domain module.
 */
class DomainCommands extends DrushCommands implements CustomEventAwareInterface {

  use CustomEventAwareTrait;

  /**
   * The domain entity storage service.
   *
   * @var \Drupal\domain\DomainStorageInterface $domain_storage
   */
  protected $domain_storage = NULL;

  /**
   * Local cache of entity field map, kept for performance.
   *
   * @var array
   */
  protected $entity_field_map = NULL;

  /**
   * Flag set by the --dryrun cli option. If set prevents changes from
   * being made by code in this class.
   *
   * @var bool
   */
  protected $is_dry_run = FALSE;

  /**
   * Static array of special-case policies for reassigning field data.
   *
   * @var string[]
   * */
  protected $reassignment_policies = ['prompt', 'default', 'ignore'];

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
   *   response: HTTP Response
   *   scheme: Scheme
   *   status: Status
   *   is_default: Default
   *   domain_id: Domain Id
   *   id: Machine name
   * @default-fields id,name,hostname,scheme,status,is_default,response
   *
   * @param array $options
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function listDomains(array $options) {
    // Load all domains:
    $domains = $this->domainStorage()->loadMultipleSorted();

    if (empty($domains)) {
      $this->logger()->warning(dt('No domains have been created. Use "drush domain:add" to create one.'));
      return new RowsOfFields([]);
    }

    $keys = [
      'weight',
      'name',
      'hostname',
      'response',
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
        switch($key) {
          case 'response':
            try {
              $v = $this->checkDomain($domain);
            }
            catch(\GuzzleHttp\Exception\TransferException $ex) {
              $v = dt('500 - Failed');
            }
            catch(Exception $ex) {
              $v = dt('500 - Exception');
            }
            if ($v >= 200 && $v <= 299) {
              $v = dt('200 - OK');
            }
            elseif ($v == 500) {
              $v = dt('500 - No server');
            }
            break;
          case 'status':
            $v = $domain->get($key);
            if (($options['inactive'] && $v) || ($options['active'] && !$v)) {
              continue 3; // switch, for, for
            }
            $v = !empty($v) ? dt('Active') : dt('Inactive');
            break;
          case 'is_default':
            $v = $domain->get($key);
            $v = !empty($v) ? dt('Default') : '';
            break;
          default:
            $v = $domain->get($key);
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
   * default_host: Default Domain hostname
   * scheme: Fields in Domain entity
   * domain_admin_entities: Domain admin entities
   * @list-orientation true
   * @format table
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function infoDomains() {
    $default_domain = $this->domainStorage()->loadDefaultDomain();

    // Load all domains:
    $all_domains = $this->domainStorage()->loadMultiple(NULL);
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
          $v = '-unset-';
          if ($default_domain) {
            $v = $default_domain->id();
          }
          break;
        case 'default_host':
          $v = '-unset-';
          if ($default_domain) {
            $v = $default_domain->getHostname();
          }
          break;
        case 'scheme':
          $v = implode(', ', array_keys($this->domainStorage()->loadSchema()));
          break;
      }

      $rows[$key] = $v;
    }


    // Display which entities are enabled for domain by checking for the fields.
    $rows['domain_admin_entities'] = $this->getFieldEntities(DOMAIN_ADMIN_FIELD);

    return new PropertyList($rows);
  }

  /**
   * Finds entities that reference a specific field.
   *
   * @param $field_name
   *   The field name to lookup.
   */
  public function getFieldEntities($field_name) {
    $entity_manager = \Drupal::entityManager();
    $field_map = $entity_manager->getFieldMap();
    $domain_entities = [];
    foreach($field_map as $type => $fields) {
      if (array_key_exists($field_name, $fields)) {
        $domain_entities[] = $type;
      }
    }
    return implode(', ', $domain_entities);
  }

  /**
   * Add a new domain to the site.
   *
   * @param $hostname
   *   The domain hostname to register (e.g. example.com).
   * @param $name
   *   The name of the site (e.g. Domain Two).
   * @param array $options An associative array of optional values.
   *
   * @option inactive
   *   Set the domain to inactive status if set.
   * @option scheme
   *   Use indicated protocol for this domain, defaults to 'https'. Options:
   *    - http: normal http (no SSL).
   *    - https: secure https (with SSL).
   *    - variable: match the scheme used by the request.
   * @option weight
   *   Set the order (weight) of the domain.
   * @option is_default
   *   Set this domain as the default domain.
   * @option validate
   *   Force a check of the URL response before allowing registration.
   *
   * @usage drush domain-add example.com 'My Test Site'
   * @usage drush domain-add example.com 'My Test Site' --scheme=https --inactive
   * @usage drush domain-add example.com 'My Test Site' --weight=10
   * @usage drush domain-add example.com 'My Test Site' --validate
   *
   * @command domain:add
   * @aliases domain-add
   *
   * @return string
   *   The entity id of the created domain.
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function add($hostname, $name, array $options = ['weight' => null, 'scheme' => null]) {
    // Validate the weight arg.
    if (!empty($options['weight']) && !is_numeric($options['weight'])) {
      throw new DomainCommandException(
        dt('Domain weight "!weight" must be a number',
          ['!weight' => !empty($options['weight']) ? $options['weight'] : ''])
      );
    }

    // Validate the scheme arg.
    if (!empty($options['scheme']) &&
      ($options['scheme'] !== 'http' && $options['scheme'] !== 'https' && $options['scheme'] !== 'variable')
    ) {
      throw new DomainCommandException(
        dt('Scheme name "!scheme" not known',
          ['!scheme' => !empty($options['scheme']) ? $options['scheme'] : ''])
      );
    }

    $domains = $this->domainStorage()->loadMultipleSorted();
    $start_weight = count($domains) + 1;
    $values = [
      'hostname' => $hostname,
      'name' => $name,
      'status' => empty($options['inactive']),
      'scheme' => empty($options['scheme']) ? 'http' : $options['scheme'],
      'weight' => empty($options['weight']) ? $start_weight : $options['weight'],
      'is_default' => !empty($options['is_default']),
      'id' => $this->domainStorage()->createMachineName($hostname),
    ];
    /** @var DomainInterface $domain */
    $domain = $this->domainStorage()->create($values);

    // Check for hostname validity. This is required.
    $valid = $this->validateDomain($domain);
    if (!empty($valid)) {
      throw new DomainCommandException(
        dt('Hostname is not valid. !errors',
          ['!errors' => implode(" ", $valid)])
      );
    }
    // Check for hostname and id uniqueness.
    foreach ($domains as $existing) {
      if ($hostname == $existing->getHostname()) {
        throw new DomainCommandException(
          dt('No domain created. Hostname is a duplicate of !hostname.',
           ['!hostname' => $hostname])
        );
      }
      if ($values['id'] == $existing->id()) {
        throw new DomainCommandException(
          dt('No domain created. Id is a duplicate of !id.',
           ['!id' => $existing->id()])
        );
      }
    }

    $validate_response = (bool) $options['validate'];
    if ($this->createDomain($domain, $validate_response)) {
      return dt('Created the !hostname with machine id !id.', ['!hostname' => $values['hostname'], '!id' => $values['id']]);
    }
    else {
      return dt('No domain created.');
    }
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
   * @usage drush domain:delete --content-assign=ignore example.com
   *   Delete the domain example.com, leaving its content untouched but
   *   assigning its users to the default domain.
   *
   * @usage drush domain:delete --content-assign=example_net --users-assign=example_net
   *   example.com Delete the domain example.com, assigning its content and
   *   users to the example.net domain.
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
   * @option users-assign
   *   Values "prompt", "ignore", "default", <name>, Reassign user accounts
   *   associated with the the domain being deleted to the default domain,
   *   to the domain whose machine name is <name>, or leave the user accounts
   *   alone (and so inaccessible in the normal way). The default value is
   *   'prompt': ask which domain to use.
   *
   * @command domain:delete
   * @aliases domain-delete
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   *
   * @see https://github.com/consolidation/annotated-command#option-event-hook
   */
  public function delete($domain_id, $options = ['users-assign' => null, 'dryrun' => null, 'chatty' => null]) {
    if (is_null($options['users-assign'])) {
      $policy_users = 'prompt';
    }

    $this->is_dry_run = (bool) $options['dryrun'];

    // Get current domain list and perform validation checks.
    $default_domain = $this->domainStorage()->loadDefaultDomain();
    $all_domains = $this->domainStorage()->loadMultipleSorted(NULL);

    if (empty($all_domains)) {
      throw new DomainCommandException('There are no configured domains.');
    }
    if (empty($domain_id)) {
      throw new DomainCommandException('You must specify a domain to delete.');
    }

    // Determine which domains to be deleted.
    if ($domain_id === 'all') {
      $domains = $all_domains;
      if (empty($domains)) {
        $this->logger()->info(dt('There are no domains to delete.'));
        return;
      }
      $really = $this->io()->confirm(dt('This action cannot be undone. Continue?:'), FALSE);
      if (empty($really)) {
        return;
      }
      // TODO: handle deletion of all domains.
    }
    elseif ($domain = $this->getDomainFromArgument($domain_id)) {
      if ($domain->isDefault()) {
        throw new DomainCommandException('The primary domain may not be deleted.
          Use drush domain:default to set a new default domain.');
      }
      $domains = [$domain];
    }

    if (!empty($options['users-assign'])) {
      if (in_array($options['users-assign'], $this->reassignment_policies, TRUE)) {
        $policy_users = $options['users-assign'];
      }
    }

    $delete_options = [
      'entity_filter' => 'user',
      'policy' => $policy_users,
      'field' => DOMAIN_ADMIN_FIELD,
    ];

    if ($policy_users !== 'ignore') {
      $messages[] = $this->doReassign($domain, $delete_options);
    }

    // Fire any registered hooks for deletion, passing them current imput.
    $handlers = $this->getCustomEventHandlers('domain-delete');
    $messages = [];
    foreach ($handlers as $handler) {
      $messages[] = $handler($domain, $options);
    }

    $this->deleteDomain($domains, $options);

    $message = dt('Domain record !domain deleted.', ['!domain' => $domain->id()]);
    if ($messages) {
      $message .= "\n" . implode("\n", $messages);
    }
    $this->logger()->info($message);
    return $message;
  }

  /**
   * Handles reassignment of entities to another domain.
   *
   * This method includes necessary UI elements if the user is prompted to
   * choose a new domain.
   *
   * @param Drupal\domain\DomainInterface $target_domain
   *   The domain selected for deletion.
   * @param array $delete_options
   *   A selection of options for deletion, defined in reassignLinkedEntities().
   */
  public function doReassign(DomainInterface $target_domain, array $delete_options) {
    $policy = $delete_options['policy'];
    $default_domain = $this->domainStorage()->loadDefaultDomain();
    $all_domains = $this->domainStorage()->loadMultipleSorted(NULL);

    // Perform the 'prompt' for a destination domain.
    if ($policy === 'prompt') {
      // Make a list of the eligible destination domains in form id -> name.
      $noassign_domain = [$target_domain->id()];

      $reassign_list = $this->filterDomains($all_domains, $noassign_domain);
      $reassign_base = [
        'ignore' => dt('Do not reassign'),
        'default' => dt('Reassign to default domain'),
      ];
      $reassign_list = array_map(
        function (DomainInterface $d) {
          return $d->getHostname();
        },
        $reassign_list
      );
      $reassign_list = array_merge($reassign_base, $reassign_list);
      $policy = $this->io()->choice(dt('Reassign @type field @field data to:', ['@type' => $delete_options['entity_filter'], '@field' => $delete_options['field']]), $reassign_list);
    }
    elseif ($policy === 'default') {
      $policy = $default_domain->id();
    }
    if ($policy !== 'ignore') {
      $delete_options['policy'] = $policy;
      $target = [$target_domain];
      $count = $this->reassignLinkedEntities($target, $delete_options);
      return dt('@count @type entities updated field @field.', ['@count' => $count, '@type' => $delete_options['entity_filter'], '@field' => $delete_options['field']]);
    }
  }

  /**
   * Tests domains for proper response.
   *
   * If run from a subfolder, you must specify the --uri.
   *
   * @param $domain_id
   *   The machine name or hostname of the domain to make default.
   *
   * @usage drush domain-test
   * @usage drush domain-test example.com
   *
   * @command domain:test
   * @aliases domain-test
   *
   * @field-labels
   *   id: Machine name
   *   url: URL
   *   response: HTTP Response
   * @default-fields id,url,response
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function test($domain_id = null) {
    if (is_null($domain_id)) {
      $domains = $this->domainStorage()->loadMultipleSorted();
    }
    else {
      if ($domain = $this->getDomainFromArgument($domain_id)) {
        $domains = [$domain];
      }
      else {
        throw new DomainCommandException(dt('Domain @domain not found.',
          ['@domain' => $options['domain']]));
      }
    }
    $keys = ['url', 'response'];
    $rows = [];
    foreach ($domains as $domain) {
      $rows[] = [
        'id' => $domain->id(),
        'url' => $domain->getPath(),
        'response' => $domain->getResponse(),
      ];
    }
    return new RowsOfFields($rows);
  }

  /**
   * Sets the default domain.
   *
   * @param $domain_id
   *   The machine name or hostname of the domain to make default.
    * @param array $options
   *    An associative array of options whose values come from cli, aliases,
   *    config, etc.
   * @option validate
   *   Force a check of the URL response before allowing registration.
   * @usage drush domain-default www.example.com
   * @usage drush domain-default example_org
   * @usage drush domain-default www.example.org --validate=1
   *
   * @command domain:default
   * @aliases domain-default
   *
   * @return string
   *   The machine name of the default domain.
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function defaultDomain($domain_id, array $options = ['validate' => null]) {
    // Resolve the domain.
    if (!empty($domain_id) && $domain = $this->getDomainFromArgument($domain_id)) {
      $validate = ($options['validate']) ? 1 : 0;
      $domain->addProperty('validate_url', $validate);
      if ($error = $this->checkHTTPResponse($domain)) {
        throw new DomainCommandException(dt('Unable to verify domain !domain: !error',
          ['!domain' => $domain->getHostname(), '!error' => $error]));
      }
      else {
        $domain->saveDefault();
      }
    }

    // Now, ask for the current default, so we know if it worked.
    $domain = $this->domainStorage()->loadDefaultDomain();
    if ($domain->status()) {
      $this->logger()->info(dt('!domain set to primary domain.',
        ['!domain' => $domain->getHostname()]));
    }
    else {
      $this->logger()->warning(dt('!domain set to primary domain, but is also inactive.',
        ['!domain' => $domain->getHostname()]));
    }
    return $domain->id();
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
   * @return string
   *   'disabled' if the domain is now disabled.
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
        return dt('Disabled !domain.', ['!domain' => $domain->getHostname()]);
      }
      else {
        $this->logger()->info(dt('!domain is already disabled.',
          ['!domain' => $domain->getHostname()]));
        return dt('!domain is already disabled.', ['!domain' => $domain->getHostname()]);
      }
    }
    return dt('No matching domain record found.');
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
   * @return string
   *   'enabled' if the domain is now enabled.
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
        return dt('Enabled !domain.', ['!domain' => $domain->getHostname()]);
      }
      else {
        $this->logger()->info(dt('!domain is already enabled.',
          ['!domain' => $domain->getHostname()]));
        return dt('!domain is already enabled.', ['!domain' => $domain->getHostname()]);
      }
    }
    return dt('No matching domain record found.');
  }

  /**
   * Changes a domain label.
   *
   * @param $domain_id
   *   The machine name or hostname of the domain to relabel.
   * @param $name
   *   The name to use for the domain.
   * @usage drush domain-name example.com Foo
   * @usage drush domain-name 1 Foo
   *
   * @command domain:name
   * @aliases domain-name
   *
   * @return string
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function renameDomain($domain_id, $name) {
    // Resolve the domain.
    if ($domain = $this->getDomainFromArgument($domain_id)) {
      $domain->saveProperty('name', $name);
      return dt('Renamed !domain to !name.', ['!domain' => $domain->getHostname(), '!name' => $domain->label()]);
    }
    return dt('No matching domain record found.');
  }

  /**
   * Changes a domain scheme.
   *
   * @param $domain_id
   *   The machine name or hostname of the domain to change.
   * @param $scheme
   *   The scheme to use for the domain: http, https, or variable.
   *
   * @usage drush domain-scheme example.com http
   * @usage drush domain-scheme example_com https
   *
   * @command domain:scheme
   * @aliases domain-scheme
   *
   * @return string
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function scheme($domain_id, $scheme = null) {
    $new_scheme = null;

    // Resolve the domain.
    if ($domain = $this->getDomainFromArgument($domain_id)) {
      if (!empty($scheme)) {
        // --set with a value
        $new_scheme = $scheme;
      }
      else {
        // Prompt for selection.
        $new_scheme = $this->io()->choice(dt('Select the default http scheme:'),
          [
            'http' => 'http',
            'https' => 'https',
            'variable' => 'variable',
          ]);
      }

      // If we were asked to change scheme, validate the value and do so.
      if (!empty($new_scheme)) {
        switch ($new_scheme) {
          case 'http':
            $new_scheme = 'http';
            break;

          case 'https':
            $new_scheme = 'https';
            break;

          case 'variable':
            $new_scheme = 'variable';
            break;

          default:
            throw new DomainCommandException(
              dt('Scheme name "!scheme" not known', ['!scheme' => $new_scheme])
            );
        }
        $domain->saveProperty('scheme', $new_scheme);
      }

      // Either way, return the (new | current) scheme for this domain.
      return dt('Scheme is now to "!scheme" for !domain', ['!scheme' => $domain->get('scheme'),'!domain' => $domain->id()]);
    }

    // We couldn't find the domain - so fail.
    throw new DomainCommandException(
      dt('Domain name "!domain" not known', ['!domain' => $domain_id])
    );
  }

  /**
   * Generate domains for testing.
   *
   * @param $primary
   *   The primary domain to use. This will be created and used for
   *   *.example.com hostnames.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
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
   * @command domain:generate
   * @aliases gend,domgen,domain-generate
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function generate($primary = 'example.com', array $options = ['count' => null, 'empty' => null]) {
    // Check the number of domains to create.
    $count = $options['count'];
    if (is_null($count)) {
      $count = 15;
    }

    $domains = $this->domainStorage()->loadMultiple(NULL);
    if (!empty($options['empty'])) {
      $this->domainStorage()->delete($domains);
      $domains = $this->domainStorage()->loadMultiple(NULL);
    }
    // Ensure we don't duplicate any domains.
    $existing = [];
    if (!empty($domains)) {
      /** @var DomainInterface $domain */
      foreach ($domains as $domain) {
        $existing[] = $domain->getHostname();
      }
    }
    // Set up one.* and so on.
    $names = [
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
    ];
    // Set the creation array.
    $new = [$primary];
    foreach ($names as $name) {
      $new[] = $name . '.' . $primary;
    }
    // Include a non hostname.
    $new[] = 'my' . $primary;
    // Filter against existing so we can count correctly.
    $prepared = [];
    foreach ($new as $key => $value) {
      if (!in_array($value, $existing, true)) {
        $prepared[] = $value;
      }
    }

    // Add any test domains that have numeric prefixes. We don't expect these URLs to work,
    // and mainly use these for testing the user interface.
    // Test that we already have test domains.
    $start = 1;
    foreach ($existing as $exists) {
      $name = explode('.', $exists);
      if (substr_count($name[0], 'test') > 0) {
        $num = (int) str_replace('test', '', $name[0]) + 1;
        if ($num > $start) {
          $start = $num;
        }
      }
    }
    $needed = $count - count($prepared) + $start;
    for ($i = $start; $i <= $needed; $i++) {
      $prepared[] = 'test' . $i . '.' . $primary;
    }
    // Get the initial item weight for sorting.
    $start_weight = count($domains);
    $prepared = array_slice($prepared, 0, $count);
    $list = [];

    // Create the domains.
    foreach ($prepared as $key => $item) {
      $hostname = mb_strtolower($item);
      $values = [
        'name' => ($item != $primary) ? ucwords(str_replace(".$primary", '', $item)) : \Drupal::config('system.site')->get('name'),
        'hostname' => $hostname,
        'scheme' => 'http',
        'status' => 1,
        'weight' => ($item != $primary) ? $key + $start_weight + 1 : -1,
        'is_default' => 0,
        'id' => $this->domainStorage()->createMachineName($hostname),
      ];
      $domain = $this->domainStorage()->create($values);
      $domain->save();
      $list[] = dt('Created @domain.', ['@domain' => $domain->getHostname()]);
    }

    // If nothing created, say so.
    if (empty($prepared)) {
      return dt('No new domains were created.');
    }
    else {
      return dt("Created @count new domains:\n@list", ['@count' => count($prepared), '@list' => implode("\n", $list)]);
    }
  }

  /**
   * Gets a domain storage object or throw an exception.
   *
   * Note that domain can run very early in the bootstrap, so we cannot
   * reliably inject this service.
   *
   * @return DomainStorageInterface
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function domainStorage() {
    if (!is_null($this->domain_storage)) {
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
   * Loads a domain based on a string identifier.
   *
   * @param string $argument
   *   The machine name or the hostname of an existing domain.
   *
   * @return \Drupal\domain\DomainInterface
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function getDomainFromArgument($argument) {

    // Try loading domain assuming arg is a machine name.
    $domain = $this->domainStorage()->load($argument);
    if (!$domain) {
      // Try loading assuming it is a host name.
      $domain = $this->domainStorage()->loadByHostname($argument);
    }

    // domain_id (an INT) is only used internally because the Node Access
    // system demands the use of numeric keys. It should never be used to load
    // or identify domain records. Use the machine_name or hostname instead.
    if (!$domain) {
      throw new DomainCommandException(
        dt('Domain record could not be found from "!a".', ['!a' => $argument])
      );
    }

    return $domain;
  }

  /**
   * Filters a list of domains by excluding domains appearing in a specific list.
   *
   * @param DomainInterface[] $domains
   *   List of domains.
   * @param string[] $exclude
   *   List of domain id to exclude from the list.
   * @param DomainInterface[] $initial
   *   Initial value of list that will be returned.
   *
   * @return array
   */
  protected function filterDomains(array $domains, array $exclude, array $initial = []) {
    foreach ($domains as $domain) {
      // Exclude unwanted domains.
      if (!in_array($domain->id(), $exclude, FALSE)) {
        $initial[$domain->id()] = $domain;
      }
    }
    return $initial;
  }

  /**
   * Checks the domain response.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain to check.
   * @param bool $validate_url
   *   True to validate this domain by performing a URL lookup; False to skip
   *   the checks.
   *
   * @return bool
   *   True if the domain resolves properly, or we are not checking,
   *   False otherwise.
   */
  protected function checkHTTPResponse(DomainInterface $domain, $validate_url = FALSE) {
    // Ensure the url is rebuilt.
    if ($validate_url) {
      $code = $this->checkDomain($domain);

      // Some sort of success:
      return  $code >= 200 && $code <= 299;
    }
    // Not validating, so all is well!
    return FALSE;
  }

  /**
   * Helper function: check a domain is responsive and create it.
   *
   * @param DomainInterface $domain
   *   The (as yet unsaved) domain to create.
   * @param bool $check_response
   *   Indicates that registration should not be allowed unless the server
   *   returns a 200 response.
   *
   * @return bool
   *    TODO: stndardize this return so we can issue good messages.
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function createDomain(DomainInterface $domain, $check_response = FALSE) {
    if ($check_response) {
      $valid = $this->checkHTTPResponse($domain, TRUE);
      if (!$valid) {
        throw new DomainCommandException(
          dt('The server did not return a 200 response for !d. Domain creation failed. Remove the --validate flag to save this domain.', ['!d' => $domain->getHostname()])
        );
      }
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
        return TRUE;
      }
      else {
        $this->logger()->error(dt('The request could not be completed.'));
      }
    }
    return FALSE;
  }

  /**
   * Checks a domain exists by trying to do an http request to it.
   *
   * @param DomainInterface $domain
   *   The domain to validate for syntax and uniqueness.
   *
   * @return int
   *   The server response code for the request.
   *
   * @see domain_validate()
   */
  protected function checkDomain(DomainInterface $domain) {
    /** @var \Drupal\domain\DomainValidatorInterface $validator */
    $validator = \Drupal::service('domain.validator');
    return $validator->checkResponse($domain);
  }

  /**
   * Validates a domain meets the standards for a hostname.
   *
   * @param DomainInterface $domain
   *   The domain to validate for syntax and uniqueness.
   * @return string[]
   *   Array of strings indicating issues found.
   *
   * @see domain_validate()
   */
  protected function validateDomain(DomainInterface $domain) {
    /** @var \Drupal\domain\DomainValidatorInterface $validator */
    $validator = \Drupal::service('domain.validator');
    return $validator->validate($domain->getHostname());
  }

  /**
   * Deletes a domain record.
   *
   * @param DomainInterface[] $domains
   *   The domain_id to delete. Pass 'all' to delete all records.
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   * @throws \UnexpectedValueException
   */
  protected function deleteDomain(array $domains) {
    foreach ($domains as $domain) {
      if (!$domain instanceof DomainInterface) {
        throw new StorageException('deleting domains: value is not a domain');
      }
      $hostname = $domain->getHostname();

      if ($this->is_dry_run) {
        $this->logger()->info(dt('DRYRUN: Domain record @domain deleted.',
          ['@domain' => $hostname]));
        continue;
      }

      try {
        $domain->delete();
      }
      catch (EntityStorageException $e) {
        throw new DomainCommandException(dt('Unable to delete domain: @domain',
          ['@domain' => $hostname]), $e);
      }
      $this->logger()->info(dt('Domain record @domain deleted.',
        ['@domain' => $hostname]));
    }
  }

  /**
   * Returns a list of the entity types that are domain enabled.
   *
   * A domain-enabled entity is defined here as an entity type that includes
   * the domain access field(s).
   *
   * @param string $using_field
   *   The specific field name to look for.
   *
   * @return string[]
   *   List of entity machine names that support domain references.
   */
  protected function findDomainEnabledEntities($using_field = DOMAIN_ADMIN_FIELD) {
    $this->ensureEntityFieldMap();
    $entities = [];
    foreach($this->entity_field_map as $type => $fields) {
      if (array_key_exists($using_field, $fields)) {
        $entities[] = $type;
      }
    }
    return $entities;
  }

  /**
   * Determines whether or not a given entity is domain-enabled.
   *
   * @param string $entity_type
   *   The machine name of the entity.
   * @param string $field
   *   The name of the field to check for existence.
   *
   * @return bool
   *   True if this type of entity has a domain field.
   */
  protected function entityHasDomainField($entity_type, $field = DOMAIN_ADMIN_FIELD) {
    // Try to avoid repeated calls to getFieldMap(), assuming it's expensive.
    $this->ensureEntityFieldMap();
    return array_key_exists($field, $this->entity_field_map[$entity_type]);
  }

  /**
   * Ensure the local entity field map has been defined.
   *
   * Asking for the entity field map cause a lot of lookup, so we lazily
   * fetch it and then remember it to avoid repeated checks.
   */
  protected function ensureEntityFieldMap() {
    // Try to avoid repeated calls to getFieldMap() assuming it's expensive.
    if (empty($this->entity_field_map)) {
      $entity_manager = \Drupal::entityManager();
      $this->entity_field_map = $entity_manager->getFieldMap();
    }
  }

  /**
   * Enumerate entity instances of the supplied type and domain.
   *
   * @param string $entity_type
   *   The entity type name, e.g. 'node'
   * @param string $domain_id
   *   The machine name of the domain to enumerate.
   * @param string $field
   *   The field to manipulate in the entity, e.g. DOMAIN_ACCESS_FIELD.
   *
   * @return int|string[]
   *   List of entity IDs for the selected domain.
   * @todo: should this really be a string[] of fields?
   */
  protected function enumerateDomainEntities($entity_type, $domain_id, $field, $just_count = FALSE) {
    if (!$this->entityHasDomainField($entity_type, $field)) {
      $this->logger()->info('Entity type @entity_type does not have field @field, so none found.',
        ['@entity_type'=> $entity_type,
         '@field' => $field]);
      return [];
    }

    $efq = \Drupal::entityQuery($entity_type);
    // Don't access check or we wont get all of the possible entities moved.
    $efq->accessCheck(FALSE);
    $efq->condition($field, $domain_id, '=');
    if ($just_count) {
      $efq->count();
    }
    return $efq->execute();
  }

  /**
   * Reassign old_domain entities, of the supplied type, to the new_domain.
   *
   * @param string $entity_type
   *   The entity type name, e.g. 'node'
   * @param string $field
   *   The field to manipulate in the entity, e.g. DOMAIN_ADMIN_FIELD.
   * @param \Drupal\domain\DomainInterface $old_domain
   *   The domain the entities currently belong to. It is not an error for
   *   entity ids to be passed in that are not in this domain, though of course
   *   not very useful.
   * @param \Drupal\domain\DomainInterface $new_domain
   *   The domain the entities should now belong to: When an entity belongs to
   *   the old_domain, this domain replaces it.
   * @param array $ids
   *   List of entity IDs for the selected domain and all of type $entity_type.
   *
   * @return int
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function reassignEntities($entity_type, $field, DomainInterface $old_domain, DomainInterface $new_domain, array $ids) {
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entities = $entity_storage->loadMultiple($ids);

    foreach($entities as $entity) {
      $changed = FALSE;
      if (!$entity->hasField($field)) {
        continue;
      }
      // Multivalue fields are used, so check each one.
      foreach ($entity->get($field) as $k => $item) {
        if ($item->target_id == $old_domain->id()) {

          if ($this->is_dry_run) {
            $this->logger()->info(dt('DRYRUN: Update domain membership for entity @id to @new.',
              [ '@id' => $entity->id(), '@new' => $new_domain->id() ]));

            // Don't set changed, so don't save either.
            continue;
          }

          $changed = TRUE;
          $item->target_id = $new_domain->id();
        }
      }
      if ($changed) {
        $entity->save();
      }
    }
    return count($entities);
  }

  /**
   * Return the Domain object corresponding to a policy string.
   *
   * @param string $policy
   *   In general one of 'prompt' | 'default' | 'ignore' or a domain entity
   *   machine name, but this function does not process 'prompt'.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\domain\DomainInterface|null
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function getDomainInstanceFromPolicy($policy) {
    switch($policy) {
      /* Use the Default Domain machine name */
      case 'default':
        $new_domain = $this->domainStorage()->loadDefaultDomain();
        break;

      /* Ask interactively for a Domain machine name */
      case 'prompt':
      case 'ignore':
        return NULL;

      /* Use this (specified) Domain machine name */
      default:
        $new_domain = $this->domainStorage()->load($policy);
        break;
    }
    return $new_domain;
  }

  /**
   * Reassign entities of the supplied type to the $policy domain.
   *
   * @param array $options
   *  [
   *    'entity_filter' => 'node',
   *    'policy' => 'prompt' | 'default' | 'ignore' | {domain_id}
   *    'field' => DOMAIN_ACCESS_FIELD,
   *  ];
   *
   * @param DomainInterface[] $domains
   *   List of the domains to reassign content away from.
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function reassignLinkedEntities($domains, array $options) {
    $count = 0;
    $field = $options['field'];
    $entity_typenames = $this->findDomainEnabledEntities($field);

    $new_domain = $this->getDomainInstanceFromPolicy($options['policy']);
    if (empty($new_domain)) {
      throw new DomainCommandException('invalid destination domain');
    }

    // For each entity type...
    $exceptions = FALSE;
    foreach ($entity_typenames as $name) {
      if (empty($options['entity_filter']) || $options['entity_filter'] === $name) {

        // For each domain being reassigned from...
        foreach ($domains as $domain) {
          $ids = $this->enumerateDomainEntities($name, $domain->id(), $field);
          if (!empty($ids)) {
            try {
              if ($options['chatty']) {
                $this->logger()->info('Reassigning @count @entity_name entities to @domain',
                  ['@entity_name'=>'',
                   '@count' => \count($ids),
                   '@domain' => $new_domain->id()]);
              }
             $count = $this->reassignEntities($name, $field, $domain, $new_domain, $ids);
            }
            catch (PluginException $e) {
              $exceptions = TRUE;
              $this->logger()->error('Unable to reassign content to @new_domain: plugin exception: @ex',
                ['@ex' => $e->getMessage(),
               '@new_domain' => $new_domain->id()]);
            }
            catch (EntityStorageException $e) {
              $exceptions = TRUE;
              $this->logger()->error('Unable to reassign content to @new_domain: storage exception: @ex',
                ['@ex' => $e->getMessage(),
                 '@new_domain' => $new_domain->id()]);
            }
          }
        }
      }
    }
    if ($exceptions) {
      throw new DomainCommandException('Errors encountered during reassign.');
    }

    return $count;
  }

}

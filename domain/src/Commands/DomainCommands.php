<?php

namespace Drupal\domain\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Drupal\Component\Plugin\Exception\PluginException;
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
   * Static array of special-case policies for reassigning content.
   *
   * @var string[]
   * */
  protected $reassignment_policies = ['prompt', 'default', 'ignore']; // + machine name;

  /**
   * Get a domain storage object or throw an exception.
   *
   * @return \Drupal\domain\DomainStorageInterface
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function getStorage() {
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
   * Lookup a string identifying a domain and return the object, or throw error.
   * 
   * @param string $argument
   *   The machine name, or the hostname, of an existing domain.
   *
   * @return \Drupal\domain\DomainInterface
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function getDomainFromArgument(string $argument) {
    $domain_storage = $this->getStorage();

    // Try loading domain assuming arg is a machine name.
    $domain = $domain_storage->load($argument);
    if (!$domain) {
      // Try loading assuming it is a host name.
      $domain = $domain_storage->loadByHostname($argument);
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
   * Filter a list of domains by excluding domains appearing in a specific list.
   *
   * @param DomainInterface[] $domains
   *   List of domains.
   * @param int[] $exclude
   *   List of domain names to exclude from the list.
   * @param DomainInterface[] $initial
   *   Initial value of list that will be returned.
   *
   * @return array
   */
  protected function filterDomains(array $domains, array $exclude, array $initial = []) {
    foreach ($domains as $domain) {
      // Exclude unwanted domains.
      if (!in_array($domain->id(), $exclude)) {
        $initial[$domain->id()] = $domain;
      }
    }
    return $initial;
  }

  /**
   * Check the domain response.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *
   * @return string
   */
  protected function checkHTTPResponse(DomainInterface $domain, $validate_url = FALSE) {
    // Ensure the url is rebuilt.
    if ($validate_url) {
      $code = $this->checkDomain($domain);
      echo "check: $code\n";
      // Some sort of success:
      return  $code >= 200 && $code <= 299;
    }
    // Not validating, so all is well!
    return TRUE;
  }

  /**
   * Helper function: check a domain is responsive and create it.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function checkCreatedDomain(DomainInterface $domain, array $values)
  {
    $valid = $this->checkHTTPResponse($domain, $values['validate_url']);
    if (!$valid) {
      throw new DomainCommandException(
        dt('Nobody listening on domain !d.', ['!d' => $domain->getHostname()])
      );
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
   * Checks a domain exists by trying to do an http request to it.
   *
   * @param $domain
   *   The domain to validate for syntax and uniqueness.
   *
   * @return int
   *   The server response code for the request.
   *
   * @see domain_validate()
   */
  protected function checkDomain($domain) {
    /** @var \Drupal\domain\DomainValidatorInterface $validator */
    $validator = \Drupal::service('domain.validator');
    return $validator->checkResponse($domain);
  }

  /**
   * Validates a domain exists by trying to do an http request to it.
   *
   * @param $domain
   *   The domain to validate for syntax and uniqueness.
   *
   * @return string[]
   *   Array of strings indicating issues found.
   *
   * @see domain_validate()
   */
  protected function validateDomain($domain) {
    /** @var \Drupal\domain\DomainValidatorInterface $validator */
    $validator = \Drupal::service('domain.validator');
    return $validator->validate($domain);
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
  protected function deleteDomain($domains) {
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
   * Determine a list of the entities that are domain enabled.
   *
   * @return string[]
   *   List of entities supporting domain access.
   */
  protected function findDomainEnabledEntities(string $using_field = DOMAIN_ACCESS_FIELD) {
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
   * Determine whether or not a given entity is domain-enabled.
   *
   * @return string[]
   *   List of entities supporting domain access.
   */
  protected function entityHasDomainField(string $entity_type, string $field = DOMAIN_ACCESS_FIELD) {
    // Try to avoid repeated calls to getFieldMap() assuming it's expensive.
    $this->ensureEntityFieldMap();
    return array_key_exists($field, $this->entity_field_map[$entity_type]);
  }

  /**
   * Ensure the local entity field map has been defined.
   *
   * @return string[]
   *   List of entities supporting domain access.
   */
  protected function ensureEntityFieldMap() {
    // Try to avoid repeated calls to getFieldMap() assuming it's expensive.
    if (empty($this->entity_field_map)) {
      $entity_manager         = \Drupal::entityManager();
      $this->entity_field_map = $entity_manager->getFieldMap();
    }
  }

  /**
   * Enumerate entities of the supplied type and domain.
   *
   * @param string $entity_type
   *   The entity type name, e.g. 'node'
   * @param string $domain
   *   The machine name of the domain to enumerate.
   * @param string $field
   *   The field to manipulate in the entity, e.g. DOMAIN_ACCESS_FIELD.
   *
   * @return int|string[]
   *   List of entity IDs for the selected domain.
   * @todo: should this really be a string[] of fields?
   */
  protected function enumerateDomainEntities(string $entity_type, string $domain, string $field, $just_count = FALSE) {
    if (!$this->entityHasDomainField($entity_type, $field)) {
      $this->logger()->info("Entity type @entity_type does not have field @field, so none found.",
        ['@entity_type'=> $entity_type,
         '@field' => $field]);
      return [];
    }

    $efq = \Drupal::entityQuery($entity_type);
    // Dont access check or we wont get all of the possible entities moved.
    $efq->accessCheck(FALSE);
    $efq->condition($field, $domain, '=');
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
   *   The field to manipulate in the entity, e.g. DOMAIN_ACCESS_FIELD.
   *   @todo: should this really be a string[] of fields?
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
  protected function reassignEntities(string $entity_type, string $field, DomainInterface $old_domain, DomainInterface $new_domain, $ids) {
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);

    // @TODO is there a problem loading many, possibly big, entities in one go?
    $entities = $entity_storage->loadMultiple($ids);

    // @TODO do we need to protect against $entity not having the field $field?
    /** @var \Drupal\domain\DomainInterface $entity */
    foreach($entities as $entity) {
      $changed = FALSE;
      // Multivalue fields are used, so check each one.
      foreach ($entity->get($field) as $k => $item) {
        // NB: NOT strict ==
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
  protected function getDomainInstanceFromPolicy(string $policy) {
    $domain_storage = $this->getStorage();
    switch($policy) {
      case 'default':
        $new_domain = $domain_storage->loadDefaultDomain();
        break;

      case 'prompt':
      case 'ignore':
        return NULL;

      default:
        $new_domain = $domain_storage->load($policy);
        break;
    }
    return $new_domain;
  }

  /**
   * Reassign entities of the supplied type to the $policy domain.
   *
   *  $options = [
   *    'entity_filter' => 'node',
   *    'policy' => 'prompt' | 'default' | 'ignore'
   *    'multidomain' => FALSE,
   *  ];
   *
   * @param DomainInterface[] $domains
   *   List of the domains to reassign content away from.
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  protected function reassignLinkedEntities($domains, array $options = ['chatty' => null, 'policy' => null]) {
    $entity_typenames = $this->findDomainEnabledEntities();
    // DOMAIN_ADMIN_FIELD too??
    $field_names = [DOMAIN_ACCESS_FIELD, DOMAIN_SOURCE_FIELD];

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

          // And, For each domain field ...
          foreach ($field_names as $field) {
            $ids = $this->enumerateDomainEntities($name, $domain->id(), $field);
            if (!empty($ids)) {

              try {
                if ($options['chatty']) {
                  $this->logger()->info("Reassigning @count @entity_name entities to @domain",
                    ['@entity_name'=>'',
                     '@count' => count($ids),
                     '@domain' => $new_domain->id()]);
                }

                $this->reassignEntities($name, $field, $domain, $new_domain, $ids);
              }
              catch (PluginException $e) {
                $exceptions = TRUE;
                $this->logger()->error("Unable to reassign content to @new_domain: plugin exception: @ex",
                  ['@ex' => $e->getMessage(),
                   '@new_domain' => $new_domain->id()]);
              }
              catch (EntityStorageException $e) {
                $exceptions = TRUE;
                $this->logger()->error("Unable to reassign content to @new_domain: storage exception: @ex",
                  ['@ex' => $e->getMessage(),
                   '@new_domain' => $new_domain->id()]);
              }
            }
          }
        }
      }
    }
    if ($exceptions) {
      throw new DomainCommandException('Errors encountered during reassign.');
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
   *   membership: Membership
   *   weight: Weight
   *   name: Name
   *   hostname: Hostname
   *   valid: HTTP Response
   *   scheme: Scheme
   *   status: Status
   *   is_default: Default
   *   domain_id: Domain Id
   *   id: Machine name
   * @default-fields id,name,hostname,valid,status,membership,is_default
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

    $entity_typenames = $this->findDomainEnabledEntities();
    $stats = [];
    $totals = [];
    foreach ($domains as $domain) {
      $stats[$domain->id()] = [];
      foreach ($entity_typenames as $name) {
        $stats[$domain->id()][$name] = $this->enumerateDomainEntities($name, $domain->id(), DOMAIN_ACCESS_FIELD, TRUE);
      }
      $totals[$domain->id()] = array_reduce($stats[$domain->id()], function ($sum, $v){ return $sum + $v; }, 0);
    }

    $keys = [
      'weight',
      'name',
      'hostname',
      'valid',
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
          case 'valid':
            try {
              $v = $this->checkDomain($domain);
            }
            catch(\GuzzleHttp\Exception\TransferException $ex) {
              $v = -2;
            }
            catch(\Exception $ex) {
              $v = -1;
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
      $row['membership'] = $totals[$domain->id()];
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
   * domain_access_entities: Domain access entities
   * all_affiliate_support: All-affiliate support
   * @list-orientation true
   * @format table
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function infoDomains() {
    $domain_storage = $this->getStorage();
    $default_domain = $domain_storage->loadDefaultDomain();

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
          $v = '-unset-';
          if ($default_domain) {
            $v = $default_domain->getDomainId();
          }
          break;
        case 'default_host':
          $v = '-unset-';
          if ($default_domain) {
            $v = $default_domain->getHostname();
          }
          break;
        case 'scheme':
          $v = implode(', ', array_keys($domain_storage->loadSchema()));
          break;
      }

      $rows[$key] = $v;
    }

    // Display which entities are enabled for domain access by checking for the fields.
    $entity_manager = \Drupal::entityManager();
    $field_map = $entity_manager->getFieldMap();
    $domain_entities = [];
    $domain_affiliate_entities = [];
    foreach($field_map as $type => $fields) {
      if (array_key_exists(DOMAIN_ACCESS_FIELD, $fields)) {
        $domain_entities[] = '+'.$type;
      }
      else {
        try {
          $def = $entity_manager->getDefinition($type, FALSE);
        } catch (PluginException $ex) {
          $domain_entities[] = '{notfound:' . $type . '}';
          continue;
        }
        if ($def->entityClassImplements('Drupal\Core\Entity\FieldableEntityInterface')) {
          // Entity is fieldable, so could have domain fields.
          $domain_entities[] = '-' . $type;
        }
        elseif ($def->entityClassImplements('Drupal\Core\Entity\ConfigEntityInterface')) {
          // Entity is configuration entity, which is not fieldable.
          $domain_entities[] = '!' . $type;
        }
        elseif ($def->entityClassImplements('Drupal\Core\Entity\EntityInterface')) {
          // Entity does not support fields.
          $domain_entities[] = '/' . $type;
        }
      }
      if (array_key_exists(DOMAIN_ACCESS_ALL_FIELD, $fields)) {
        $domain_affiliate_entities[] = $type;
      }
    }
    $rows['domain_access_entities'] = implode(', ', $domain_entities);
    $rows['all_affiliate_support'] = implode(', ', $domain_affiliate_entities);

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
   * @usage drush domain-add example.com 'My Test Site'
   * @usage drush domain-add example.com 'My Test Site' --inactive=1 --scheme=https
   * @usage drush domain-add example.com 'My Test Site' --weight=10
   * @usage drush domain-add example.com 'My Test Site' --validate=1
   *
   * @command domain:add
   * @aliases domain-add
   *
   * @return string
   *   The entity id of the created domain.
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function add($hostname, $name, array $options = ['inactive' => null, 'scheme' => null, 'weight' => null, 'is_default' => null, 'validate' => null]) {
    $domain_storage = $this->getStorage();

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

    $records_count = $domain_storage->getQuery()->count()->execute();
    $start_weight = $records_count + 1;
    $hostname = mb_strtolower($hostname);
    $values = array(
      'hostname' => $hostname,
      'name' => $name,
      'status' => (!$options['invalid']) ? 1 : 0,
      'scheme' => ($options['scheme']) ? $options['scheme'] : 'https',
      'weight' => ($weight = $options['weight']) ? $weight : $start_weight + 1,
      'is_default' => ($is_default = $options['is_default']) ? $is_default : 0,
      'id' => $domain_storage->createMachineName($hostname),
      'validate_url' => ($options['validate']) ? 1 : 0,
    );
    /** @var DomainInterface $domain */
    $domain = $domain_storage->create($values);
    $this->checkCreatedDomain($domain, $values);

    return $values['id'];
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
   * @usage drush domain:delete --content-as=example_net --users-as=example_net
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

    $this->is_dry_run = (bool)$options['dryrun'];

    //region Get current domain list and perform sanity checks.
    $domain_storage = $this->getStorage();
    $default_domain = $domain_storage->loadDefaultDomain();
    $all_domains = $domain_storage->loadMultipleSorted(NULL);

    if (empty($all_domains)) {
      throw new DomainCommandException('There are no configured domains.');
    }
    if (empty($domain_id)) {
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
      $noassign_domain = $domains;
      $noassign_domain[$default_domain->id()] = $default_domain;

      $reassign_list = $this->filterDomains($all_domains, $noassign_domain);
      $reassign_base = [
        'ignore' => dt('Do not reassign'),
        'default' => dt('Default domain'),
      ];
      $reassign_list = array_map(
        function (DomainInterface $d) {
          return $d->getHostname();
        },
        $reassign_list
      );
      $reassign_list = array_merge($reassign_base, $reassign_list);
      // asort($reassign_list);

      if ($policy_content === 'prompt') {
        $policy_content = $this->io()
                                 ->choice(dt('Reassign content to:'), $reassign_list);
      }
      if ($policy_users === 'prompt') {
        $policy_users = $this->io()
                              ->choice(dt('Reassign users to:'), $reassign_list);
      }
    }
    if ($policy_content === 'default') {
      $policy_content = $default_domain->id();
    }
    if ($policy_users === 'default') {
      $policy_users = $default_domain->id();
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
    
    $this->deleteDomain($domains, $options);
    $this->logger()->info(dt('Domain record deleted.'));
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
   *
   * @command domain:test
   * @aliases domain-test
   * 
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function test($domain_id, array $options = ['base_path' => null]) {
    $domain_storage = $this->getStorage();

    // TODO: This won't work in a subdirectory without a parameter.
    // RIC: What is the intended benaviour here?
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
   * @usage drush domain-default www.example.com
   * @usage drush domain-default example_org
   * @usage drush domain-default www.example.org --validate=1
   *
   * @command domain:default
   * @aliases domain-default
   *
   * @return string
   *   Ihe machine name of the default domain.
   *
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function defaultDomain($domain_id, array $options = ['validate' => null]) {
    $domain_storage = $this->getStorage();
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
    $domain = $domain_storage->loadDefaultDomain();
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
      }
      else {
        $this->logger()->info(dt('!domain is already disabled.',
          ['!domain' => $domain->getHostname()]));
      }
      if ($domain->status()) {
        return 'enabled';
      }
      else {
        return 'disabled';
      }
    }
    return 'unknown domain';
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
      }
      else {
        $this->logger()->info(dt('!domain is already enabled.',
          ['!domain' => $domain->getHostname()]));
      }
      if ($domain->status()) {
        return 'enabled';
      }
      else {
        return 'disabled';
      }
    }
    return 'unknown domain';
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
      return $domain->name();
    }
    return 'unknown domain';
  }

  /**
   * Changes a domain scheme.
   *
   * @param $domain_id
   *   The numeric id or hostname of the domain to change.
   * @param array $options
   *    An associative array of options whose values come from cli, aliases,
   *    config, etc.
   *
   * @usage drush domain-scheme example.com --https
   * @usage drush domain-scheme 681 https
   *
   * @option set
   *   Set the canonical domain scheme:
   *    - http: to http (no SSL).
   *    - https: to https (with SSL).
   *    - variable: match the scheme used by the request.
   *
   * @command domain:scheme
   * @aliases domain-scheme
   *
   * @return string
   * @throws \Drupal\domain\Commands\DomainCommandException
   */
  public function scheme($domain_id, $options = ['set' => null ]) {
    $new_scheme = NULL;

    // Resolve the domain.
    if ($domain = $this->getDomainFromArgument($domain_id)) {
      if (isset($options['set']) && $options['set'] === TRUE) {
        // --set with no value
        $new_scheme = $this->io()->choice(dt('Select the default http scheme:'),
          [
            '1' => dt('http'),
            '2' => dt('https'),
            '3' => dt('variable'),
          ]);
        // TODO: Something weird is going on here.
        echo 'selected: "' . var_export($new_scheme, TRUE) . '"' . PHP_EOL;
      }
      elseif (isset($options['set']) && strlen($options['set']) > 0) {
        // --set with a value
        $new_scheme = $options['set'];
      }
      // otherwise, --set is not present

      // If we were asked to change scheme, validate the value and do so.
      if (!empty($new_scheme)) {
        switch ($new_scheme) {
          case 'http':
          case '1':
            $new_scheme = 'http';
            break;

          case 'https':
          case '2':
            $new_scheme = 'https';
            break;

          case 'variable':
          case '3':
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
      return $domain->get('scheme');
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

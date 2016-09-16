<?php

namespace Drupal\domain;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Handles requests for token creation.
 *
 * TokenAPI still uses procedural code, but we have moved it to a class for
 * easier refactoring.
 */

/**
 * Token handler for Domain.
 */
class DomainToken {

  use StringTranslationTrait;

  /**
   * The Domain loader.
   *
   * @var \Drupal\domain\DomainLoaderInterface $loader
   */
  protected $loader;

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface $negotiator
   */
  protected $negotiator;

  /**
   * Constructs a DomainToken object.
   *
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainLoaderInterface $loader, DomainNegotiatorInterface $negotiator) {
    $this->loader = $loader;
    $this->negotiator = $negotiator;
  }

  /**
   * Implements hook_token_info().
   */
  public function getTokenInfo() {
    // Domain token types.
    $info['types']['domain'] = array(
      'name' => $this->t('Domains'),
      'description' => $this->t('Tokens related to domains.'),
      'needs-data' => 'domain',
    );
    // These two types require the Token contrib module.
    $info['types']['current-domain'] = array(
      'name' => $this->t('Current domain'),
      'description' => $this->t('Tokens related to the current domain.'),
      'type' => 'domain',
    );
    $info['types']['default-domain'] = array(
      'name' => $this->t('Default domain'),
      'description' => $this->t('Tokens related to the default domain.'),
      'type' => 'domain',
    );

    // Domain tokens.
    $info['tokens']['domain']['id'] = array(
      'name' => $this->t('Domain id'),
      'description' => $this->t('The domain\'s numeric ID.'),
    );
    $info['tokens']['domain']['machine-name'] = array(
      'name' => $this->t('Domain machine name'),
      'description' => $this->t('The domain machine identifier.'),
    );
    $info['tokens']['domain']['path'] = array(
      'name' => $this->t('Domain path'),
      'description' => $this->t('The base URL for the domain.'),
    );
    $info['tokens']['domain']['name'] = array(
      'name' => $this->t('Domain name'),
      'description' => $this->t('The domain name.'),
    );
    $info['tokens']['domain']['url'] = array(
      'name' => $this->t('Domain URL'),
      'description' => $this->t('The domain\'s URL for the current page request.'),
    );
    $info['tokens']['domain']['hostname'] = array(
      'name' => $this->t('Domain hostname'),
      'description' => $this->t('The domain hostname.'),
    );
    $info['tokens']['domain']['scheme'] = array(
      'name' => $this->t('Domain scheme'),
      'description' => $this->t('The domain scheme.'),
    );
    $info['tokens']['domain']['status'] = array(
      'name' => $this->t('Domain status'),
      'description' => $this->t('The domain status.'),
    );
    $info['tokens']['domain']['weight'] = array(
      'name' => $this->t('Domain weight'),
      'description' => $this->t('The domain weight.'),
    );
    $info['tokens']['domain']['is_default'] = array(
      'name' => $this->t('Domain default'),
      'description' => $this->t('The domain is the default domain.'),
    );

    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  public function getTokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = array();

    $domain = NULL;

    // Based on the type, get the proper domain context.
    switch ($type) {
      case 'domain':
        if (!empty($data['domain'])) {
          $domain = $data['domain'];
        }
        else {
          $domain = $this->negotiator->getActiveDomain();
        }
        break;
      case 'current-domain':
        $domain = $this->negotiator->getActiveDomain();
        break;
      case 'default-domain':
        $domain = $this->loader->loadDefaultDomain();
        break;
    }

    // Set the token information.
    if (!empty($domain)) {
      $callbacks = $this->getCallbacks();
      foreach ($tokens as $name => $original) {
        if (isset($callbacks[$name])) {
          $replacements[$original] = $domain->{$callbacks[$name]}();
          $bubbleable_metadata->addCacheableDependency($domain);
        }
      }
    }

    return $replacements;
  }

  /**
   * Maps tokens to their entity callbacks.
   *
   * We assume that the token will call an instance of DomainInterface.
   *
   * @return array
   */
  public function getCallbacks() {
    return [
      'id' => 'getDomainId',
      'machine-name' => 'id',
      'path' => 'getPath',
      'name' => 'label',
      'hostname' => 'getHostname',
      'scheme' => 'getScheme',
      'status' => 'status',
      'weight' => 'getWeight',
      'is_default' => 'isDefault',
      'url' => 'getUrl',
    ];
  }

}

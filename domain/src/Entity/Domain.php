<?php

namespace Drupal\domain\Entity;

use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiator;

/**
 * Defines the domain entity.
 *
 * @ConfigEntityType(
 *   id = "domain",
 *   label = @Translation("Domain record"),
 *   module = "domain",
 *   handlers = {
 *     "storage" = "Drupal\domain\DomainStorage",
 *     "access" = "Drupal\domain\DomainAccessControlHandler",
 *     "list_builder" = "Drupal\domain\DomainListBuilder",
 *     "form" = {
 *       "default" = "Drupal\domain\DomainForm",
 *       "edit" = "Drupal\domain\DomainForm",
 *       "delete" = "Drupal\domain\Form\DomainDeleteForm"
 *     }
 *   },
 *   config_prefix = "record",
 *   admin_permission = "administer domains",
 *   entity_keys = {
 *     "id" = "id",
 *     "domain_id" = "domain_id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/domain/delete/{domain}",
 *     "edit-form" = "/admin/config/domain/edit/{domain}",
 *     "collection" = "/admin/config/domain",
 *   },
 *   uri_callback = "domain_uri",
 *   config_export = {
 *     "id",
 *     "domain_id",
 *     "hostname",
 *     "name",
 *     "scheme",
 *     "status",
 *     "weight",
 *     "is_default",
 *   }
 * )
 */
class Domain extends ConfigEntityBase implements DomainInterface {

  use StringTranslationTrait;

  /**
   * The ID of the domain entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The domain record ID.
   *
   * @var int
   */
  protected $domain_id;

  /**
   * The domain list name (e.g. Drupal).
   *
   * @var string
   */
  protected $name;

  /**
   * The domain hostname (e.g. example.com).
   *
   * @var string
   */
  protected $hostname;

  /**
   * The domain record sort order.
   *
   * @var int
   */
  protected $weight;

  /**
   * Indicates the default domain.
   *
   * @var bool
   */
  protected $is_default = FALSE;

  /**
   * The domain record protocol (e.g. http://).
   *
   * @var string
   */
  protected $scheme;

  /**
   * The domain record base path, a calculated value.
   *
   * @var string
   */
  protected $path;

  /**
   * The domain record current url, a calculated value.
   *
   * @var string
   */
  protected $url;

  /**
   * The domain record http response test (e.g. 200), a calculated value.
   *
   * @var int
   */
  protected $response = NULL;

  /**
   * The redirect method to use, if needed.
   *
   * @var int|null
   */
  protected $redirect = NULL;

  /**
   * The type of match returned by the negotiator.
   *
   * @var int
   */
  protected $matchType;

  /**
   * The canonical hostname for the domain.
   *
   * @var string
   */
  protected $canonical;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $default = $domain_storage->loadDefaultId();
    $count = $storage_controller->getQuery()->count()->execute();
    $values += [
      'scheme' => empty($GLOBALS['is_https']) ? 'http' : 'https',
      'status' => 1,
      'weight' => $count + 1,
      'is_default' => (int) empty($default),
    ];
    // Note that we have not created a domain_id, which is only used for
    // node access control and will be added on save.
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    $negotiator = \Drupal::service('domain.negotiator');
    /** @var self $domain */
    $domain = $negotiator->getActiveDomain();
    if (empty($domain)) {
      return FALSE;
    }
    return ($this->id() == $domain->id());
  }

  /**
   * {@inheritdoc}
   */
  public function addProperty($name, $value) {
    if (!isset($this->{$name})) {
      $this->{$name} = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return (bool) $this->is_default;
  }

  /**
   * {@inheritdoc}
   */
  public function isHttps() {
    return (bool) ($this->getScheme(FALSE) == 'https');
  }

  /**
   * {@inheritdoc}
   */
  public function saveDefault() {
    if (!$this->isDefault()) {
      // Swap the current default.
      /** @var self $default */
      if ($default = \Drupal::entityTypeManager()->getStorage('domain')->loadDefaultDomain()) {
        $default->is_default = FALSE;
        $default->setHostname($default->getCanonical());
        $default->save();
      }
      // Save the new default.
      $this->is_default = TRUE;
      $this->setHostname($this->getCanonical());
      $this->save();
    }
    else {
      \Drupal::messenger()->addMessage($this->t('The selected domain is already the default.'), 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->setStatus(TRUE);
    $this->setHostname($this->getCanonical());
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    if (!$this->isDefault()) {
      $this->setStatus(FALSE);
      $this->setHostname($this->getCanonical());
      $this->save();
    }
    else {
      \Drupal::messenger()->addMessage($this->t('The default domain cannot be disabled.'), 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveProperty($name, $value) {
    if (isset($this->{$name})) {
      $this->{$name} = $value;
      $this->setHostname($this->getCanonical());
      $this->save();
      \Drupal::messenger()->addMessage($this->t('The @key attribute was set to @value for domain @hostname.', [
        '@key' => $name,
        '@value' => $value,
        '@hostname' => $this->hostname,
      ]));
    }
    else {
      \Drupal::messenger()->addMessage($this->t('The @key attribute does not exist.', ['@key' => $name]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setPath() {
    global $base_path;
    $this->path = $this->getScheme() . $this->getHostname() . ($base_path ?: '/');
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl() {
    $request = \Drupal::request();
    $uri = $request ? $request->getRequestUri() : '/';
    $this->url = $this->getScheme() . $this->getHostname() . $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    if (!isset($this->path)) {
      $this->setPath();
    }
    return $this->path;
  }

  /**
   * Returns the raw path of the domain object, without the base url.
   */
  public function getRawPath() {
    return $this->getScheme() . $this->getHostname();
  }

  /**
   * Builds a link from a known internal path.
   *
   * @param string $path
   *   A Drupal-formatted internal path, starting with /. Note that it is the
   *   caller's responsibility to handle the base_path().
   *
   * @return string
   *   The built link.
   */
  public function buildUrl($path) {
    return $this->getRawPath() . $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    if (!isset($this->url)) {
      $this->setUrl();
    }
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Sets the default domain properly.
    /** @var self $default */
    $default = $storage->loadDefaultDomain();
    if (!$default) {
      $this->is_default = TRUE;
    }
    elseif ($this->is_default && $default->getDomainId() != $this->getDomainId()) {
      // Swap the current default.
      $default->is_default = FALSE;
      $default->save();
    }
    // Ensures we have a proper domain_id but does not erase existing ones.
    if ($this->isNew() && empty($this->getDomainId())) {
      $this->createDomainId();
    }
    // Prevent duplicate hostname.
    $hostname = $this->getHostname();
    // Do not use domain loader because it may change hostname.
    $existing = $storage->loadByProperties(['hostname' => $hostname]);
    $existing = reset($existing);
    if ($existing && $this->getDomainId() != $existing->getDomainId()) {
      throw new ConfigValueException("The hostname ($hostname) is already registered.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    // Invalidate cache tags relevant to domains.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['rendered', 'url.site']);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach ($entities as $entity) {
      $actions = $storage->loadMultiple([
        'domain_default_action.' . $entity->id(),
        'domain_delete_action.' . $entity->id(),
        'domain_disable_action.' . $entity->id(),
        'domain_enable_action.' . $entity->id(),
      ]);
      foreach ($actions as $action) {
        $action->delete();
      }
    }
    // Invalidate cache tags relevant to domains.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['rendered', 'url.site']);
  }

  /**
   * {@inheritdoc}
   */
  public function createDomainId() {
    // We cannot reliably use sequences (1, 2, 3) because those can be different
    // across environments. Instead, we use the crc32 hash function to create a
    // unique numeric id for each domain. In some systems (Windows?) we have
    // reports of crc32 returning a negative number. Issue #2794047.
    // If we don't use hash(), then crc32() returns different results for 32-
    // and 64-bit systems. On 32-bit systems, the number returned may also be
    // too large for PHP.
    // See #2908236.
    $id = hash('crc32', $this->id());
    $id = abs(hexdec(substr($id, 0, -2)));
    $this->createNumericId($id);
  }

  /**
   * Creates a unique numeric id for use in the {node_access} table.
   *
   * @param int $id
   *   An integer to use as the numeric id.
   */
  public function createNumericId($id) {
    // Ensure that this value is unique.
    $storage = \Drupal::entityTypeManager()->getStorage('domain');
    $result = $storage->loadByProperties(['domain_id' => $id]);
    if (empty($result)) {
      $this->domain_id = $id;
    }
    else {
      $id++;
      $this->createNumericId($id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getScheme($add_suffix = TRUE) {
    $scheme = $this->scheme;
    if ($scheme == 'variable') {
      $scheme = \Drupal::entityTypeManager()->getStorage('domain')->getDefaultScheme();
    }
    elseif ($scheme != 'https') {
      $scheme = 'http';
    }
    $scheme .= ($add_suffix) ? '://' : '';

    return $scheme;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawScheme() {
    return $this->scheme;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    if (empty($this->response)) {
      $validator = \Drupal::service('domain.validator');
      $validator->checkResponse($this);
    }
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function setResponse($response) {
    $this->response = $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getLink($current_path = TRUE) {
    $options = ['absolute' => TRUE, 'https' => $this->isHttps()];
    if ($current_path) {
      $url = Url::fromUri($this->getUrl(), $options);
    }
    else {
      $url = Url::fromUri($this->getPath(), $options);
    }

    return Link::fromTextAndUrl($this->getCanonical(), $url)->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirect() {
    return $this->redirect;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirect($code = 302) {
    $this->redirect = $code;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostname() {
    return $this->hostname;
  }

  /**
   * {@inheritdoc}
   */
  public function setHostname($hostname) {
    $this->hostname = $hostname;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomainId() {
    return $this->domain_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setMatchType($match_type = DomainNegotiator::DOMAIN_MATCH_EXACT) {
    $this->matchType = $match_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getMatchType() {
    return $this->matchType;
  }

  /**
   * {@inheritdoc}
   */
  public function getPort() {
    $ports = explode(':', $this->getHostname());
    if (isset($ports[1])) {
      return ':' . $ports[1];
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setCanonical($hostname = NULL) {
    if (is_null($hostname)) {
      $this->canonical = $this->getHostname();
    }
    else {
      $this->canonical = $hostname;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCanonical() {
    if (empty($this->canonical)) {
      $this->setCanonical();
    }
    return $this->canonical;
  }

}

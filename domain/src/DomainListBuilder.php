<?php

namespace Drupal\domain;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * User interface for the domain overview screen.
 */
class DomainListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'domains';

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $destinationHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The domain entity access control handler.
   *
   * @var \Drupal\domain\DomainAccessControlHandler
   */
  protected $accessHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Domain storage handler.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The domain field element manager.
   *
   * @var \Drupal\domain\DomainElementManagerInterface
   */
  protected $domainElementManager;

  /**
   * The User storage handler.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The number of entities to list per page.
   *
   * DraggableListBuilder sets this to FALSE, which cancels any pagination.
   * Restore the default value from EntityListBuilder.
   *
   * @var int|false
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('current_user'),
      $container->get('redirect.destination'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('domain.element_manager')
    );
  }

  /**
   * Constructs a new DomainListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\domain\DomainStorageInterface $domain_storage
   *   The domain storage class.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The active user account.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $destination_handler
   *   The redirect destination helper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\domain\DomainElementManagerInterface $domain_element_manager
   *   The domain field element manager.
   */
  public function __construct(EntityTypeInterface $entity_type, DomainStorageInterface $domain_storage, AccountInterface $account, RedirectDestinationInterface $destination_handler, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, DomainElementManagerInterface $domain_element_manager) {
    parent::__construct($entity_type, $domain_storage);
    $this->entityTypeId = $entity_type->id();
    $this->domainStorage = $domain_storage;
    $this->entityType = $entity_type;
    $this->currentUser = $account;
    $this->destinationHandler = $destination_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->accessHandler = $this->entityTypeManager->getAccessControlHandler('domain');
    $this->moduleHandler = $module_handler;
    $this->domainElementManager = $domain_element_manager;
    $this->userStorage = $this->entityTypeManager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_admin_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $destination = $this->destinationHandler->getAsArray();
    $default = $entity->isDefault();
    $id = $entity->id();

    // If the user cannot edit domains, none of these actions are permitted.
    $access = $this->accessHandler->checkAccess($entity, 'update');
    if ($access->isForbidden()) {
      return $operations;
    }

    $super_admin = $this->currentUser->hasPermission('administer domains');
    if ($super_admin || $this->currentUser->hasPermission('access inactive domains')) {
      if ($entity->status() && !$default) {
        $operations['disable'] = [
          'title' => $this->t('Disable'),
          'url' => Url::fromRoute('domain.inline_action', ['op' => 'disable', 'domain' => $id]),
          'weight' => 50,
        ];
      }
      elseif (!$default) {
        $operations['enable'] = [
          'title' => $this->t('Enable'),
          'url' => Url::fromRoute('domain.inline_action', ['op' => 'enable', 'domain' => $id]),
          'weight' => 40,
        ];
      }
    }
    if (!$default && $super_admin) {
      $operations['default'] = [
        'title' => $this->t('Make default'),
        'url' => Url::fromRoute('domain.inline_action', ['op' => 'default', 'domain' => $id]),
        'weight' => 30,
      ];
    }
    if (!$default && $this->accessHandler->checkAccess($entity, 'delete')->isAllowed()) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.domain.delete_form', ['domain' => $id]),
        'weight' => 20,
      ];
    }
    $operations += $this->moduleHandler->invokeAll('domain_operations', [$entity, $this->currentUser]);
    foreach ($operations as $key => $value) {
      if (isset($value['query']['token'])) {
        $operations[$key]['query'] += $destination;
      }
    }
    /** @var DomainInterface $default */
    $default = $this->domainStorage->loadDefaultDomain();

    // Deleting the site default domain is not allowed.
    if ($default && $id == $default->id()) {
      unset($operations['delete']);
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['hostname'] = $this->t('Hostname');
    $header['status'] = $this->t('Status');
    $header['is_default'] = $this->t('Default');
    $header += parent::buildHeader();
    if (!$this->currentUser->hasPermission('administer domains')) {
      unset($header['weight']);
    }
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // If the user cannot view the domain, none of these actions are permitted.
    $admin = $this->accessHandler->checkAccess($entity, 'view');
    if ($admin->isForbidden()) {
      return;
    }

    $row['label'] = $this->getLabel($entity);
    $row['hostname'] = ['#markup' => $entity->getLink()];
    if ($entity->isActive()) {
      $row['hostname']['#prefix'] = '<strong>';
      $row['hostname']['#suffix'] = '</strong>';
    }
    $row['status'] = ['#markup' => $entity->status() ? $this->t('Active') : $this->t('Inactive')];
    $row['is_default'] = ['#markup' => ($entity->isDefault() ? $this->t('Yes') : $this->t('No'))];
    $row += parent::buildRow($entity);

    if (!$this->currentUser->hasPermission('administer domains')) {
      unset($row['weight']);
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form[$this->entitiesKey]['#domains'] = $this->entities;
    $form['actions']['submit']['#value'] = $this->t('Save configuration');
    // Only super-admins may sort domains.
    if (!$this->currentUser->hasPermission('administer domains')) {
      $form['actions']['submit']['#access'] = FALSE;
      unset($form['#tabledrag']);
    }
    // Delta is set after each row is loaded.
    $count = count($this->domainStorage->loadMultiple()) + 1;
    foreach (Element::children($form['domains']) as $key) {
      if (isset($form['domains'][$key]['weight'])) {
        $form['domains'][$key]['weight']['#delta'] = $count;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Overrides the parent method to prevent saving bad data.
   *
   * @link https://www.drupal.org/project/domain/issues/2925798
   * @link https://www.drupal.org/project/domain/issues/2925629
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue($this->entitiesKey) as $id => $value) {
      if (isset($this->entities[$id]) && $this->entities[$id]->get($this->weightKey) != $value['weight']) {
        // Reset weight properly.
        $this->entities[$id]->set($this->weightKey, $value['weight']);
        // Do not allow accidental hostname rewrites.
        $this->entities[$id]->set('hostname', $this->entities[$id]->getCanonical());
        $this->entities[$id]->save();
      }
    }
  }

  /**
   * Internal sort method for form weights.
   */
  private function sortByWeight($a, $b) {
    if ($a['weight'] < $b['weight']) {
      return 0;
    }
    return 1;
  }

  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as a form with pagination. This method overrides
   * both Drupal\Core\Config\Entity\DraggableListBuilder::render() and
   * Drupal\Core\Entity\EntityListBuilder::render().
   */
  public function render() {
    // Build the default form, which includes weights.
    $form = $this->formBuilder()->getForm($this);

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $form['pager'] = [
        '#type' => 'pager',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Loads entity IDs using a pager sorted by the entity weight. The default
   * behavior when using a limit is to sort by id.
   *
   * We also have to limit by assigned domains of the active user.
   *
   * See Drupal\Core\Entity\EntityListBuilder::getEntityIds()
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('weight'));

    // If the user cannot administer domains, we must filter the query further
    // by assigned IDs. We don't have to check permissions here, because that is
    // handled by the route system and buildRow(). There are two permissions
    // that allow users to view the entire list.
    if (!$this->currentUser->hasPermission('administer domains') && !$this->currentUser->hasPermission('view domain list')) {
      $user = $this->userStorage->load($this->currentUser->id());
      $allowed = $this->domainElementManager->getFieldValues($user, DOMAIN_ADMIN_FIELD);
      $query->condition('id', array_keys($allowed), 'IN');
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}

<?php

namespace Drupal\domain;

use Drupal\Core\Form\FormStateInterface;

/**
 * Handles hidden field options for domain entity references.
 *
 * Since domain options are restricted for various forms (users, nodes, source)
 * we have a base class for handling common use cases. The details of each
 * implementation are generally handled by a subclass and invoked within a
 * hook_form_alter().
 *
 * The default implementation, DomainElementManger, works with standard
 * multi-value domain entity reference fields.
 */
interface DomainElementManagerInterface {

  /**
   * Resets form options and stores hidden values that the user cannot change.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   * @param $field_name
   *   The name of the field to check.
   * @param boolean $hide_on_disallow
   *   If the field is set to a value that cannot be altered by the user who
   *   is not assigned to that domain, pass TRUE to remove the form element
   *   entirely. See DomainSourceElementManager for the use-case.
   *
   * @return array $form
   *   Return the modified form array.
   */
  public function setFormOptions(array $form, FormStateInterface $form_state, $field_name, $hide_on_disallow = FALSE);

  /**
   * Submit function for handling hidden values from a form.
   *
   * On form submit, loop through the hidden form values and add those to the
   * entity being saved.
   *
   * @param $form
   *   The form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return
   *   No return value. Hidden values are added to the field values directly.
   */
  public static function submitEntityForm(array &$form, FormStateInterface $form_state);

  /**
   * Finds options not accessible to the current user.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $field
   *   The field element being processed.
   */
  public function disallowedOptions(FormStateInterface $form_state, $field);

  /**
   * Stores a static list of fields that have been disallowed.
   *
   * @param $field_name
   *   The name of the field being processed. Inherited from setFormOptions.
   *
   * @return array
   *   An array of field names.
   */
  public function fieldList($field_name);

  /**
   * Gets the domain entity reference field values from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   * @param string $field_name
   *   The name of the field that holds our data.
   *
   * @return array
   *   The domain access field values, keyed by id (machine_name) with value of
   *   the numeric domain_id used by node access.
   */
  public function getFieldValues($entity, $field_name);

  /**
   * Returns the default submit handler to be used for a field element.
   *
   * @return string
   *   A fully-qualified class and method name, such as
   *   '\\Drupal\\domain\\DomainElementManager::submitEntityForm'
   *
   *   The method must be public and static, since it will be called from the
   *   form submit handler without knowledge of the parent class.
   *
   * The base implementat is submitEntityForm, and can be overridden by
   * specific subclasses.
   */
  public function getSubmitHandler();

}

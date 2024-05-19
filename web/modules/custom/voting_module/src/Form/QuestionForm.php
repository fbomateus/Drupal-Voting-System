<?php

namespace Drupal\voting_module\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Question entity edit forms.
 *
 * @ingroup voting_module
 */
class QuestionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\voting_module\Entity\Question $entity */
    $form = parent::buildForm($form, $form_state);

    // Additional customizations to the form can be added here.

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Custom validation for the identifier field.
    $identifier = $form_state->getValue('identifier');
    if (empty($identifier)) {
      $title = $form_state->getValue('title');
      $identifier = $this->generateIdentifierFromTitle($title);
      $form_state->setValue('identifier', $identifier);
    }

    // Check for unique identifier.
    if ($this->identifierExists($identifier, $this->entity->id())) {
      $form_state->setErrorByName('identifier', $this->t('The identifier %identifier already exists. Please choose another one.', ['%identifier' => $identifier]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Question.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Question.', [
          '%label' => $entity->label(),
        ]));
    }

    $form_state->setRedirect('entity.voting_module_question.collection');
  }

  /**
   * Generates a unique identifier based on the title.
   *
   * @param string $title
   *   The title of the question.
   *
   * @return string
   *   The generated identifier.
   */
  protected function generateIdentifierFromTitle($title) {
    // Replace spaces with underscores and remove special characters.
    $identifier = strtolower(preg_replace('/[^a-z0-9_]+/', '_', $title));
    return $identifier;
  }

  /**
   * Checks if the identifier already exists.
   *
   * @param string $identifier
   *   The identifier to check.
   * @param int|null $exclude_id
   *   The ID of the entity to exclude from the check (used during updates).
   *
   * @return bool
   *   TRUE if the identifier exists, FALSE otherwise.
   */
  protected function identifierExists($identifier, $exclude_id = NULL) {
    $query = $this->entityTypeManager->getStorage('voting_module_question')->getQuery()
      ->condition('identifier', $identifier)
      ->accessCheck(TRUE);

    if ($exclude_id) {
      $query->condition('id', $exclude_id, '<>');
    }

    return (bool) $query->count()->execute();
  }

}

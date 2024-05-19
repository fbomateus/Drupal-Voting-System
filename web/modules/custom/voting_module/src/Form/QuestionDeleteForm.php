<?php

namespace Drupal\voting_module\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Question entities.
 *
 * @ingroup voting_module
 */
class QuestionDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the question %title?', ['%title' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmMessage() {
    return $this->t('This action cannot be undone. This will permanently delete the question %title and all associated data.', ['%title' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone. This will permanently delete this question and all its associated data.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check if the question is associated with any answer options.
    $associated_answer_options = $this->getAssociatedAnswerOptions();
    if (!empty($associated_answer_options)) {
      $answer_option_ids = implode(', ', $associated_answer_options);
      $form_state->setErrorByName('question', $this->t('This question is associated with the following answer option IDs: %ids. Please remove the association before deleting the question.', ['%ids' => $answer_option_ids]));
    }
  }

  /**
   * Get the IDs of the associated answer options.
   *
   * @return array
   *   An array of associated answer option IDs.
   */
  protected function getAssociatedAnswerOptions() {
    $question_id = $this->entity->id();
    $answer_option_storage = \Drupal::entityTypeManager()->getStorage('voting_module_answer_option');
    $answer_option_ids = $answer_option_storage->getQuery()
      ->condition('question', $question_id)
      ->accessCheck(TRUE)
      ->execute();

    return $answer_option_ids;
  }

}

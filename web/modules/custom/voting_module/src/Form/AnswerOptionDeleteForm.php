<?php

namespace Drupal\voting_module\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting AnswerOption entities.
 *
 * @ingroup voting_module
 */
class AnswerOptionDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the answer option %label?', ['%label' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmMessage() {
    return $this->t('This action cannot be undone. This will permanently delete the answer option %label and all associated data.', ['%label' => $this->entity->label()]);
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
    return $this->t('This action cannot be undone. This will permanently delete this answer option and all its associated data.');
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

    // Check if the answer option is associated with any results.
    $associated_results = $this->getAssociatedResults();
    if (!empty($associated_results)) {
      $result_ids = implode(', ', $associated_results);
      $form_state->setErrorByName('answer_option', $this->t('This answer option is associated with the following result IDs: %ids. Please remove the association before deleting the answer option.', ['%ids' => $result_ids]));
    }
  }

  /**
   * Get the IDs of the associated results.
   *
   * @return array
   *   An array of associated result IDs.
   */
  protected function getAssociatedResults() {
    $answer_option_id = $this->entity->id();
    $result_storage = \Drupal::entityTypeManager()->getStorage('voting_module_result');
    $result_ids = $result_storage->getQuery()
      ->condition('answer_id', $answer_option_id)
      ->accessCheck(TRUE)
      ->execute();

    return $result_ids;
  }

}

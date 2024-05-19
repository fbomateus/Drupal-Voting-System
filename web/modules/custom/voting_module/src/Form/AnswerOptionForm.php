<?php

namespace Drupal\voting_module\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the AnswerOption entity edit forms.
 *
 * @ingroup voting_module
 */
class AnswerOptionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\voting_module\Entity\AnswerOption $entity */
    $form = parent::buildForm($form, $form_state);

    // Additional customizations to the form can be added here.

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Ensure that at least one of the fields (title, image, or description) is not empty.
    $title = $form_state->getValue('title')[0]['value'] ?? '';
    $image = $form_state->getValue('image')[0]['target_id'] ?? '';
    $description = $form_state->getValue('description')[0]['value'] ?? '';

    if (empty($title) && empty($image) && empty($description)) {
      $form_state->setErrorByName('title', $this->t('At least one of the fields Title, Image, or Description must be filled.'));
    }

    // Ensure the question is not associated with multiple answer options.
    $question_id = $form_state->getValue('question')[0]['target_id'] ?? NULL;
    if ($question_id) {
      $answer_option_storage = $this->entityTypeManager->getStorage('voting_module_answer_option');
      $existing_options = $answer_option_storage->loadByProperties(['question' => $question_id]);

      foreach ($existing_options as $existing_option) {
        if ($existing_option->id() != $this->entity->id()) {
          $form_state->setErrorByName('question', $this->t('The selected question already has an associated answer option.'));
          break;
        }
      }
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
        $this->messenger()->addStatus($this->t('Created the %label AnswerOption.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label AnswerOption.', [
          '%label' => $entity->label(),
        ]));
    }

    $form_state->setRedirect('entity.voting_module_answer_option.collection');
  }

}

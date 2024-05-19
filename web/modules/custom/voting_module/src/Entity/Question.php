<?php

namespace Drupal\voting_module\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Question entity.
 *
 * @ingroup voting_module
 *
 * @ContentEntityType(
 *   id = "voting_module_question",
 *   label = @Translation("Question"),
 *   base_table = "voting_module_question",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\voting_module\QuestionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\voting_module\Form\QuestionForm",
 *       "add" = "Drupal\voting_module\Form\QuestionForm",
 *       "edit" = "Drupal\voting_module\Form\QuestionForm",
 *       "delete" = "Drupal\voting_module\Form\QuestionDeleteForm"
 *     },
 *     "access" = "Drupal\voting_module\QuestionAccessControlHandler",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/question/{voting_module_question}",
 *     "add-form" = "/admin/content/question/add",
 *     "edit-form" = "/admin/content/question/{voting_module_question}/edit",
 *     "delete-form" = "/admin/content/question/{voting_module_question}/delete",
 *     "collection" = "/admin/content/question"
 *   }
 * )
 */
class Question extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the question.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setRequired(TRUE);

    $fields['identifier'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Identifier'))
      ->setDescription(t('The unique identifier for the question, generated from the title.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ]);

    // Additional fields for visibility, activation, and expiration
    $fields['visibility'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Visibility'))
      ->setDescription(t('Whether the question is visible to users.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -1,
      ]);

    $fields['activation_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Activation Date'))
      ->setDescription(t('The date when the question becomes active.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -1,
      ]);

    $fields['expiration_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Expiration Date'))
      ->setDescription(t('The date when the question expires.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -1,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Ensure the identifier is unique and formatted correctly.
    if (empty($this->get('identifier')->value)) {
      $this->set('identifier', $this->createUniqueIdentifier());
    }
  }

  /**
   * Create a unique identifier based on the title, replacing spaces with underscores and ensuring uniqueness.
   */
  protected function createUniqueIdentifier() {
    $base_id = strtolower(preg_replace('/\s+/', '_', $this->get('title')->value));
    $base_id = preg_replace('/[^a-z0-9_]/', '', $base_id);
    $query = \Drupal::entityQuery('voting_module_question')
      ->condition('identifier', $base_id, 'STARTS_WITH')
      ->accessCheck(TRUE);
    $ids = $query->execute();

    if (!empty($ids)) {
      $max = 1;
      foreach ($ids as $id) {
        $pieces = explode('_', $id);
        $last_piece = end($pieces);
        if (is_numeric($last_piece) && $last_piece >= $max) {
          $max = $last_piece + 1;
        }
      }
      $base_id .= '_' . $max;
    }

    return $base_id;
  }
}

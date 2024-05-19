<?php

namespace Drupal\voting_module\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Answer Option entity.
 *
 * @ingroup voting_module
 *
 * @ContentEntityType(
 *   id = "voting_module_answer_option",
 *   label = @Translation("Answer Option"),
 *   base_table = "voting_module_answer_option",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\voting_module\AnswerOptionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\voting_module\Form\AnswerOptionForm",
 *       "add" = "Drupal\voting_module\Form\AnswerOptionForm",
 *       "edit" = "Drupal\voting_module\Form\AnswerOptionForm",
 *       "delete" = "Drupal\voting_module\Form\AnswerOptionDeleteForm"
 *     },
 *     "access" = "Drupal\voting_module\AnswerOptionAccessControlHandler",
 *     "reference" = "Drupal\voting_module\Plugin\EntityReferenceSelection\QuestionReferenceSelection"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/answer_option/{voting_module_answer_option}",
 *     "add-form" = "/admin/content/answer_option/add",
 *     "edit-form" = "/admin/content/answer_option/{voting_module_answer_option}/edit",
 *     "delete-form" = "/admin/content/answer_option/{voting_module_answer_option}/delete",
 *     "collection" = "/admin/content/answer_option"
 *   }
 * )
 */
class AnswerOption extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the answer option.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ]);

    $fields['image'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Image'))
      ->setDescription(t('The image associated with the answer option.'))
      ->setSettings([
        'target_type' => 'file',
        'file_extensions' => 'png jpg jpeg',
        'file_directory' => 'voting_module_answer_option/images',
        'alt_field_required' => FALSE,
        'title_field_required' => FALSE,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'image',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => -3,
      ]);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A detailed description of the answer option.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -2,
      ]);

    $fields['question'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Question'))
      ->setDescription(t('The question this answer option belongs to.'))
      ->setSetting('target_type', 'voting_module_question')
      ->setSetting('handler', 'voting_module_question_filter')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Add any specific pre-save logic here if needed.
  }
}

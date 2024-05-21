<?php

namespace Drupal\voting_module\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the API Key entity.
 *
 * @ContentEntityType(
 *   id = "voting_module_api_key",
 *   label = @Translation("API Key"),
 *   base_table = "voting_module_api_key",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "key",
 *   },
 * )
 */
class ApiKey extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('API Key'))
      ->setDescription(t('The API key for accessing the voting API.'))
      ->setSettings([
        'max_length' => 64,
      ])
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the API key was created.'));

    return $fields;
  }

  /**
   * Gets the creation time of the API key.
   *
   * @return int
   *   The creation time as a Unix timestamp.
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

}

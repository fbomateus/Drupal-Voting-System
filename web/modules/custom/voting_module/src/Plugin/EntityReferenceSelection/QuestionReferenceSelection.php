<?php

namespace Drupal\voting_module\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Provides specific access control for the Question entity type.
 *
 * @EntityReferenceSelection(
 *   id = "voting_module_question_filter",
 *   label = @Translation("Question selection filter"),
 *   entity_types = {"voting_module_question"},
 *   group = "voting_module_question_filter",
 *   weight = 1
 * )
 */
class QuestionReferenceSelection extends DefaultSelection {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new QuestionReferenceSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    ModuleHandlerInterface $moduleHandler,
    AccountInterface $currentUser,
    EntityFieldManagerInterface $entityFieldManager,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    EntityRepositoryInterface $entityRepository,
    Connection $database
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityTypeManager, $moduleHandler, $currentUser, $entityFieldManager, $entityTypeBundleInfo, $entityRepository);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
  
    // Get the current entity and question IDs.
    $current_entity = $this->configuration['entity'] ?? NULL;
    $current_entity_id = $current_entity ? $current_entity->id() : NULL;
    $current_question_id = $current_entity ? $current_entity->get('question')->target_id : NULL;
  
    // Subquery to find question IDs already used in other answer options.
    $subquery = $this->database->select('voting_module_answer_option__question', 'aoq')
      ->fields('aoq', ['question_target_id'])
      ->distinct()
      ->execute()
      ->fetchCol();
  
    // Add condition to exclude already used questions, but allow the current question ID.
    if (!empty($subquery)) {
      if ($current_question_id) {
        $subquery = array_diff($subquery, [$current_question_id]);
      }
      if (!empty($subquery)) {
        $query->condition('id', $subquery, 'NOT IN');
      }
    }
  
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $entities = parent::getReferenceableEntities($match, $match_operator, $limit);

    // If no entities are found, add a message indicating that there are no selectable questions.
    if (empty($entities)) {
      $entities['none'] = ['none' => $this->t('No selectable questions available')];
    }

    return $entities;
  }
}

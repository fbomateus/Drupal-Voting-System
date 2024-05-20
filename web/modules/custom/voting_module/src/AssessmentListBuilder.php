<?php

namespace Drupal\voting_module;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\voting_module\Service\VotingResultsService;
use Drupal\Core\Link;

/**
 * Provides a list controller for the Assessment entity.
 *
 * @ingroup voting_module
 */
class AssessmentListBuilder extends EntityListBuilder {

  /**
   * The voting results service.
   *
   * @var \Drupal\voting_module\Service\VotingResultsService
   */
  protected $votingResultsService;

  /**
   * Constructs a new AssessmentListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\voting_module\Service\VotingResultsService $voting_results_service
   *   The voting results service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, VotingResultsService $voting_results_service) {
    parent::__construct($entity_type, $storage);
    $this->votingResultsService = $voting_results_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $entity_type_manager = $container->get('entity_type.manager');
    $storage = $entity_type_manager->getStorage($entity_type->id());

    return new static(
      $entity_type,
      $storage,
      $container->get('voting_module.voting_results_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['question'] = $this->t('Question');
    $header['total_votes'] = $this->t('Total Votes');
    $header['title_votes'] = $this->t('Title Votes');
    $header['description_votes'] = $this->t('Description Votes');
    $header['image_votes'] = $this->t('Image Votes');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\voting_module\Entity\Assessment $entity */
    $question = $entity->get('question_id')->entity;
    $total_votes = $this->votingResultsService->getTotalVotes($question);
    $results = $this->votingResultsService->getResults($question);
    $percentages = $this->votingResultsService->calculateVotePercentages($question);

    $row['question'] = Link::createFromRoute(
      $entity->get('question_id')->entity->label(),
      'entity.voting_module_question.canonical',
      ['voting_module_question' => $entity->get('question_id')->entity->id()]
    )->toString();
    $row['total_votes'] = $total_votes;

    $title_votes = isset($results['title']) ? $results['title']['count'] : 0;
    $description_votes = isset($results['description']) ? $results['description']['count'] : 0;
    $image_votes = isset($results['image']) ? $results['image']['count'] : 0;

    $row['title_votes'] = $title_votes . ' (' . round($percentages['title'] ?? 0, 2) . '%)';
    $row['description_votes'] = $description_votes . ' (' . round($percentages['description'] ?? 0, 2) . '%)';
    $row['image_votes'] = $image_votes . ' (' . round($percentages['image'] ?? 0, 2) . '%)';

    return $row + parent::buildRow($entity);
  }

}

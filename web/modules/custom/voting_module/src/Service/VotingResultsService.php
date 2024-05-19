<?php

namespace Drupal\voting_module\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\voting_module\Entity\Question;
use Drupal\voting_module\Event\VoteResultEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Service for handling voting results logic.
 */
class VotingResultsService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a VotingResultsService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Get the results for a question.
   *
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question entity.
   *
   * @return array
   *   An array of results, keyed by answer option ID.
   */
  public function getResults(Question $question) {
    $result_storage = $this->entityTypeManager->getStorage('voting_module_result');
    $query = $result_storage->getQuery()
      ->condition('question_id', $question->id())
      ->accessCheck(TRUE);
    $result_ids = $query->execute();

    $results = [];
    if ($result_ids) {
      $result_entities = $result_storage->loadMultiple($result_ids);
      foreach ($result_entities as $result) {
        /** @var \Drupal\voting_module\Entity\Result $result */
        $answer_id = $result->get('answer_id')->target_id;
        if (!isset($results[$answer_id])) {
          $results[$answer_id] = [
            'count' => 0,
          ];
        }
        $results[$answer_id]['count']++;
      }
    }

    // Dispatch the vote result event.
    $event = new VoteResultEvent($question, $results);
    $this->eventDispatcher->dispatch($event, 'voting_module.vote_result');

    return $results;
  }

  /**
   * Calculate the percentage of votes for each answer option.
   *
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question entity.
   *
   * @return array
   *   An array of percentages, keyed by answer option ID.
   */
  public function calculateVotePercentages(Question $question) {
    $results = $this->getResults($question);
    $total_votes = array_sum(array_column($results, 'count'));

    $percentages = [];
    if ($total_votes > 0) {
      foreach ($results as $answer_id => $data) {
        $percentages[$answer_id] = ($data['count'] / $total_votes) * 100;
      }
    }

    return $percentages;
  }

  /**
   * Get the total number of votes for a question.
   *
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question entity.
   *
   * @return int
   *   The total number of votes.
   */
  public function getTotalVotes(Question $question) {
    $results = $this->getResults($question);
    return array_sum(array_column($results, 'count'));
  }

  /**
   * Get the highest-rated answer for a question.
   *
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question entity.
   *
   * @return array
   *   An array containing the answer option ID and its count.
   */
  public function getHighestRatedAnswer(Question $question) {
    $results = $this->getResults($question);

    if (empty($results)) {
      return [];
    }

    $highest_rated_answer_id = array_keys($results, max(array_column($results, 'count')))[0];
    return [
      'answer_id' => $highest_rated_answer_id,
      'count' => $results[$highest_rated_answer_id]['count'],
    ];
  }
}

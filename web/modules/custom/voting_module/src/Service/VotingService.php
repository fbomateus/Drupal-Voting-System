<?php

namespace Drupal\voting_module\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\voting_module\Entity\Question;
use Drupal\voting_module\Entity\AnswerOption;
use Drupal\voting_module\Event\VoteEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Service for handling voting logic.
 */
class VotingService {

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
   * Constructs a VotingService object.
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
   * Process a vote from a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question being voted on.
   * @param \Drupal\voting_module\Entity\AnswerOption $answer
   *   The answer option chosen.
   *
   * @return bool
   *   TRUE if the vote was processed successfully, FALSE otherwise.
   */
  public function processVote(AccountInterface $account, Question $question, AnswerOption $answer) {
    // Ensure the question and answer are related.
    if (!$this->validateAnswerForQuestion($question, $answer)) {
      return FALSE;
    }

    // Create a new result entity.
    $result_storage = $this->entityTypeManager->getStorage('voting_module_result');
    $result = $result_storage->create([
      'user_id' => $account->id(),
      'question_id' => $question->id(),
      'answer_id' => $answer->id(),
      'timestamp' => \Drupal::time()->getRequestTime(),
    ]);
    $result->save();

    // Dispatch the vote event.
    $event = new VoteEvent($account, $question, $answer);
    $this->eventDispatcher->dispatch($event, 'voting_module.vote');

    return TRUE;
  }

  /**
   * Validate that an answer belongs to a question.
   *
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question entity.
   * @param \Drupal\voting_module\Entity\AnswerOption $answer
   *   The answer option entity.
   *
   * @return bool
   *   TRUE if the answer belongs to the question, FALSE otherwise.
   */
  protected function validateAnswerForQuestion(Question $question, AnswerOption $answer) {
    // Validate that the answer's question field matches the question ID.
    return $answer->get('question')->target_id == $question->id();
  }
}

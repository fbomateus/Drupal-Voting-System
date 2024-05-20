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
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user account.
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question being voted on.
   * @param \Drupal\voting_module\Entity\AnswerOption $answer
   *   The answer option chosen.
   * @param string $selected_option
   *   The type of option selected (title, description, or image).
   *
   * @return bool
   *   TRUE if the vote was processed successfully, FALSE otherwise.
   */
  public function processVote(AccountInterface $user, Question $question, AnswerOption $answer, $selected_option) {
    // Check if the user has already voted on this question, unless they are an administrator.
    if (!$user->hasPermission('administer site') && $this->hasUserVoted($user, $question)) {
      return FALSE;
    }

    // Create a new result entity.
    $result_storage = $this->entityTypeManager->getStorage('voting_module_result');
    $result = $result_storage->create([
      'user_id' => $user->id(),
      'question_id' => $question->id(),
      'answer_id' => $answer->id(),
      'selected_option' => $selected_option,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ]);
    $result->save();

    // Dispatch the vote event.
    $event = new VoteEvent($user, $question, $answer, $selected_option);
    $this->eventDispatcher->dispatch($event, 'voting_module.vote');

    return TRUE;
  }

  /**
   * Checks if the user has already voted on the given question.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question entity.
   *
   * @return bool
   *   TRUE if the user has already voted, FALSE otherwise.
   */
  protected function hasUserVoted(AccountInterface $user, $question) {
    $query = $this->entityTypeManager->getStorage('voting_module_result')->getQuery()
      ->condition('user_id', $user->id())
      ->condition('question_id', $question->id())
      ->accessCheck(TRUE);
    
    $results = $query->execute();
    return !empty($results);
  }
}

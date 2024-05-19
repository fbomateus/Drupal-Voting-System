<?php

namespace Drupal\voting_module\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\voting_module\Event\VoteEvent;
use Drupal\voting_module\Event\VoteResultEvent;
use Psr\Log\LoggerInterface;

/**
 * Event subscriber for voting events.
 */
class VotingEventSubscriber implements EventSubscriberInterface {

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a VotingEventSubscriber object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Reacts to vote events.
   *
   * @param \Drupal\voting_module\Event\VoteEvent $event
   *   The vote event.
   */
  public function onVote(VoteEvent $event) {
    $account = $event->getAccount();
    $question = $event->getQuestion();
    $answer = $event->getAnswer();

    $this->logger->info('User @user voted on question @question with answer @answer.', [
      '@user' => $account->getDisplayName(),
      '@question' => $question->label(),
      '@answer' => $answer->label(),
    ]);
  }

  /**
   * Reacts to vote result events.
   *
   * @param \Drupal\voting_module\Event\VoteResultEvent $event
   *   The vote result event.
   */
  public function onVoteResult(VoteResultEvent $event) {
    $question = $event->getQuestion();
    $results = $event->getResults();

    $this->logger->info('Results calculated for question @question.', [
      '@question' => $question->label(),
    ]);

    foreach ($results as $answer_id => $data) {
      $this->logger->info('Answer @answer received @count votes.', [
        '@answer' => $answer_id,
        '@count' => $data['count'],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['voting_module.vote'][] = ['onVote'];
    $events['voting_module.vote_result'][] = ['onVoteResult'];
    return $events;
  }
}

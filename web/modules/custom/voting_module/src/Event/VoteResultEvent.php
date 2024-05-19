<?php

namespace Drupal\voting_module\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\voting_module\Entity\Question;

/**
 * Defines the vote result event.
 */
class VoteResultEvent extends Event {

  /**
   * The question entity.
   *
   * @var \Drupal\voting_module\Entity\Question
   */
  protected $question;

  /**
   * The results of the vote.
   *
   * @var array
   */
  protected $results;

  /**
   * Constructs a VoteResultEvent object.
   *
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question entity.
   * @param array $results
   *   The results of the vote.
   */
  public function __construct(Question $question, array $results) {
    $this->question = $question;
    $this->results = $results;
  }

  /**
   * Gets the question entity.
   *
   * @return \Drupal\voting_module\Entity\Question
   *   The question entity.
   */
  public function getQuestion() {
    return $this->question;
  }

  /**
   * Gets the results of the vote.
   *
   * @return array
   *   The results of the vote.
   */
  public function getResults() {
    return $this->results;
  }

}

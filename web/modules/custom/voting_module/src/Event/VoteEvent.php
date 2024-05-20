<?php

namespace Drupal\voting_module\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\Core\Session\AccountInterface;
use Drupal\voting_module\Entity\Question;
use Drupal\voting_module\Entity\AnswerOption;

/**
 * Defines the vote event.
 */
class VoteEvent extends Event {

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The question entity.
   *
   * @var \Drupal\voting_module\Entity\Question
   */
  protected $question;

  /**
   * The answer option entity.
   *
   * @var \Drupal\voting_module\Entity\AnswerOption
   */
  protected $answer;

  /**
   * The selected option.
   *
   * @var string
   */
  protected $selectedOption;

  /**
   * Constructs a VoteEvent object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question being voted on.
   * @param \Drupal\voting_module\Entity\AnswerOption $answer
   *   The answer option chosen.
   * @param string $selected_option
   *   The selected option.
   */
  public function __construct(AccountInterface $account, Question $question, AnswerOption $answer, $selected_option) {
    $this->account = $account;
    $this->question = $question;
    $this->answer = $answer;
    $this->selectedOption = $selected_option;
  }

  /**
   * Gets the user account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user account.
   */
  public function getAccount() {
    return $this->account;
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
   * Gets the answer option entity.
   *
   * @return \Drupal\voting_module\Entity\AnswerOption
   *   The answer option entity.
   */
  public function getAnswer() {
    return $this->answer;
  }

  /**
   * Gets the selected option.
   *
   * @return string
   *   The selected option.
   */
  public function getSelectedOption() {
    return $this->selectedOption;
  }
}

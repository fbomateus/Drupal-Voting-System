<?php

namespace Drupal\Tests\voting_module\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Voting module.
 *
 * @group voting_module
 */
class VotingFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['voting_module', 'node', 'user'];

  /**
   * A user with permission to administer the voting module.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * A user with permission to vote.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $voterUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in an admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer nodes',
      'administer voting_module',
    ]);
    $this->drupalLogin($this->adminUser);

    // Create a voter user.
    $this->voterUser = $this->drupalCreateUser([
      'access content',
      'vote on questions',
    ]);
  }

  /**
   * Tests the creation of a question.
   */
  public function testCreateQuestion() {
    $this->drupalGet('entity/voting_module_question/add');
    $edit = [
      'title[0][value]' => 'Test Question',
      'identifier[0][value]' => 'test_question',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Created the Test Question.');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the voting process.
   */
  public function testVotingProcess() {
    // Log in as a voter user.
    $this->drupalLogin($this->voterUser);

    // Create a question to vote on.
    $question = $this->createQuestion([
      'title' => 'Vote Question',
      'identifier' => 'vote_question',
    ]);

    // Add answer options to the question.
    $option1 = $this->createAnswerOption($question, [
      'title' => 'Option 1',
    ]);
    $option2 = $this->createAnswerOption($question, [
      'title' => 'Option 2',
    ]);

    // Vote on the question.
    $this->drupalGet('vote/' . $question->id());
    $edit = ['answer' => $option1->id()];
    $this->submitForm($edit, t('Vote'));

    // Check that the vote was recorded.
    $this->assertSession()->pageTextContains('Your vote has been recorded.');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Creates a question entity for testing.
   *
   * @param array $values
   *   An array of values to set on the question entity.
   *
   * @return \Drupal\voting_module\Entity\Question
   *   The created question entity.
   */
  protected function createQuestion(array $values) {
    $question = $this->entityTypeManager->getStorage('voting_module_question')->create($values);
    $question->save();
    return $question;
  }

  /**
   * Creates an answer option entity for a question.
   *
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question entity.
   * @param array $values
   *   An array of values to set on the answer option entity.
   *
   * @return \Drupal\voting_module\Entity\AnswerOption
   *   The created answer option entity.
   */
  protected function createAnswerOption($question, array $values) {
    $values['question'] = $question->id();
    $answer_option = $this->entityTypeManager->getStorage('voting_module_answer_option')->create($values);
    $answer_option->save();
    return $answer_option;
  }

}

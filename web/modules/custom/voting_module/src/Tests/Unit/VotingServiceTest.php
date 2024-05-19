<?php

namespace Drupal\Tests\voting_module\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\voting_module\Service\VotingService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\voting_module\Entity\Question;
use Drupal\voting_module\Entity\AnswerOption;
use Drupal\Core\Session\AccountInterface;
use Drupal\voting_module\Entity\Result;
use Drupal\voting_module\Event\VoteEvent;

/**
 * Unit tests for the VotingService.
 *
 * @group voting_module
 */
class VotingServiceTest extends UnitTestCase {

  /**
   * The voting service.
   *
   * @var \Drupal\voting_module\Service\VotingService
   */
  protected $votingService;

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher mock.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The current user mock.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create mock objects.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $this->currentUser = $this->createMock(AccountInterface::class);

    // Instantiate the service with the mock objects.
    $this->votingService = new VotingService($this->entityTypeManager, $this->eventDispatcher);
  }

  /**
   * Tests the processVote method.
   */
  public function testProcessVote() {
    $question = $this->createMock(Question::class);
    $answer = $this->createMock(AnswerOption::class);
    $result_storage = $this->createMock(Result::class);

    // Mock the getStorage method to return the result storage mock.
    $this->entityTypeManager->method('getStorage')
      ->willReturn($result_storage);

    // Mock the creation and saving of a result entity.
    $result_storage->expects($this->once())
      ->method('create')
      ->willReturn($this->createMock(Result::class));

    $result_storage->expects($this->once())
      ->method('save');

    // Mock the event dispatcher dispatch method.
    $this->eventDispatcher->expects($this->once())
      ->method('dispatch')
      ->with($this->isInstanceOf(VoteEvent::class), 'voting_module.vote');

    // Assert that the processVote method returns TRUE.
    $this->assertTrue($this->votingService->processVote($this->currentUser, $question, $answer));
  }

  /**
   * Tests the validateAnswerForQuestion method.
   */
  public function testValidateAnswerForQuestion() {
    $question = $this->createMock(Question::class);
    $answer = $this->createMock(AnswerOption::class);

    // Mock the question and answer relationship.
    $answer->method('get')
      ->with('question')
      ->willReturn((object) ['target_id' => 1]);

    $question->method('id')
      ->willReturn(1);

    // Assert that the validateAnswerForQuestion method returns TRUE.
    $this->assertTrue($this->votingService->validateAnswerForQuestion($question, $answer));

    // Change the question ID in the answer and assert it returns FALSE.
    $answer->method('get')
      ->with('question')
      ->willReturn((object) ['target_id' => 2]);

    $this->assertFalse($this->votingService->validateAnswerForQuestion($question, $answer));
  }

}

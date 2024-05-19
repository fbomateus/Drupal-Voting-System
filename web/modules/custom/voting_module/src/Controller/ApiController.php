<?php

namespace Drupal\voting_module\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\voting_module\Service\VotingService;
use Drupal\voting_module\Service\VotingResultsService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApiController
 *
 * Provides API endpoints for the voting module.
 */
class ApiController extends ControllerBase {

  /**
   * The voting service.
   *
   * @var \Drupal\voting_module\Service\VotingService
   */
  protected $votingService;

  /**
   * The voting results service.
   *
   * @var \Drupal\voting_module\Service\VotingResultsService
   */
  protected $votingResultsService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ApiController object.
   *
   * @param \Drupal\voting_module\Service\VotingService $voting_service
   *   The voting service.
   * @param \Drupal\voting_module\Service\VotingResultsService $voting_results_service
   *   The voting results service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(VotingService $voting_service, VotingResultsService $voting_results_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->votingService = $voting_service;
    $this->votingResultsService = $voting_results_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('voting_module.voting_service'),
      $container->get('voting_module.voting_results_service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Endpoint to get all questions.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing all questions.
   */
  public function getAllQuestions() {
    $questions = $this->entityTypeManager->getStorage('voting_module_question')->loadMultiple();
    $data = [];

    foreach ($questions as $question) {
      $data[] = [
        'id' => $question->id(),
        'label' => $question->label(),
        'identifier' => $question->get('identifier')->value,
      ];
    }

    return new JsonResponse($data);
  }

  /**
   * Endpoint to get question details by ID.
   *
   * @param int $question_id
   *   The question entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing the question details.
   */
  public function getQuestion($question_id) {
    $question = $this->entityTypeManager->getStorage('voting_module_question')->load($question_id);

    if ($question) {
      $data = [
        'id' => $question->id(),
        'label' => $question->label(),
        'identifier' => $question->get('identifier')->value,
        'answers' => [],
      ];

      $answer_options = $this->entityTypeManager->getStorage('voting_module_answer_option')->loadByProperties(['question' => $question_id]);
      foreach ($answer_options as $answer) {
        $data['answer_options'][] = [
          'id' => $answer->id(),
          'label' => $answer->label(),
          'description' => $answer->get('description')->value,
          'image' => $answer->get('image')->target_id,
        ];
      }

      return new JsonResponse($data);
    }

    return new JsonResponse(['message' => 'Question not found'], 404);
  }

  /**
   * Endpoint to submit a vote.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing vote data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response indicating the result of the vote submission.
   */
  public function submitVote(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $user = \Drupal::currentUser();
    $question_id = $data['question_id'];
    $answer_id = $data['answer_id'];

    $question = $this->entityTypeManager->getStorage('voting_module_question')->load($question_id);
    $answer = $this->entityTypeManager->getStorage('voting_module_answer_option')->load($answer_id);

    if ($question && $answer) {
      $result = $this->votingService->processVote($user, $question, $answer);
      if ($result) {
        return new JsonResponse(['message' => 'Vote submitted successfully']);
      }
    }

    return new JsonResponse(['message' => 'Vote submission failed'], 400);
  }

  /**
   * Endpoint to get voting results for a question.
   *
   * @param int $question_id
   *   The question entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing the voting results.
   */
  public function getResults($question_id) {
    $question = $this->entityTypeManager->getStorage('voting_module_question')->load($question_id);

    if ($question) {
      $results = $this->votingResultsService->getResults($question);
      $percentages = $this->votingResultsService->calculateVotePercentages($question);

      $data = [
        'question' => $question->label(),
        'results' => $results,
        'percentages' => $percentages,
      ];

      return new JsonResponse($data);
    }

    return new JsonResponse(['message' => 'Question not found'], 404);
  }

}

<?php

namespace Drupal\voting_module\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\voting_module\Service\VotingService;
use Drupal\voting_module\Service\VotingResultsService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

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
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new ApiController object.
   *
   * @param \Drupal\voting_module\Service\VotingService $voting_service
   *   The voting service.
   * @param \Drupal\voting_module\Service\VotingResultsService $voting_results_service
   *   The voting results service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   */
  public function __construct(VotingService $voting_service, VotingResultsService $voting_results_service, EntityTypeManagerInterface $entity_type_manager, FileUrlGeneratorInterface $file_url_generator) {
    $this->votingService = $voting_service;
    $this->votingResultsService = $voting_results_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('voting_module.voting_service'),
      $container->get('voting_module.voting_results_service'),
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * Validates the API key for third-party requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if the API key is valid, FALSE otherwise.
   */
  protected function validateApiKey(Request $request) {
    $api_key = $request->headers->get('Authorization');
    if (strpos($api_key, 'Bearer ') === 0) {
      $api_key = substr($api_key, 7);
    }
    $keys = $this->entityTypeManager->getStorage('voting_module_api_key')->loadByProperties(['key' => $api_key]);
    return !empty($keys);
  }

  /**
   * Validates the API key for server-side requests.
   *
   * @return bool
   *   TRUE if the API key is valid, FALSE otherwise.
   */
  protected function validateServerApiKey() {
    $config = $this->config('voting_module.settings');
    $api_key = $config->get('api_key');
    $keys = $this->entityTypeManager->getStorage('voting_module_api_key')->loadByProperties(['key' => $api_key]);
    return !empty($keys);
  }

  /**
   * Endpoint to get all questions.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing all questions.
   */
  public function getAllQuestions(Request $request) {
    if (!$this->validateApiKey($request)) {
      return new JsonResponse(['message' => 'Unauthorized'], 401);
    }

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
  public function getQuestion(Request $request, $question_id) {
    if (!$this->validateApiKey($request)) {
      return new JsonResponse(['message' => 'Unauthorized'], 401);
    }

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
          'image' => $this->getImageUrl($answer->get('image')->entity),
        ];
      }

      return new JsonResponse($data);
    }

    return new JsonResponse(['message' => 'Question not found'], 404);
  }

  /**
   * Submits a vote from the server-side.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing vote data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response indicating the result of the vote submission.
   */
  public function submitServerSideVote(Request $request) {
    if (!$this->validateServerApiKey()) {
      return new JsonResponse(['message' => 'Unauthorized'], 401);
    }

    return $this->processVoteSubmission($request);
  }

  /**
   * Submits a vote from third-party applications.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing vote data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response indicating the result of the vote submission.
   */
  public function submitThirdPartyVote(Request $request) {
    if (!$this->validateApiKey($request)) {
      return new JsonResponse(['message' => 'Unauthorized'], 401);
    }

    return $this->processVoteSubmission($request);
  }

  /**
   * Processes the vote submission.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object containing vote data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response indicating the result of the vote submission.
   */
  private function processVoteSubmission(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $user = \Drupal::currentUser();
    $question_id = $data['question_id'];
    $answer_id = $data['answer_id'];
    $selected_option = $data['selected_option'];

    $question = $this->entityTypeManager->getStorage('voting_module_question')->load($question_id);
    $answer = $this->entityTypeManager->getStorage('voting_module_answer_option')->load($answer_id);

    if ($question && $answer) {
      // Process the vote and save the selected option.
      $result = $this->votingService->processVote($user, $question, $answer, $selected_option);
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
  public function getResults(Request $request, $question_id) {
    if (!$this->validateApiKey($request)) {
      return new JsonResponse(['message' => 'Unauthorized'], 401);
    }

    $question = $this->entityTypeManager->getStorage('voting_module_question')->load($question_id);

    if ($question) {
      $results = $this->votingResultsService->getResults($question);
      $percentages = $this->votingResultsService->calculateVotePercentages($question);

      $data = [
        'question' => $question->label(),
        'results' => $results,
        'percentages' => $percentages,
        'selected_option' => [],
      ];

      foreach ($results as $answer_id => $result) {
        $data['selected_option'][] = $result['selected_option'] ?? 'N/A';
      }

      return new JsonResponse($data);
    }

    return new JsonResponse(['message' => 'Question not found'], 404);
  }

  /**
   * Gets the URL of an image file entity.
   *
   * @param \Drupal\file\FileInterface|null $file
   *   The file entity.
   *
   * @return string
   *   The URL of the image.
   */
  protected function getImageUrl($file) {
    if ($file) {
      return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    }
    return '';
  }

}

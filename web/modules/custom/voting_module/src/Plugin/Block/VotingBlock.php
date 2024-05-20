<?php

namespace Drupal\voting_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\voting_module\Service\VotingService;
use Drupal\voting_module\Service\VotingResultsService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Provides a 'VotingBlock' block.
 *
 * @Block(
 *   id = "voting_block",
 *   admin_label = @Translation("Voting Block"),
 *   category = @Translation("Custom")
 * )
 */
class VotingBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new VotingBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\voting_module\Service\VotingService $voting_service
   *   The voting service.
   * @param \Drupal\voting_module\Service\VotingResultsService $voting_results_service
   *   The voting results service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   */
  public function __construct(
      array $configuration, 
      $plugin_id, 
      $plugin_definition, 
      EntityTypeManagerInterface $entity_type_manager, 
      AccountInterface $current_user, 
      VotingService $voting_service, 
      VotingResultsService $voting_results_service, 
      ConfigFactoryInterface $config_factory, 
      FileUrlGeneratorInterface $file_url_generator
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->votingService = $voting_service;
    $this->votingResultsService = $voting_results_service;
    $this->configFactory = $config_factory;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('voting_module.voting_service'),
      $container->get('voting_module.voting_results_service'),
      $container->get('config.factory'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('voting_module.settings');
    $enable_voting = $config->get('enable_voting');
    $show_results = $config->get('show_results');
    $is_logged_in = $this->currentUser->isAuthenticated();

    $questions = $this->entityTypeManager->getStorage('voting_module_question')->loadMultiple();
    $options = [];
    $votes = $this->votingResultsService->getUserVotes($this->currentUser->id());

    foreach ($questions as $question) {
      /* @var \Drupal\voting_module\Entity\Question $question */
      // Skip the question if its visibility is set to false.
      if (!$question->get('visibility')->value) {
        continue;
      }

      $answer_options = $enable_voting ? $this->getAnswerOptions($question) : [];
      $has_voted = !$this->currentUser->hasPermission('administer site') && isset($votes[$question->id()]);
      $total_votes = $show_results ? $this->votingResultsService->getTotalVotes($question) : NULL;

      $options[$question->id()] = [
        'label' => $question->label(),
        'answer_options' => $answer_options,
        'has_voted' => $has_voted,
        'total_votes' => $total_votes,
        'can_vote' => $enable_voting && $is_logged_in && !$has_voted,
        'show_results' => $show_results,
      ];
    }

    return [
      '#theme' => 'voting_block',
      '#questions' => $options,
      '#attached' => [
        'library' => [
          'voting_module/voting_block',
        ],
      ],
    ];
  }

  /**
   * Retrieves the answer options for a given question.
   *
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question entity.
   *
   * @return array
   *   An array of answer options with image, title, and description.
   */
  protected function getAnswerOptions($question) {
    $answer_options = $this->entityTypeManager->getStorage('voting_module_answer_option')->loadByProperties(['question' => $question->id()]);
    $options = [];

    foreach ($answer_options as $answer_option) {
      /* @var \Drupal\voting_module\Entity\AnswerOption $answer_option */
      $option = [];
      if ($answer_option->get('title')->value) {
        $option['title'] = $answer_option->get('title')->value;
      }
      if ($answer_option->get('image')->entity) {
        $option['image'] = $this->getImageUrl($answer_option->get('image')->entity);
      }
      if ($answer_option->get('description')->value) {
        $option['description'] = $answer_option->get('description')->value;
      }

      if (!empty($option)) {
        $options[$answer_option->id()] = $option;
      }
    }

    return $options;
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

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}

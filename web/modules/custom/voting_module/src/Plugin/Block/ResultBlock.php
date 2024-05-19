<?php

namespace Drupal\voting_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\voting_module\Service\VotingResultsService;

/**
 * Provides a 'ResultBlock' block.
 *
 * @Block(
 *   id = "result_block",
 *   admin_label = @Translation("Result Block"),
 *   category = @Translation("Custom")
 * )
 */
class ResultBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The voting results service.
   *
   * @var \Drupal\voting_module\Service\VotingResultsService
   */
  protected $votingResultsService;

  /**
   * Constructs a new ResultBlock instance.
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
   * @param \Drupal\voting_module\Service\VotingResultsService $voting_results_service
   *   The voting results service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, VotingResultsService $voting_results_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->votingResultsService = $voting_results_service;
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
      $container->get('voting_module.voting_results_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $question_id = $this->configuration['question'];
    $question = $this->entityTypeManager->getStorage('voting_module_question')->load($question_id);

    if (!$question) {
      return [
        '#markup' => $this->t('No question selected or the selected question does not exist.'),
      ];
    }

    $results = $this->votingResultsService->getResults($question);
    $percentages = $this->votingResultsService->calculateVotePercentages($question);

    return [
      '#theme' => 'result_block',
      '#question' => $question->label(),
      '#results' => $results,
      '#percentages' => $percentages,
      '#attached' => [
        'library' => [
          'voting_module/result_block',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['question'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Question'),
      '#options' => $this->getQuestions(),
      '#default_value' => $this->configuration['question'] ?? '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['question'] = $form_state->getValue('question');
  }

  /**
   * Retrieves the list of questions.
   *
   * @return array
   *   An array of question labels keyed by their IDs.
   */
  protected function getQuestions() {
    $questions = $this->entityTypeManager->getStorage('voting_module_question')->loadMultiple();
    $options = [];
    foreach ($questions as $question) {
      $options[$question->id()] = $question->label();
    }
    return $options;
  }

}

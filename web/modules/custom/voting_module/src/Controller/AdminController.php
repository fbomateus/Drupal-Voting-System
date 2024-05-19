<?php

namespace Drupal\voting_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AdminController
 *
 * Provides administrative pages for the Voting module.
 */
class AdminController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AdminController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Page callback to display detailed view of a question and its answer options.
   *
   * @param int $question_id
   *   The question entity ID.
   *
   * @return array
   *   A render array representing the detailed view.
   */
  public function viewQuestion($question_id) {
    $question = $this->entityTypeManager->getStorage('voting_module_question')->load($question_id);

    if (!$question) {
      throw new NotFoundHttpException();
    }

    $answers = $this->entityTypeManager->getStorage('voting_module_answer_option')->loadByProperties(['question' => $question_id]);

    return [
      '#theme' => 'admin_question_view',
      '#question' => $question,
      '#answers' => $answers,
      '#attached' => [
        'library' => [
          'voting_module/admin_styles',
        ],
      ],
    ];
  }

  /**
   * Page callback to list all questions with links to their detailed views.
   *
   * @return array
   *   A render array representing the list view.
   */
  public function listQuestions() {
    $questions = $this->entityTypeManager->getStorage('voting_module_question')->loadMultiple();
    $items = [];

    foreach ($questions as $question) {
      $items[] = [
        'text' => $question->label(),
        'url' => $question->toUrl('canonical')->toString(),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Questions'),
      '#attached' => [
        'library' => [
          'voting_module/admin_styles',
        ],
      ],
    ];
  }

}

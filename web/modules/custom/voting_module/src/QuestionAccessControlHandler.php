<?php

namespace Drupal\voting_module;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\voting_module\Entity\Question;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the Question entity.
 *
 * @see \Drupal\voting_module\Entity\Question
 */
class QuestionAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\voting_module\Entity\Question $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view question entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit question entities');

      case 'delete':
        // Prevent deletion if the question is referenced by any answer options.
        if ($this->isQuestionReferenced($entity)) {
          return AccessResult::forbidden()->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'delete question entities');
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add question entities');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_field.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Checks if a question is referenced by any answer options.
   *
   * @param \Drupal\voting_module\Entity\Question $question
   *   The question entity.
   *
   * @return bool
   *   TRUE if the question is referenced by any answer options, FALSE otherwise.
   */
  protected function isQuestionReferenced(Question $question) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $answer_option_storage = $entity_type_manager->getStorage('voting_module_answer_option');
    $query = $answer_option_storage->getQuery()
      ->condition('question', $question->id())
      ->accessCheck(TRUE);
    $results = $query->execute();

    return !empty($results);
  }
}

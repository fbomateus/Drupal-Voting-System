<?php

namespace Drupal\voting_module;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a list controller for the Question entity.
 *
 * @ingroup voting_module
 */
class QuestionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    $header['visibility'] = $this->t('Visibility');
    $header['operations'] = $this->t('Operations');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\voting_module\Entity\Question $entity */
    $row['id'] = $entity->id();
    $row['title'] = Link::createFromRoute(
      $entity->label(),
      'entity.voting_module_question.canonical',
      ['voting_module_question' => $entity->id()]
    )->toString();
    $row['visibility'] = $entity->get('visibility')->value ? $this->t('Visible') : $this->t('Hidden');
    $row['operations'] = $this->buildOperations($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['add_question'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Question'),
      '#url' => Url::fromRoute('entity.voting_module_question.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];
    $build += parent::render();
    return $build;
  }

  /**
   * Builds the operations links for each row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the operations.
   *
   * @return array
   *   An associative array of operations.
   */
  public function buildOperations(EntityInterface $entity) {
    $operations = parent::buildOperations($entity);
    if ($entity->access('update')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.voting_module_question.edit_form', ['voting_module_question' => $entity->id()])->toString(),
      ];
    }
    if ($entity->access('delete')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.voting_module_question.delete_form', ['voting_module_question' => $entity->id()])->toString(),
      ];
    }
    return $operations;
  }

}

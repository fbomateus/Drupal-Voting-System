<?php

namespace Drupal\voting_module;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a list controller for the AnswerOption entity.
 *
 * @ingroup voting_module
 */
class AnswerOptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    $header['operations'] = $this->t('Operations');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\voting_module\Entity\AnswerOption $entity */
    $row['id'] = $entity->id();
    $row['title'] = Link::createFromRoute(
      $entity->label(),
      'entity.voting_module_answer_option.canonical',
      ['voting_module_answer_option' => $entity->id()]
    )->toString();
    $row['operations'] = $this->buildOperations($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['add_answer_option'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Answer Option'),
      '#url' => Url::fromRoute('entity.voting_module_answer_option.add_form'),
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
    $operations = [];
    if ($entity->access('update')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.voting_module_answer_option.edit_form', ['voting_module_answer_option' => $entity->id()]),
      ];
    }
    if ($entity->access('delete')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.voting_module_answer_option.delete_form', ['voting_module_answer_option' => $entity->id()]),
      ];
    }

    // Convert the operations into renderable links.
    $links = [];
    foreach ($operations as $key => $operation) {
      $links[$key] = [
        'title' => $operation['title'],
        'url' => $operation['url'],
        'attributes' => [],
      ];
    }

    return [
      'data' => [
        '#type' => 'operations',
        '#links' => $links,
      ],
    ];
  }

}

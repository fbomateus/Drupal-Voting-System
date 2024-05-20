<?php

namespace Drupal\voting_module;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Provides a list controller for the Result entity.
 *
 * @ingroup voting_module
 */
class ResultListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['user'] = $this->t('User');
    $header['question'] = $this->t('Question');
    $header['answer'] = $this->t('Answer');
    $header['selected_option'] = $this->t('Selected Option');
    $header['timestamp'] = $this->t('Timestamp');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\voting_module\Entity\Result $entity */
    $row['id'] = $entity->id();
    $row['user'] = $entity->get('user_id')->entity->getDisplayName();
    $row['question'] = Link::createFromRoute(
      $entity->get('question_id')->entity->label(),
      'entity.voting_module_question.canonical',
      ['voting_module_question' => $entity->get('question_id')->entity->id()]
    )->toString();
    $row['answer'] = Link::createFromRoute(
      $entity->get('answer_id')->entity->label(),
      'entity.voting_module_answer_option.canonical',
      ['voting_module_answer_option' => $entity->get('answer_id')->entity->id()]
    )->toString();
    $row['selected_option'] = $entity->get('selected_option')->value;
    $row['timestamp'] = \Drupal::service('date.formatter')->format($entity->get('timestamp')->value, 'short');
    return $row + parent::buildRow($entity);
  }

}

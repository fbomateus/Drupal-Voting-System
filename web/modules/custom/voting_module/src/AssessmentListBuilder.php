<?php

namespace Drupal\voting_module;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Provides a list controller for the Assessment entity.
 *
 * @ingroup voting_module
 */
class AssessmentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    $header['created'] = $this->t('Created');
    $header['question'] = $this->t('Question');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\voting_module\Entity\Assessment $entity */
    $row['id'] = $entity->id();
    $row['title'] = Link::createFromRoute(
      $entity->label(),
      'entity.voting_module_assessment.canonical',
      ['voting_module_assessment' => $entity->id()]
    )->toString();
    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short');
    $row['question'] = $entity->get('question')->entity->label();
    $row['status'] = $entity->get('status')->value ? $this->t('Active') : $this->t('Inactive');
    return $row + parent::buildRow($entity);
  }

}

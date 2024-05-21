<?php

namespace Drupal\voting_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApiKeyForm.
 */
class ApiKeyForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ApiKeyForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'voting_module_api_key_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['generate_key'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate API Key'),
    ];

    $keys = $this->entityTypeManager->getStorage('voting_module_api_key')->loadMultiple();

    $form['keys'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('API Key'),
        $this->t('Created'),
        $this->t('Operations'),
      ],
    ];

    foreach ($keys as $key) {
      $form['keys'][$key->id()]['key'] = [
        '#markup' => $key->label(),
      ];
      $form['keys'][$key->id()]['created'] = [
        '#markup' => \Drupal::service('date.formatter')->format($key->getCreatedTime()),
      ];
      $form['keys'][$key->id()]['operations'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#submit' => [[$this, 'deleteKey']],
        '#name' => 'delete_' . $key->id(),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $key = bin2hex(random_bytes(32));

    $key_entity = $this->entityTypeManager->getStorage('voting_module_api_key')->create([
      'key' => $key,
    ]);
    $key_entity->save();

    $this->messenger()->addStatus($this->t('Generated new API key: @key', ['@key' => $key]));
    $form_state->setRebuild();
  }

  /**
   * Custom submit handler to delete an API key.
   */
  public function deleteKey(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $key_id = str_replace('delete_', '', $triggering_element['#name']);

    $key_entity = $this->entityTypeManager->getStorage('voting_module_api_key')->load($key_id);
    if ($key_entity) {
      $key_entity->delete();
      $this->messenger()->addStatus($this->t('Deleted API key.'));
    }

    $form_state->setRebuild();
  }

}

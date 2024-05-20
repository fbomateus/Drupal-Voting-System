<?php

namespace Drupal\voting_module\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure voting module settings for this site.
 */
class VotingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['voting_module.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'voting_module_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('voting_module.settings');

    // Enable or disable voting functionality.
    $form['enable_voting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Voting'),
      '#description' => $this->t('Enable or disable the voting functionality.'),
      '#default_value' => $config->get('enable_voting'),
    ];

    // Show or hide voting results to users.
    $form['show_results'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Results'),
      '#description' => $this->t('Show voting results to users.'),
      '#default_value' => $config->get('show_results'),
    ];

    // Allow anonymous voting.
    $form['anonymous_voting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Anonymous Voting'),
      '#description' => $this->t('Allow users who are not logged in to vote.'),
      '#default_value' => $config->get('anonymous_voting'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('voting_module.settings')
      ->set('enable_voting', $form_state->getValue('enable_voting'))
      ->set('show_results', $form_state->getValue('show_results'))
      ->set('anonymous_voting', $form_state->getValue('anonymous_voting'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

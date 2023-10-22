<?php

namespace Drupal\norris_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdminSettingsForm.
 *
 * This class defines a form for configuring global settings for the Jokes module.
 *
 * @package Drupal\norris_import\Form
 */
class AdminSettingsForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'norris_import_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'norris_import.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    // Load the configuration for the Jokes module.
    $config = $this->config('norris_import.settings');

    // Description for the form.
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Global settings for Jokes module.'),
    ];

    // Input field for specifying the API URL.
    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#default_value' => $config->get('api_url'),
    ];

    // Input field for specifying the content type for imported nodes.
    $form['node_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node Type'),
      '#default_value' => $config->get('node_type'),
    ];

    // Input field for specifying the number of jokes to be imported.
    $form['page_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of jokes to be imported'),
      '#default_value' => $config->get('page_size'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Load the configuration for the Jokes module.
    $config = $this->config('norris_import.settings');

    // Update configuration values with form input.
    $config->set('api_url', $form_state->getValue('api_url'));
    $config->set('node_type', $form_state->getValue('node_type'));
    $config->set('page_size', $form_state->getValue('page_size'));

    // Save the updated configuration.
    $config->save();

    parent::submitForm($form, $form_state);
  }
}

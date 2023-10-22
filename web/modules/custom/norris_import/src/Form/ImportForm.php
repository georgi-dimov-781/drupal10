<?php

namespace Drupal\norris_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use function GuzzleHttp\Promise\each_limit;
use Drupal\norris_import\Service\JokesApiService;

/**
 * Class ImportForm.
 *
 * This class defines a form for migrating data from an external API to Drupal.
 *
 * @package Drupal\norris_import\Form
 */
class ImportForm extends ConfigFormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'norris_import_migrate_form';
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
    $service = JokesApiService::getInstance();

    // Description for the form.
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Import data from an external API to Drupal.'),
    ];

    // Input field for specifying the number of nodes to import.
    $form['rows_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of nodes to import'),
      '#default_value' => $service->getPageSize(),
      '#disabled' => TRUE
    ];

    // Input field for specifying the API URL.
    $form[JokesApiService::PARAM_API_URL] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#default_value' => $service->getApiUrl(),
      '#disabled' => TRUE
    ];

    // Input field for specifying the content type for imported nodes.
    $form[JokesApiService::PARAM_NODE_TYPE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node Type'),
      '#default_value' => $service->getNodeType(),
      // The imported nodes are going to be configured in the AdminSett.Form, so this is only as a
      // reminder => set it to disable to prevent unwanted errors.
      '#disabled' => TRUE
    ];

    // Submit button to trigger the data migration.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $service = JokesApiService::getInstance();

    // Get the API URL and the number of rows to import from the form state.
    $url = $form_state->getValue(JokesApiService::PARAM_API_URL);
    $rows_number = $form_state->getValue('rows_number');

    // Fetch and import data from the external API.
    $importedData = $service->getImportedJokes($url, $rows_number);
    $success_number = count($importedData);

    // Iterate through the imported data and save each item as a node.
    foreach ($importedData as $data) {
      $url = $data['url'];
      $content = $data['value'];
      $created = $data['created_at'];
      $id = $data['id'];
      $iconUrl = $data['icon_url'];
      $categories = $data['categories'];

      // Create a node object with attached data.
      $service->saveJoke($content, $url, $id, $created, $iconUrl, $categories);
    }

    // Log the completion of the migration process.
    $service->logInfo("Migrate completed. $success_number / $rows_number saved successfully!");

    parent::submitForm($form, $form_state);
  }
}

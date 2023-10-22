<?php

namespace Drupal\norris_import\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Each;
use function GuzzleHttp\Promise\each_limit;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for fetching and managing Chuck Norris jokes as nodes.
 */
class JokesApiService
{
  // Constants for module settings and default values.
  const MODULE_NAME = 'norris_import';
  const API_URL = 'https://api.chucknorris.io/jokes/random';
  const NODE_TYPE = 'jokes';
  const FIELD_ID = 'field_id';
  const PARAM_API_URL = 'api_url';
  const PARAM_NODE_TYPE = 'node_type';
  const PARAM_PAGE_SIZE = 'page_size';

  private ConfigFactoryInterface $configFactory;
  private EntityTypeManagerInterface $entityTypeManager;
  private LoggerChannelFactoryInterface $loggerFactory;
  private Client $httpClient;

  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
    Client $httpClient
  ) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
    $this->httpClient = $httpClient;
  }

  /**
   * Singleton instance.
   * This method ensures that only one instance of the JokesApiService class is created.
   */
  private static ?JokesApiService $instance = null;

  public static function getInstance(): ?JokesApiService
  {
    if (self::$instance == null) {
      // Initialize the singleton instance with dependencies.
      self::$instance = new JokesApiService(
        \Drupal::configFactory(),
        \Drupal::entityTypeManager(),
        \Drupal::getContainer()->get('logger.factory'),
        new Client(['timeout' => 12])
      );
    }

    return self::$instance;
  }

  // Installation and uninstallation logic.

  /**
   * Perform initial setup and configuration during installation.
   * This method sets default configuration values.
   */
  public function install()
  {
    // Set default module configurations during installation.
    $defaultConfig = $this->configFactory->getEditable($this->getModuleSettingsId());
    $defaultConfig->set(JokesApiService::PARAM_API_URL, JokesApiService::API_URL)
      ->set(JokesApiService::PARAM_NODE_TYPE, JokesApiService::NODE_TYPE)
      ->set(JokesApiService::PARAM_PAGE_SIZE, 5)
      ->save();
  }

  /**
   * Cleanup and remove module-related data during uninstallation.
   * This method deletes nodes and module-specific configurations.
   */
  public function uninstall()
  {
    // Delete nodes of the specified content type during uninstallation.
    $this->deleteNodesByType($this->getNodeType());

    // Delete module-specific configurations during uninstallation.
    $this->deleteModuleConfigs();
  }

  // Helper methods for uninstallation.

  /**
   * Delete nodes of a specific content type.
   */
  private function deleteNodesByType($nodeType)
  {
    // Delete nodes of a specific content type.
    $query = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $nodeType);
    $nids = $query->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $this->entityTypeManager->getStorage('node')->delete($nodes);
  }

  /**
   * Delete module-specific configuration files.
   */
  private function deleteModuleConfigs()
  {
    // Delete module-specific configuration files during uninstallation.
    $modulePath = \Drupal::service('extension.list.module')->getPath(self::MODULE_NAME) . '/config/install';
    $configs = [];
    $files = \Drupal::service('file_system')->scanDirectory($modulePath, '/\.yml$/');
    if ($files) {
      foreach ($files as $file) {
        $configs[] = $file->name;
      }

      foreach ($configs as $configName) {
        \Drupal::configFactory()->getEditable($configName)->delete();
      }
    }
  }

  // Logging methods.

  /**
   * Log an informational message using the module's logger.
   */
  public function logInfo($message)
  {
    // Log informational messages related to the module.
    $this->loggerFactory->get(self::MODULE_NAME)->notice($message);
  }

  /**
   * Log an error message using the module's logger.
   */
  public function logError($message)
  {
    // Log error messages related to the module.
    $this->loggerFactory->get(self::MODULE_NAME)->error($message);
  }

  // Import methods.

  /**
   * Fetch and import Chuck Norris jokes from an external API.
   */
  public function getImportedJokes($url, $rowsNumber): array
  {
    $promises = function () use ($url, $rowsNumber) {
      for ($index = 1; $index <= $rowsNumber; $index++) {
        // Asynchronously fetch data from the provided URL.
        yield $this->httpClient->getAsync($url)
          ->then(
            function ($response) use ($index) {
              return $response->getBody();
            },
            function ($exception) use ($index) {
              // Log errors if the import fails.
              $this->logError("Import #$index failed: " . $exception->getMessage());
            }
          );
      }
    };

    $data = [];
    $promise = Each::ofLimit(
      $promises(),
      $rowsNumber,
      function ($response) use (&$data) {
        $data[] = json_decode($response, true);
      }
    );

    $promise->wait();
    return $data;
  }

  // Data retrieval methods.

  /**
   * Create a new node to store a Chuck Norris joke.
   */
  public function saveJoke($content, $url, $id, $created, $iconUrl, $categories)
  {
    try {
      // Validate the input data.
      if (empty($content) || empty($url) || empty($id) || empty($created)) {
        throw new \InvalidArgumentException('Invalid data provided for creating a node.');
      }

      // Create a new node with the provided data.
      $node = \Drupal\node\Entity\Node::create([
        'type' => $this->getNodeType(),
        'title' => $content,
        'field_content' => $content,
        'field_url' => $url,
        'field_id' => $id,
        'field_created' => $created,
        'field_icon_url' => $iconUrl,
        'field_categories' => $categories
      ]);
      $node->save();
    } catch (\Exception $e) {
      // Log errors if node creation fails.
      $this->logError("Failed to import joke #$id: " . $e->getMessage());
    }
  }

  /**
   * Retrieve Chuck Norris jokes as nodes.
   */
  public function getJokes($pageSize)
  {
    $rows = [];
    $query = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck(false)
      ->condition('type', $this->getNodeType())
      ->sort('created', 'DESC')
      ->pager($pageSize);

    $nids = $query->execute();
    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      $rows[] = [
        'title' => $node->getTitle(),
        'url' => $node->get('field_url')->getString(),
        'id' => $node->get('field_id')->getString(),
        'content' => $node->get('field_content')->getString(),
        'created' => substr($node->get('field_created')->getString(), 0, 10),
      ];
    }
    return $rows;
  }

  // Settings methods.

  /**
   * Get the configuration ID for the module settings.
   */
  public function getModuleSettingsId()
  {
    return self::MODULE_NAME . '.settings';
  }

  /**
   * Get the module's configuration settings.
   */
  public function getSettings()
  {
    return $this->configFactory->get($this->getModuleSettingsId());
  }

  /**
   * Get the API URL from module settings.
   */
  public function getApiUrl()
  {
    return $this->getSettings()->get(JokesApiService::PARAM_API_URL);
  }

  /**
   * Get the page size from module settings.
   */
  public function getPageSize()
  {
    return $this->getSettings()->get(JokesApiService::PARAM_PAGE_SIZE);
  }

  /**
   * Get the content type for storing Chuck Norris jokes from module settings.
   */
  public function getNodeType()
  {
    return $this->getSettings()->get(JokesApiService::PARAM_NODE_TYPE);
  }
}

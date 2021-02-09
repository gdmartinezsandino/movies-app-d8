<?php

namespace Drupal\dc_content_import\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\State;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

/**
 * Class ApiImportTools.
 */
class ApiImportTools {

  protected $themoviedbApiUrl;

  protected $themoviedbApiKey;

  /**
   * Drupal\Core\Entity\EntityManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AccountProxy, Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Include the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Config\ConfigFactory $configFactory
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Database\Connection $database
   */
  protected $database;

  /**
   * Drupal\Core\State\State definition.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * ApiImportTools constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \GuzzleHttp\ClientInterface $http_client
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\State\State $state
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    ClientInterface $http_client,
    LoggerChannelFactory $loggerFactory,
    MessengerInterface $messenger,
    ConfigFactory $config_factory,
    Connection $database,
    State $state
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->themoviedbApiUrl = $this->configFactory->get('dc_content_import.connection_config')
      ->get('themovied_api_url');
    $this->themoviedbApiKey = $this->configFactory->get('dc_content_import.connection_config')
      ->get('themovied_api_key');
    $this->loggerFactory = $loggerFactory->get('dc_content_import');
    $this->messenger = $messenger;
    $this->database = $database;
    $this->state = $state;
  }


  /**
   * Create and execute a query on themovied Api to get a entity list.
   * Entities could be 'movie' or 'people' and types could be
   * upcoming' or 'popular'.
   *
   * @param $entity
   * @param int $page
   * @param string $type
   *
   * @return bool|string
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getAPIEntities($entity, $type = 'popular', $page = 1) {
    if (!in_array($type, ['upcoming', 'popular'])) {
      return FALSE;
    }

    $api_endpoint_url = $this->themoviedbApiUrl . $entity . '/' . $type;
    $api_key = $this->themoviedbApiKey;

    if (empty($api_endpoint_url) || empty($api_key)) {
      $this->messenger->addMessage(t('Endpoint or key missed'), 'error');
      return FALSE;
    }

    try {
      $get_parameters = [
        'api_key' => $api_key,
        'page' => $page,
        'language' => 'en-US',
      ];
      // Set the options array to be passed in the request.
      $options = [
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/hal+json',
        ],
        RequestOptions::QUERY => $get_parameters,
      ];

      // Perform the request. You can also use $this->httpClient->get() instead.
      $response = $this->httpClient->request('GET', $api_endpoint_url, $options);
      if ($response->getStatusCode() != 200) {
        $message = t('API error. Response: @response', ['@response' => $response->getStatusCode()]);
        $this->messenger->addMessage($message, 'error');
        $this->loggerFactory->error($message);
        return FALSE;
      }

      return $response->getBody()->getContents();
    } catch (RequestException $exception) {
      $message = $exception->getMessage();
      $this->messenger->addMessage($message, 'error');
      $this->loggerFactory->error($message);
      return FALSE;
    }
    return FALSE;
  }


  /**
   * Executes a query on themovied Api to get a entity type detailed data.
   * Entities could be 'movie' or 'people' and types could be
   * upcoming' or 'popular'.
   *
   * @param $entity
   * @param $id
   *
   * @return bool|string
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getEntityFullData($entity, $id) {
    $api_endpoint_url = $this->themoviedbApiUrl . $entity . '/' . $id;
    $api_key = $this->themoviedbApiKey;

    if (empty($api_endpoint_url) || empty($api_key)) {
      $this->messenger->addMessage(t('Endpoint or key missed'), 'error');
      return FALSE;
    }

    try {
      $get_parameters = [
        'api_key' => $api_key,
      ];
      // Set the options array to be passed in the request.
      $options = [
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/hal+json',
        ],
        RequestOptions::QUERY => $get_parameters,
      ];

      // Perform the request. You can also use $this->httpClient->get() instead.
      $response = $this->httpClient->request('GET', $api_endpoint_url, $options);
      if ($response->getStatusCode() != 200) {
        $message = t('API error. Response: @response', ['@response' => $response->getStatusCode()]);
        $this->messenger->addMessage($message, 'error');
        $this->loggerFactory->error($message);
        return FALSE;
      }

      return $response->getBody()->getContents();
    } catch (RequestException $exception) {
      $message = $exception->getMessage();
      $this->messenger->addMessage($message, 'error');
      $this->loggerFactory->error($message);
      return FALSE;
    }
    return FALSE;
  }


  /**
   * Executes a query on themovied Api to get Genre Movie List.
   *
   * @return bool|string
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getGenreMovieList() {
    $api_endpoint_url = $this->themoviedbApiUrl . 'genre/movie/list';
    $api_key = $this->themoviedbApiKey;

    if (empty($api_endpoint_url) || empty($api_key)) {
      $this->messenger->addMessage(t('Endpoint or key missed'), 'error');
      return FALSE;
    }

    try {
      $get_parameters = [
        'api_key' => $api_key,
      ];
      // Set the options array to be passed in the request.
      $options = [
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/hal+json',
        ],
        RequestOptions::QUERY => $get_parameters,
      ];

      // Perform the request. You can also use $this->httpClient->get() instead.
      $response = $this->httpClient->request('GET', $api_endpoint_url, $options);
      if ($response->getStatusCode() != 200) {
        $message = t('API error. Response: @response', ['@response' => $response->getStatusCode()]);
        $this->messenger->addMessage($message, 'error');
        $this->loggerFactory->error($message);
        return FALSE;
      }

      return $response->getBody()->getContents();
    } catch (RequestException $exception) {
      $message = $exception->getMessage();
      $this->messenger->addMessage($message, 'error');
      $this->loggerFactory->error($message);
      return FALSE;
    }
    return FALSE;
  }


  /**
   * Executes a query on themovied Api to get Movie specific data.
   *
   * @param $id
   * @param $data_type
   *
   * @return bool|string
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getEntitySpecificData($id, $data_type, $entity_type = 'movie') {
    $api_endpoint_url = $this->themoviedbApiUrl . $entity_type . '/' . $id . '/' . $data_type;
    $api_key = $this->themoviedbApiKey;

    if (empty($api_endpoint_url) || empty($api_key)) {
      $this->messenger->addMessage(t('Endpoint or key missed'), 'error');
      return FALSE;
    }

    try {
      $get_parameters = [
        'api_key' => $api_key,
      ];

      // Set the options array to be passed in the request.
      $options = [
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/hal+json',
        ],
        RequestOptions::QUERY => $get_parameters,
      ];

      // Perform the request. You can also use $this->httpClient->get() instead.
      $response = $this->httpClient->request('GET', $api_endpoint_url, $options);
      if ($response->getStatusCode() != 200) {
        $message = t('API error. Response: @response', ['@response' => $response->getStatusCode()]);
        $this->messenger->addMessage($message, 'error');
        $this->loggerFactory->error($message);
        return FALSE;
      }

      return $response->getBody()->getContents();
    } catch (RequestException $exception) {
      $message = $exception->getMessage();
      $this->messenger->addMessage($message, 'error');
      $this->loggerFactory->error($message);
      return FALSE;
    }

    return FALSE;
  }


  /**
   * Creates a file entity by external url.
   *
   * @param string $filepath
   *   File url.
   * @param string $destination
   *   File destination.
   *
   * @return int|null
   *   File entity id.
   */
  public function createFileByPath($filepath, $destination) {
    if (empty($filepath)) {
      return NULL;
    }

    // Create the file.
    $handle = fopen($filepath, 'r');
    $file_default_scheme = file_default_scheme();

    if ($handle) {
      $file = file_save_data($handle, "$file_default_scheme://$destination", FILE_EXISTS_REPLACE);
      fclose($handle);

      if ($file) {
        $fid = $file->id();
        return $fid;
      }
    }

    return NULL;
  }


  /**
   * Return themoviedbApiKey;
   *
   * @return array|mixed|null
   */
  public function getApiKey() {
    return $this->themoviedbApiKey;
  }
}

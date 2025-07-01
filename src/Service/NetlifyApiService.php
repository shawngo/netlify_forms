<?php

/**
 * @file
 * Contains \Drupal\netlify_forms\Service\NetlifyApiService.
 *
 * LOCATION: modules/custom/netlify_forms/src/Service/NetlifyApiService.php
 */

namespace Drupal\netlify_forms\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for interacting with Netlify API.
 */
class NetlifyApiService {

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a NetlifyApiService object.
   */
  public function __construct(
    ClientFactory $http_client_factory,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClientFactory = $http_client_factory;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Get forms for a specific site.
   *
   * @param string $site_id
   *   The Netlify site ID.
   *
   * @return array
   *   Array of forms or empty array on failure.
   */
  public function getForms($site_id) {
    $config = $this->configFactory->get('netlify_forms.settings');
    $api_token = $config->get('api_token');

    if (!$api_token || !$site_id) {
      return [];
    }

    try {
      $client = $this->httpClientFactory->fromOptions([
        'base_uri' => 'https://api.netlify.com/api/v1/',
        'headers' => [
          'Authorization' => 'Bearer ' . $api_token,
          'Content-Type' => 'application/json',
        ],
      ]);

      $response = $client->get("sites/{$site_id}/forms");
      $data = json_decode($response->getBody()->getContents(), TRUE);

      return $data ?: [];
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('netlify_forms')->error('Failed to fetch forms: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Get submissions for a specific form.
   *
   * @param string $site_id
   *   The Netlify site ID.
   * @param string $form_id
   *   The form ID.
   *
   * @return array
   *   Array of submissions or empty array on failure.
   */
  public function getSubmissions($site_id, $form_id) {
    $config = $this->configFactory->get('netlify_forms.settings');
    $api_token = $config->get('api_token');

    if (!$api_token || !$site_id || !$form_id) {
      return [];
    }

    try {
      $client = $this->httpClientFactory->fromOptions([
        'base_uri' => 'https://api.netlify.com/api/v1/',
        'headers' => [
          'Authorization' => 'Bearer ' . $api_token,
          'Content-Type' => 'application/json',
        ],
      ]);

      $response = $client->get("sites/{$site_id}/forms/{$form_id}/submissions");
      $data = json_decode($response->getBody()->getContents(), TRUE);

      return $data ?: [];
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('netlify_forms')->error('Failed to fetch submissions: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Get a specific submission.
   *
   * @param string $site_id
   *   The Netlify site ID.
   * @param string $submission_id
   *   The submission ID.
   *
   * @return array|null
   *   Submission data or null on failure.
   */
  public function getSubmission($site_id, $submission_id) {
    $config = $this->configFactory->get('netlify_forms.settings');
    $api_token = $config->get('api_token');

    if (!$api_token || !$site_id || !$submission_id) {
      return NULL;
    }

    try {
      $client = $this->httpClientFactory->fromOptions([
        'base_uri' => 'https://api.netlify.com/api/v1/',
        'headers' => [
          'Authorization' => 'Bearer ' . $api_token,
          'Content-Type' => 'application/json',
        ],
      ]);

      $response = $client->get("submissions/{$submission_id}");
      $data = json_decode($response->getBody()->getContents(), TRUE);

      return $data ?: NULL;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('netlify_forms')->error('Failed to fetch submission: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}

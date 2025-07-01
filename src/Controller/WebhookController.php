<?php

/**
 * @file
 * Handles incoming Netlify form submission webhooks and data storage.
 */

namespace Drupal\netlify_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for handling Netlify webhooks.
 */
class WebhookController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a WebhookController object.
   */
  public function __construct(Connection $database, LoggerChannelFactoryInterface $logger_factory) {
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('logger.factory')
    );
  }

  /**
   * Handle incoming Netlify form submission webhook.
   */
  public function handleSubmission($site_id, Request $request) {
    $logger = $this->loggerFactory->get('netlify_forms');

    try {
      // Get the raw JSON payload
      $payload = $request->getContent();
      $data = json_decode($payload, TRUE);

      if (!$data) {
        $logger->error('Invalid JSON payload received for site @site_id', ['@site_id' => $site_id]);
        return new JsonResponse(['error' => 'Invalid JSON'], 400);
      }

      // Log the incoming webhook
      $logger->info('Webhook received for site @site_id with submission @submission_id', [
        '@site_id' => $site_id,
        '@submission_id' => $data['id'] ?? 'unknown',
      ]);

      // Find the customer for this site
      $customer = $this->getCustomerBySiteId($site_id);
      if (!$customer) {
        $logger->warning('No customer found for site @site_id', ['@site_id' => $site_id]);
        return new JsonResponse(['error' => 'Site not found'], 404);
      }

      // Validate required fields
      if (!isset($data['id']) || !isset($data['form_id'])) {
        $logger->error('Missing required fields in webhook payload for site @site_id', ['@site_id' => $site_id]);
        return new JsonResponse(['error' => 'Missing required fields'], 400);
      }

      // Check if submission already exists (duplicate webhook)
      $existing = $this->database->select('netlify_submissions', 'ns')
        ->fields('ns', ['id'])
        ->condition('netlify_submission_id', $data['id'])
        ->execute()
        ->fetchField();

      if ($existing) {
        $logger->info('Duplicate submission @submission_id ignored', ['@submission_id' => $data['id']]);
        return new JsonResponse(['status' => 'duplicate', 'message' => 'Submission already exists']);
      }

      // Store the submission
      $submission_id = $this->storeSubmission($customer, $site_id, $data);

      $logger->info('Successfully stored submission @submission_id as local ID @local_id', [
        '@submission_id' => $data['id'],
        '@local_id' => $submission_id,
      ]);

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Submission stored successfully',
        'local_id' => $submission_id,
      ]);

    } catch (\Exception $e) {
      $logger->error('Error processing webhook for site @site_id: @error', [
        '@site_id' => $site_id,
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Get customer by site ID.
   */
  protected function getCustomerBySiteId($site_id) {
    $storage = \Drupal::entityTypeManager()->getStorage('netlify_customer');
    $customers = $storage->loadByProperties(['site_id' => $site_id]);
    return !empty($customers) ? reset($customers) : NULL;
  }

  /**
   * Store submission in database.
   */
  protected function storeSubmission($customer, $site_id, $data) {
    $created_at = isset($data['created_at']) ? strtotime($data['created_at']) : time();

    return $this->database->insert('netlify_submissions')
      ->fields([
        'customer_id' => $customer->id(),
        'site_id' => $site_id,
        'form_id' => $data['form_id'] ?? '',
        'form_name' => $data['form_name'] ?? '',
        'netlify_submission_id' => $data['id'],
        'submission_data' => json_encode($data),
        'email' => $data['email'] ?? '',
        'submission_name' => $data['name'] ?? ($data['summary'] ?? ''),
        'created_at' => $created_at,
        'received_at' => time(),
      ])
      ->execute();
  }

}

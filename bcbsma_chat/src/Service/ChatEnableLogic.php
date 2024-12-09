<?php

namespace Drupal\bcbsma_chat\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\key\KeyRepository;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A Class for ChatEnableLogic.
 *
 * @package Drupal\bcbsma_chat\Service
 */
class ChatEnableLogic implements ContainerInjectionInterface {

  const CACHE_BIN = "bcbsma_chat.token";
  const CACHE_PERMANENT = 86400;
  /**
   * Guzzle\Client instance.
   *
   * @var \GuzzleHttp\Client
   */
  // phpcs:ignore
   protected $httpClient;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
    // phpcs:ignore
    protected $request;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
    // phpcs:ignore
    protected $logger;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
    // phpcs:ignore
    protected $configFactory;

  /**
   * KeyRepository.
   *
   * @var \Drupal\key\KeyRepository
   */
  // phpcs:ignore
  protected $keyRepository;

  /**
   * Cache Backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  // phpcs:ignore
  protected $cacheBackend;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  // phpcs:ignore
  protected $time;

  /**
   * Constructor for ChatEnableLogic.
   *
   * @param \GuzzleHttp\Client $httpClient
   *   Http Client.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Current request.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger Factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config instance.
   * @param \Drupal\key\KeyRepository $keyRepository
   *   KeyRepository.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache Backend.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
      Client $httpClient,
      RequestStack $request,
      LoggerChannelFactoryInterface $logger,
      ConfigFactoryInterface $configFactory,
      KeyRepository $keyRepository,
      CacheBackendInterface $cacheBackend,
      TimeInterface $time
      ) {
    $this->httpClient = $httpClient;
    $this->request = $request;
    $this->logger = $logger;
    $this->configFactory = $configFactory;
    $this->keyRepository = $keyRepository;
    $this->cacheBackend = $cacheBackend;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
     $container->get('http_client'),
     $container->get('request_stack'),
     $container->get('logger.factory'),
     $container->get('config.factory'),
     $container->get('key.repository'),
     $container->get('cache.bcbsma_chat'),
     $container->get('datetime.time')
    );
  }

  /**
   * Public Chat Serivce.
   *
   * @return bool
   *   Return Response Data.
   */
  public function chatLogic(): bool {
    $chat_block_visibility = FALSE;
    $config = $this->configFactory->get('bcbsma_chat.settings');
    date_default_timezone_set('America/New_York');
    $current_day = date('w');
    // 0 means sunday, 6 means saturday
    if ($current_day == $config->get('bcbsma_chat_sunday') || $current_day == $config->get('bcbsma_chat_saturday')) {
      return $chat_block_visibility;
    }
    // Current time.
    $current_time = date('H:i');
    // Start time.
    $start_time = $config->get('bcbsma_chat_start_time');
    // End time.
    $end_time = $config->get('bcbsma_chat_end_time');
    // Current time stamp.
    $current_timestamp = strtotime($current_time);
    // Start time stamp.
    $start_timestamp = strtotime($start_time);
    // End time stamp.
    $end_timestamp = strtotime($end_time);
    if ($current_timestamp < $start_timestamp || $current_timestamp > $end_timestamp) {
      return $chat_block_visibility;
    }
    elseif ($current_timestamp < $start_timestamp && $current_timestamp > $end_timestamp) {
      return $chat_block_visibility;
    }

    $chatService = $config->get('chat_type');
    if (empty($chatService)) {
      return $chat_block_visibility;
    }

    if ($chatService == "cisco") {
      $chat_block_visibility = $this->ciscoAgentCheck();
    }
    elseif ($chatService == "amelia") {
      $chat_block_visibility = $this->ameliaAgentCheck();
    }

    return $chat_block_visibility;
  }

  /**
   * Public ciscoAgentCheck.
   *
   * @return bool
   *   Return Response Data.
   */
  public function ciscoAgentCheck(): bool {

    $chat_block_visibility = FALSE;

    $config = $this->configFactory->get('bcbsma_chat.settings');
    $chat_api_url = trim($config->get('bcbsma_chat_api_url') ?? "");

    if (empty($chat_api_url)) {
      return $chat_block_visibility;
    }

    try {
      // Load Chat API and check agent availability.
      $method = 'GET';
      $options = [];
      $response = $this->httpClient->request($method, $chat_api_url, $options);
      $code = $response->getStatusCode();
      if ($code != 200) {
        return $chat_block_visibility;
      }
      $body = $response->getBody()->getContents();
      $xmlObject = simplexml_load_string($body);
      $xmlArray = (array) $xmlObject;
      if (!empty($xmlArray)) {
        return $chat_block_visibility;
      }
      $data = (array) $xmlObject->children('ns2', TRUE);
      if ($data['count'] == 0) {
        return $chat_block_visibility;
      }
      $chat_block_visibility = TRUE;
    }
    catch (\Exception $e) {
      $this->logger->get('BCBSMA_CHAT')->error($e->getMessage());
    }
    return $chat_block_visibility;
  }

  /**
   * Public ameliaAgentCheck.
   *
   * @return bool
   *   Return Response Data.
   */
  public function ameliaAgentCheck(): bool {
    $chat_block_visibility = FALSE;
    $agent_queue = [];
    // Retrive Configurtion from Key Entity.
    $key = $this->keyRepository->getKey('amelia_live_chat');
    $request = [];
    if (!is_null($key)) {
      $config = preg_replace('/^\s+|\s+$/', '', $key->getKeyValue());
      if ($config) {
        $request = json_decode($config, TRUE);
      }
    }
    else {
      return $chat_block_visibility;
    }

    // Setting Method get for the request API.
    $method = 'GET';
    // If $access_token is empty or null.
    // Get token By calling API.
    $access_token = $this->getAccessToken($request);

    // If Token is empty return with 400.
    if ($access_token == " ") {
      return $chat_block_visibility;
    }
    try {
      $options = [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
          'X-ClientName' => 'Drupal',
          'X-ClientVersion' => 'v1',
          'X-ClientSessionId' => $this->time->getCurrentTime(),
        ],
      ];
      // Client call to the external API.
      $response = $this->httpClient->request($method, $request['queue']['endPoint'], $options);
      $body_decoded = [];
      if ($response->getStatusCode() == 200) {
        $data = $response->getBody()->getContents();
        $body_decoded = json_decode($data, TRUE);
        foreach ($body_decoded as $data) {
          if ($data['queueCode'] == $this->configFactory->get('bcbsma_chat.settings')->get('amelia_queueCode')) {
            $agent_queue[] = $data;
          }
        }
        if (empty($agent_queue)) {
          return $chat_block_visibility;
        }
        if ($agent_queue[0]['readyAgents'] > 0) {
          $chat_block_visibility = TRUE;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->get('amelia_chat')->error($e->getMessage());
    }

    return $chat_block_visibility;
  }

  /**
   * Provides AccessToken.
   *
   * @param mixed[] $request
   *   Array.
   *
   * @return string
   *   Return Access token.
   */
  public function getAccessToken(array $request): string {
    try {
      if (empty($request)) {
        $this->logger->get('amelia_chat_token')->error("Missing Token Request details.");
        return '';
      }

      $tokenResponse = $this->httpClient->request('POST', $request['token']['endPoint'], [
        'headers' => $request['token']['headers'],
        'form_params' => [
          'grant_type' => $request['token']['grant'],
        ],
      ]);
      if ($tokenResponse->getStatusCode() == 200) {
        $tokenInfo = json_decode($tokenResponse->getBody()->getContents());
        $accessToken = $tokenInfo->access_token;
        return $accessToken;
      }
      else {
        return '';
      }
    }
    catch (\Exception $e) {
      $this->logger->get('amelia_chat_token')->error($e->getMessage());
    }
    return '';
  }

}

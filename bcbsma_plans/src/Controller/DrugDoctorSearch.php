<?php

namespace Drupal\bcbsma_plans\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\key\KeyRepository;
use Drupal\session_based_temp_store\SessionBasedTempStoreFactory;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A Class for GetRedirectUrl.
 *
 * @package Drupal\bcbsma_plans\Controller
 */
class DrugDoctorSearch extends ControllerBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  // phpcs:ignore
  protected $requestStack;

  /**
   * A entity query.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  // phpcs:ignore
  protected $entityTypeManager;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  // phpcs:ignore
  protected $logger;

  /**
   * KeyRepository.
   *
   * @var \Drupal\key\KeyRepository
   */
  // phpcs:ignore
  protected $keyRepository;

  /**
   * The Guzzle\Client instance.
   *
   * @var \GuzzleHttp\Client
   */
  // phpcs:ignore
  protected $httpClient;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  // phpcs:ignore
  protected $configFactory;

  /**
   * The private tempstore factory.
   *
   * @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory
   */
  // phpcs:ignore
  protected $sessionBasedTempStore;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  // phpcs:ignore
  protected $time;

  /**
   * Constructs a Drupal\bcbsma_plans\Controller object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request Stack.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger Factory.
   * @param \Drupal\key\KeyRepository $keyRepository
   *   KeyRepository.
   * @param \GuzzleHttp\Client $httpClient
   *   The httpClient instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $sessionBasedTempStore
   *   The private tempstore factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    RequestStack $requestStack,
    EntityTypeManager $entityTypeManager,
    LoggerChannelFactoryInterface $logger,
    KeyRepository $keyRepository,
    Client $httpClient,
    ConfigFactoryInterface $configFactory,
    SessionBasedTempStoreFactory $sessionBasedTempStore,
    TimeInterface $time
  ) {
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->keyRepository = $keyRepository;
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->sessionBasedTempStore = $sessionBasedTempStore->get('bcbsma_plans', 850);
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('key.repository'),
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('session_based_temp_store'),
      $container->get('datetime.time')
    );
  }

  /**
   * Drug Search.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JsonResponse
   */
  public function drugSearch(): JsonResponse {
    $body_decoded = [];
    try {
      // Get Query params from currrent request.
      $params = $this->requestStack->getCurrentRequest()->query->all();

      // Checking if request has keyword query param.
      if (empty($params) || !isset($params['keyword'])) {
        // If empty return 400.
        return new JsonResponse(['message' => 'bad request', 'error' => 400], 400);
      }
      $pager = "";
      if (isset($params['page']) && $params['page'] != "") {
        // If empty return 400.
        $pager = "&page=" . $params['page'];
      }
      // Retrive Configurtion from Key Entity.
      $key = $this->keyRepository->getKey('drug_search');
      $request = [];
      if (!is_null($key)) {
        $config = trim($key->getKeyValue());
        if ($config) {
          $request = json_decode($config, TRUE);
        }
      }
      // Setting Method get for the request API.
      $method = 'GET';
      // Getting token from Session.
      $access_token = $this->sessionBasedTempStore->get('drug_access_token');
      // If $access_token is empty or null.
      // Get token By calling API.
      if (is_null($access_token)) {
        $access_token = $this->getAccessToken($request);
      }
      // If Token is empty return with 400.
      if ($access_token == "") {
        return new JsonResponse(
          [
            'message' => 'Missing Auth Token',
            'status' => 400,
          ], 400);
      }

      // Setting headers for the Request.
      $request['headers']['Authorization'] = $access_token;
      $request['headers']['X-ClientSessionId'] = $this->time->getCurrentTime();
      $options = ['headers' => $request['headers']];

      // Client call to the external API.
      $response = $this->httpClient->request($method, $request['endPoint']['drugSearch'] . $params['keyword'] . $pager, $options);
      if ($response != NULL) {
        $data = $response->getBody()->getContents();
        $body_decoded = json_decode($data, TRUE);
      }
    }
    catch (\Exception $e) {
      $this->logger->get('drug_search')->error($e->getMessage());
      if (!is_null($e) && method_exists($e, 'getResponse')) {
        return new JsonResponse([
          'message' => $e->getResponse()->getReasonPhrase(),
          'statusCode' => $e->getResponse()->getStatusCode(),
        ], $e->getResponse()->getStatusCode());
      }
    }

    return new JsonResponse($body_decoded, 200);

  }

  /**
   * Drug info.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JsonResponse
   */
  public function drugInfo(): JsonResponse {
    $body_decoded = [];
    try {
      // Get Query params from currrent request.
      $params = $this->requestStack->getCurrentRequest()->query->all();
      // Checking if request has keyword query param.
      if (empty($params) && !isset($params['gpi']) && !isset($params['label-name'])) {
        // If empty return 400.
        return new JsonResponse(['message' => 'bad request', 'status' => 400], 400);
      }

      // Returing when Query parameters are empty in request.
      if ($params['gpi'] == "" &&  $params['label-name'] == "") {
        $response = ['message' => 'bad-request', 'error' => 400];
        return (new JsonResponse($response, 400));
      }

      // Retrive Configurtion from Key Entity.
      $key = $this->keyRepository->getKey('drug_search');
      $request = [];
      if (!is_null($key)) {
        $config = trim($key->getKeyValue());
        if ($config) {
          $request = json_decode($config, TRUE);
        }
      }
      // Setting Method get for the request API.
      $method = 'GET';
      // Getting token from Session.
      $access_token = $this->sessionBasedTempStore->get('drug_access_token');
      // If $access_token is empty or null.
      // Get token By calling API.
      if (is_null($access_token)) {
        $access_token = $this->getAccessToken($request);
      }
      // If Token is empty return with 400.
      if ($access_token == "") {
        return new JsonResponse(
          [
            'message' => 'Missing Auth Token',
            'status' => 400,
          ], 400);
      }

      // Setting headers for the Request.
      $request['headers']['Authorization'] = $access_token;
      $request['headers']['X-ClientSessionId'] = $this->time->getCurrentTime();
      $options = ['headers' => $request['headers']];

      // Client call to the external API.
      $response = $this->httpClient->request($method, $request['endPoint']['drugInfo'] . $params['gpi'] . '&label-name=' . $params['label-name'], $options);
      if ($response != NULL) {
        $data = $response->getBody()->getContents();
        $body_decoded = json_decode($data, TRUE);
      }
    }
    catch (\Exception $e) {
      $this->logger->get('drug_info')->error($e->getMessage());
      if (!is_null($e) && method_exists($e, 'getResponse')) {
        return new JsonResponse([
          'message' => $e->getResponse()->getReasonPhrase(),
          'statusCode' => $e->getResponse()->getStatusCode(),
        ], $e->getResponse()->getStatusCode());
      }
    }
    return new JsonResponse($body_decoded, 200);
  }

  /**
   * Doctor Search.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JsonResponse
   */
  public function doctorSearch(): JsonResponse {
    $body_decoded = [];
    try {
      // Get Query params from currrent request.
      $params = $this->requestStack->getCurrentRequest()->query->all();

      // Checking if request has keyword query param.
      if (empty($params) || !isset($params['keyword']) || !isset($params['zip-code'])) {
        // If empty return 400.
        return new JsonResponse(['message' => 'bad request', 'error' => 400], 400);
      }
      $pager = "";
      if (isset($params['page']) && $params['page'] != "") {
        // If empty return 400.
        $pager = "&page=" . $params['page'];
      }

      // Retrive Configurtion from Key Entity.
      $key = $this->keyRepository->getKey('drug_search');

      $request = [];
      if (!is_null($key)) {
        $config = trim($key->getKeyValue());
        if ($config) {
          $request = json_decode($config, TRUE);
        }
      }
      // Setting Method get for the request API.
      $method = 'GET';
      // Getting token from Session.
      $access_token = $this->sessionBasedTempStore->get('drug_access_token');
      // If $access_token is empty or null.
      // Get token By calling API.
      if (is_null($access_token)) {
        $access_token = $this->getAccessToken($request);
      }
      // If Token is empty return with 400.
      if ($access_token == "") {
        return new JsonResponse(
          [
            'message' => 'Missing Auth Token',
            'status' => 400,
          ],
          400
        );
      }
      // Setting headers for the Request.
      $request['headers']['Authorization'] = $access_token;
      $request['headers']['X-ClientSessionId'] = $this->time->getCurrentTime();
      $request['headers']['X-SubmitDateTime'] = date("Y/m/d h:i:sa");
      $options = ['headers' => $request['headers']];

      // Client call to the external API.
      $response = $this->httpClient->request($method, $request['endPoint']['doctorSearch'] . "?keyword=" . $params['keyword'] . '&zip-code=' . $params['zip-code'] . $pager, $options);
      if ($response != NULL) {
        $data = $response->getBody()->getContents();
        $body_decoded = json_decode($data, TRUE);
      }
    }
    catch (\Exception $e) {
      $this->logger->get('drug_search')->error($e->getMessage());
      if (!is_null($e) && method_exists($e, 'getResponse')) {
        return new JsonResponse([
          'message' => $e->getResponse()->getReasonPhrase(),
          'statusCode' => $e->getResponse()->getStatusCode(),
        ], $e->getResponse()->getStatusCode());
      }
    }

    return new JsonResponse($body_decoded, 200);
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
        $this->logger->get('drug_search_token')->error("Missing Token Request details.");
        return '';
      }
      $tokenResponse = $this->httpClient->request('POST', $request['tokenEndpoint'], [
        'headers' => $request['tokenHeaders'],
        'form_params' => [
          'grant_type' => $request['grant'],
        ],
      ]);
      if ($tokenResponse->getStatusCode() == 200) {
        $tokenInfo = json_decode($tokenResponse->getBody()->getContents());
        $accessToken = $tokenInfo->access_token;
        /** @var  \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $this->sessionBasedTempStore */
        if (isset($this->sessionBasedTempStore)) {
          $this->sessionBasedTempStore->set('drug_access_token', 'Bearer ' . $accessToken);
        }
        return 'Bearer ' . $accessToken;
      }
      else {
        return '';
      }
    }
    catch (\Exception $e) {
      $this->logger->get('drug_search_token')->error($e->getMessage());
    }
    return '';
  }

}

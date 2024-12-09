<?php

namespace Drupal\bcbsma_mailsystem\Plugin\Mail;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\key\KeyRepository;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Graph Mail' plugin to send emails.
 *
 * @Mail(
 *   id = "graphmailer",
 *   label = @Translation("Graph Mailer"),
 *   description = @Translation("Global Graph Mail Plugin.")
 * )
 */
class GraphMailer implements MailInterface, ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  // phpcs:ignore
  protected $configFactory;

  /**
   * The Guzzle\Client instance.
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
   * KeyRepository.
   *
   * @var \Drupal\key\KeyRepository
   */
  // phpcs:ignore
  protected $keyRepository;

  /**
   * Logger Interface.
   *
   * @var \Psr\Log\LoggerInterface
   */
  // phpcs:ignore
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected $pluginId;

  /**
   * {@inheritdoc}
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  protected $pluginDefinition;

  /**
   * Constructs a Drupal\bcbsma_mailsystem\Plugin\Mail object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\Client $httpClient
   *   The httpClient instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack.
   * @param \Drupal\key\KeyRepository $keyRepository
   *   KeyRepository.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger Interface.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    Client $httpClient,
    ConfigFactoryInterface $configFactory,
    RequestStack $request,
    KeyRepository $keyRepository,
    LoggerInterface $logger) {
    $this->httpClient = $httpClient;
    $this->config = $configFactory;
    $this->request = $request;
    $this->keyRepository = $keyRepository;
    $this->logger = $logger;
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('key.repository'),
      $container->get('logger.factory')->get('bcbsma_mailsystem')
    );
  }

  /**
   * Formats a message composed by drupal_mail().
   *
   * @param mixed[] $message
   *   A message array holding all relevant details for the message.
   *
   * @return mixed[]
   *   The message as it should be sent.
   */
  public function format(array $message): array {
    return $message;
  }

  /**
   * Sends a message composed by drupal_mail().
   *
   * @param mixed[] $message
   *   A message array holding all relevant details for the message.
   *
   * @return mixed[]
   *   If the message was successfully sent response, and otherwise error.
   */
  public function mail(array $message): mixed {
    // Retriving info for Mail Body.
    if (str_contains($message['headers']['Content-Type'], 'text/plain')) {
      $contentType = "text";
    }
    elseif (str_contains($message['headers']['Content-Type'], 'text/html')) {
      $contentType = "html";
    }
    else {
      $contentType = $message['headers']['Content-Type'];
    }
    if (empty($message['subject']) && empty($message['body'])) {
      $message['subject'] = $message['params']['subject'];
      $message['body'] = $message['params']['message'];
    }
    $bccRecepients = [];
    $toRecepients = [];
    if (is_array($message['to'])) {
      foreach ($message['to'] as $key => $to) {
        $toRecepients[$key] = [
          'emailAddress' => [
            'address' => $to,
          ],
        ];
      }
    }
    elseif (!empty($message['to'])) {
      $toRecepients[] =
      [
        'emailAddress' => [
          'address' => $message['to'],
        ],
      ];
    }
    else {
      $toRecepients = [];
      $bccRecepients[] =
      [
        'emailAddress' => [
          'address' => $message['Bcc'],
        ],
      ];
    }

    // Mail body.
    $mail_body = [
      'message' => [
        'subject' => $message['subject'],
        'body' => [
          'contentType' => $contentType,
          'content' => !empty($message['body'][0]) ? $message['body'][0] : $message['body']['body']->__toString(),
        ],
        'from' => [
          'emailAddress' => [
            'address' => $message['from'],
          ],
        ],
        'toRecipients' => $toRecepients,
        'bccRecipients' => $bccRecepients,
      ],
    ];

    // Retrive Configurtion from Key Entity.
    $key = $this->keyRepository->getKey('graph_mailer');
    $request = [];
    if (!is_null($key)) {
      $config = trim($key->getKeyValue() ?? "");
      if ($config) {
        $request = json_decode($config, TRUE);
      }
    }
    else {
      $request = [];
    }
    $payload = [
      'body' => json_encode($mail_body),
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $this->getAccessToken($request),
      ],
    ];
    $url = $request['apiDomain'] . '/' . $request['version'] . '/' . $request['apiRelativePath'];
    try {
      $response = $this->httpClient->post($url, $payload);
      return $response;
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
    return [];
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
        return '';
      }
      $url = $request['tokenUrl'] . $request['tenantId'] . $request['tokenRelativePath'];
      $token = json_decode($this->httpClient->request('POST', $url, [
        'form_params' => [
          'client_id' => $request['clientId'],
          'client_secret' => $request['clientSecret'],
          'scope' => $request['scope'],
          'grant_type' => $request['grant_type'],
        ],
      ])->getBody()->getContents());
      $accessToken = $token->access_token;
      return $accessToken;
    }
    catch (\Exception $e) {
      \Drupal::logger('graph_mailer')->error($e->getMessage());
    }
    return '';
  }

}

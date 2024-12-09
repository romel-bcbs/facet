<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Class for GetAccessTokenFromAPI Service.
 *
 * @package Drupal\bcbsma_webform_medicare_forms_api\Service
 */
class GetAccessTokenFromAPI implements ContainerInjectionInterface {

  /**
   * A LoggerChannelFactory instance.
   *
   * {@inheritdoc}
   */
  protected $logger;

  /**
   * A KeyRepository instance.
   *
   * {@inheritdoc}
   */
  protected $keyStore;

  /**
   * A GuzzleHttp Client instance.
   *
   * {@inheritdoc}
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct($logger, $keyStore, $requestStack) {
    $this->logger = $logger;
    $this->keyStore = $keyStore;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return [];
  }

  /**
   * Return only Access Code.
   *
   * {@inheritdoc}
   */
  public function getAccessToken($apiKeys, $formTokenApi): array {
    $apiKeysArray = $this->keyStore->getKey($apiKeys)->getKeyValues();
    try {
      $form_params['grant_type'] = 'client_credentials';
      $form_params['client_id'] = $apiKeysArray['Auth']['ClientID'];
      $form_params['client_secret'] = $apiKeysArray['Auth']['ClientSecret'];
      $form_params['scope'] = '';
      if (isset($apiKeysArray['Auth']['X-Apienvironment'])) {
        $form_params['X-Apienvironment'] = $apiKeysArray['Auth']['X-Apienvironment'];
      }
      $response = $this->requestStack->fromOptions()->request ('post', $formTokenApi, [
        'verify' => TRUE,
        'form_params' => $form_params,
        'headers' => [
          'Content-type' => 'application/x-www-form-urlencoded',
        ],
      ])->getBody()->getContents();
      $decoded['response'] = Json::decode($response);
      $decoded['X-ClientSessionId'] = md5(time());
      $this->logger->get('FormAPI - getAccessToken')->notice("Access Token - " . serialize($decoded));
      return $decoded;
    }
    catch (\Exception $e) {
      $decoded[] = "Error";
      $this->logger->get('FormAPI - getAccessToken')->notice("get_access_token - Exception - " . serialize($e));
    }
    return $decoded;
  }

}

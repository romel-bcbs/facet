<?php

namespace Drupal\bcbsma_plans\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\key\KeyRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for PSW Data Encryption routes.
 */
class PswDataEncryptionController extends ControllerBase {

  const KEY = "psw_encryption";

  /**
   * KeyRepository.
   *
   * @var \Drupal\key\KeyRepository
   */
  // phpcs:ignore
  protected $keyRepository;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  // phpcs:ignore
  protected $requestStack;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  // phpcs:ignore
  protected $logger;

  /**
   * PSW Data Encryption constructor.
   *
   * @param \Drupal\key\KeyRepository $keyRepository
   *   KeyRepository.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request Stack.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger Factory.
   */
  public function __construct(KeyRepository $keyRepository, RequestStack $requestStack, LoggerChannelFactoryInterface $logger) {
    $this->keyRepository = $keyRepository;
    $this->requestStack = $requestStack;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('key.repository'),
      $container->get('request_stack'),
      $container->get('logger.factory')
    );
  }

  /**
   * Encrypt the data provided.
   *
   * @param mixed[] $submittedData
   *   Date to encrypt.
   *
   * @return mixed[]
   *   Redirect URl.
   */
  public function encrypt(array $submittedData): array {

    // Creating Array with Expected Body.
    $expected_data = [
      'key',
      'iv',
      'redirectUrl',
    ];

    // Retrive Configurtion from Key Entity.
    $key = $this->keyRepository->getKey(self::KEY);
    if (!is_null($key)) {
      $config = trim($key->getKeyValue());
      $keyConfig = json_decode($config, TRUE);
    }

    // Return if empty.
    if (empty($keyConfig)) {
      $error = [
        'message' => 'missing key configs',
        'status' => 400,
      ];
      $this->logger->get('Enroll Now')->error($error['message']);
      return $error;
    }

    // Looping through Expected Array.
    // Checking all keys Exists if fails Returning error.
    foreach ($expected_data as $value) {
      if (!array_key_exists($value, $keyConfig)) {
        $error = [
          'message' => $this->t('The value of @value field is missing.', ['@value' => $value]),
          'redirecturl' => $keyConfig['redirectUrl'],
          'status' => 400,
        ];
        $this->logger->get('Enroll Now')->error($error['message']);
        return $error;
      }
    }
    // Logging what information We are encrypting.
    $this->logger->get('Enroll Now data to Encrypt')->info(print_r($submittedData, TRUE));
    $data = json_encode($submittedData);
    $encryptedData = FALSE;
    $encryptedData = openssl_encrypt($data, 'AES-256-CBC', $keyConfig['key'], OPENSSL_RAW_DATA, $keyConfig['iv']);
    $redirecturl = $keyConfig['redirectUrl'] . urlencode(base64_encode($encryptedData));

    // Returning Array of information.
    return [
      'redirecturl' => $redirecturl,
      'status' => 200,
    ];
  }

}

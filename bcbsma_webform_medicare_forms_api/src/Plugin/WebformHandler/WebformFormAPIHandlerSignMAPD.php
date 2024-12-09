<?php

declare(strict_types=1);

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sends Webform submission data to Form API.
 *
 * @WebformHandler(
 *   id = "medicare_forms_api_sign_designee_mapd",
 *   label = @Translation("MMSS - Medicare Forms API Sign Designee MAPD"),
 *   category = @Translation("MMSS Forms API"),
 *   description = @Translation("Sends a form submission data to form processing system through API"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformFormAPIHandlerSignMAPD extends WebformHandlerBase {

  /**
   * The Webform token manager.
   *
   * {@inheritdoc}
   */
  protected $webformTokenManager;

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
   * A entityTypeManagers instance.
   *
   * {@inheritdoc}
   */
  protected $entityTypeManager;

  /**
   * Request stack.
   *
   * {@inheritdoc}
   */
  protected $requestQuery;

  /**
   * A dateTime instance.
   *
   * {@inheritdoc}
   */
  protected $dateTime;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->webformTokenManager = $container->get('webform.token_manager');
    $instance->logger = $container->get('logger.factory');
    $instance->requestStack = $container->get('http_client_factory');
    $instance->keyStore = $container->get('key.repository');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->requestQuery = $container->get('request_stack');
    $instance->dateTime = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'client_id' => '',
      'client_secret' => '',
      'x_clientname' => '',
      'x_clientsessionid' => '',
      'x_clientversion' => '',
      'field_list' => '',
      'form_token_api' => '',
      'form_data_api' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $api_keys = array_keys($this->keyStore->getKeys());
    $api_keys_associated = array_combine($api_keys, $api_keys);
    $form['form_api_keys'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API keys'),
    ];
    $form['form_api_keys']['api_keys'] = [
      '#type' => 'select',
      '#title' => $this->t('API Keys list'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['api_keys'],
      '#options' => $api_keys_associated,
    ];
    $form['form_api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Form API settings'),
    ];
    $from_type_value['SignDesignee'] = "SignDesignee";
    $form['form_api_settings']['from_type'] = [
      '#title' => $this->t('Form Type'),
      '#type' => 'select',
      '#default_value' => $this->configuration['from_type'],
      '#options' => $from_type_value,
    ];
    $form['form_api_error'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Form API Error'),
    ];
    $form['form_api_error']['form_api_error_redirect_url'] = [
      '#title' => $this->t('Form API Error - redirect URL'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['form_api_error_redirect_url'],
    ];
    $form['form_api_settings']['token_tree_link'] = $this->webformTokenManager->buildTreeLink();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['api_keys'] = $form_state->getValue([
      'form_api_keys',
      'api_keys',
    ]);
    $this->configuration['from_type'] = $form_state->getValue([
      'form_api_settings',
      'from_type',
    ]);
    $this->configuration['form_api_error_redirect_url'] = $form_state->getValue([
      'form_api_error',
      'form_api_error_redirect_url',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $logger = $this->logger->get('WebformFormAPIHandlerForSignDesignee - postSave');
    if ($update) {
      $logger->notice('Coming to webform submission - updatesubmission');
    }
    $logger->notice('Coming to webform submission - create submission');
    $apiKeys = $this->keyStore->getKey($this->configuration['api_keys'])->getKeyValues();
    $getAccessToken = \Drupal::service('mmss_form.getaccesstoken')->getAccessToken($this->configuration['api_keys'], $apiKeys['Auth']['form_token_api']);
    $this->processFormDataSignForm($webform_submission, $getAccessToken['response'], $logger, $this->configuration['from_type'], $this->requestStack->fromOptions(), $apiKeys, $this->requestQuery, $this->configuration['form_api_error_redirect_url']);
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * Process the data.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Webform submission data.
   * @param mixed[] $getAccessToken
   *   Access Token.
   * @param \Drupal\Core\Logger\LoggerChannel $logger
   *   Logger Factory.
   * @param string $from_type
   *   Form Type.
   * @param \GuzzleHttp\Client $requestStack
   *   Guzzle Http Client.
   * @param mixed[] $apiKeys
   *   apiKeys.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestQuery
   *   requestQuery.
   * @param string $form_api_error_redirect_url
   *   Form API Error Redirect URL.
   *
   * @return bool
   *   Boolean.
   */
  private function processFormDataSignForm(WebformSubmission $webform_submission, array $getAccessToken, LoggerChannel $logger, string $from_type, Client $requestStack, array $apiKeys, RequestStack $requestQuery, string $form_api_error_redirect_url): bool {
    $body_array_returned = $this->fieldToApiDataParsingSignForm($webform_submission, $logger, $from_type);
    $body_array_returned_json = Json::encode($body_array_returned);
    $logger->info("Form Data JSON - " . $body_array_returned_json);
    try {
      $headers['Authorization'] = 'Bearer ' . $getAccessToken['access_token'];
      $headers['X-ClientName'] = $apiKeys['Auth']['x_clientname'];
      $headers['X-ClientSessionId'] = $apiKeys['Auth']['X-ClientSessionId'];
      $headers['X-ClientVersion'] = $apiKeys['Auth']['x_clientversion'];
      $headers['Content-Type'] = 'application/json';
      if (isset($apiKeys['Auth']['X-Apienvironment'])) {
        $headers['X-Apienvironment'] = $apiKeys['Auth']['X-Apienvironment'];
      }
      $form_data_api_response = $requestStack->post(str_replace("{{id}}", $requestQuery->getCurrentRequest()->query->get('token'), $apiKeys['Auth']['form_data_post_sign_api']), [
        'verify' => TRUE,
        'body' => $body_array_returned_json,
        'headers' => $headers,
      ])->getBody()->getContents();
      $logger->info("Form Data API Response - " . $form_data_api_response);
      $form_data_api_response_decoded = Json::decode($form_data_api_response);
      // UpOn Success - Need to store the Documentum ID with Submission data.
      if (in_array($form_data_api_response_decoded['status'], [
        "NEED_SIGNATURE",
        "SUBMITTED_TO_DOCUMENTUM",
        "SUBMITTED_BY_USER",
        "SUBMITTED_TO_APPIAN",
        "SUCCESS_POSTED_TO_KAFKA",
        "success",
      ]) || isset($form_data_api_response_decoded['id'])) {
        $webform_submission->setElementData('data_send_successfully_to_form_api', 1);
        $webform_submission->setElementData('documentum_id', $webform_submission->getElementData('documentum_id'));
        $webform_submission->resave();
      }
    }
    catch (\Exception $e) {
      $this->getWebform()->setSettingOverride('confirmation_url', $form_api_error_redirect_url);
      $logger->info("Attachment - RequestException - " . $e->__toString() . "-getBody-" . $e->getMessage());
    }
    return TRUE;
  }

  /**
   * Compose body Array -  to send data to JSON.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Webform submission data.
   * @param \Drupal\Core\Logger\LoggerChannel $logger
   *   Logger Factory.
   * @param string $from_type
   *   Form Type.
   *
   * @return mixed[]
   *   Body Composed Array.
   */
  private function fieldToApiDataParsingSignForm(WebformSubmission $webform_submission, LoggerChannel $logger, string $from_type): array {
    $body_composed_array['aorSignatureAcceptance'] = $webform_submission->getElementData('authorized_representative_acceptance');
    $body_composed_array['aorSignatureName'] = $webform_submission->getElementData('authorized_representative_sign_name');
    return $body_composed_array;
  }

}

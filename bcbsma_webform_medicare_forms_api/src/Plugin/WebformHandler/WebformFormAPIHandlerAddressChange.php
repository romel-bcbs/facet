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

/**
 * Sends Webform submission data to Form API.
 *
 * @WebformHandler(
 *   id = "medicare_forms_api_addresschange",
 *   label = @Translation("MMSS - Medicare Forms API Address Change"),
 *   category = @Translation("MMSS Forms API"),
 *   description = @Translation("Sends a form submission data to form processing system through API"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformFormAPIHandlerAddressChange extends WebformHandlerBase {

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
    $from_type_value['ADDRESS_VERIF_MAPD'] = "ADDRESS_VERIF_MAPD";
    $form['form_api_settings']['from_type'] = [
      '#title' => $this->t('Form Type'),
      '#type' => 'select',
      '#default_value' => $this->configuration['from_type'],
      '#options' => $from_type_value,
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
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $logger = $this->logger->get('WebformFormAPIHandlerForAddressChange - postSave');
    if ($update) {
      $logger->notice('Coming to webform submission - updatesubmission');
    }
    $logger->notice('Coming to webform submission - create submission');
    $apiKeys = $this->keyStore->getKey($this->configuration['api_keys'])->getKeyValues();
    $getAccessToken = \Drupal::service('mmss_form.getaccesstoken')->getAccessToken($this->configuration['api_keys'], $apiKeys['Auth']['form_token_api']);
    $this->processFormDataAddressChange($webform_submission, $getAccessToken['response'], $logger, $this->configuration['from_type'], $this->requestStack->fromOptions(), $apiKeys);
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
   *
   * @return bool
   *   Boolean.
   */
  private function processFormDataAddressChange(WebformSubmission $webform_submission, array $getAccessToken, LoggerChannel $logger, string $from_type, Client $requestStack, array $apiKeys): bool {
    $body_array_returned = $this->fieldToApiDataParsingAddressChange($webform_submission, $logger, $from_type);
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
      $form_data_api_response = $requestStack->post($apiKeys['Auth']['form_data_api'], [
        'verify' => TRUE,
        'body' => $body_array_returned_json,
        'headers' => $headers,
      ])->getBody()->getContents();
      $logger->info("Form Data API Response - " . $form_data_api_response);
      $logger->info("Form Data API Header - " . print_r($headers, TRUE));
      $form_data_api_response_decoded = Json::decode($form_data_api_response);
      // UpOn Success - Need to store the Documentum ID with Submission data.
      if (in_array($form_data_api_response_decoded['status'], [
        "NEED_SIGNATURE",
        "SUBMITTED_TO_DOCUMENTUM",
        "SUBMITTED_BY_USER",
        "SUBMITTED_TO_APPIAN",
        "SUCCESS_POSTED_TO_KAFKA",
        "INIT",
      ]) || isset($form_data_api_response_decoded['id'])) {
        $webform_submission->setElementData('data_send_successfully_to_form_api', 1);
        $webform_submission->setElementData('documentum_id', $form_data_api_response_decoded['documentumId']);
        $webform_submission->resave();
      }
    }
    catch (\Exception $e) {
      $logger->info("Change Request API - RequestException - " . $e->__toString() . "-getBody-" . $e->getMessage());
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
  private function fieldToApiDataParsingAddressChange(WebformSubmission $webform_submission, LoggerChannel $logger, string $from_type): array {
    $bcbsma_webform_medicare_forms_api = \Drupal::config('bcbsma_webform_medicare_forms_api.field_mapping');
    // Get field mapping data from Admin Config.
    $body_composed_array = [];
    $medicare_field_data_mapping_AOR_MAPD_exploded = explode(PHP_EOL, $bcbsma_webform_medicare_forms_api->get('medicare_field_data_mapping_addresschange'));
    foreach ($medicare_field_data_mapping_AOR_MAPD_exploded as $val) {
      $val_explode = explode('-', $val);
      if ($val_explode['0'] == 'formType') {
        $body_composed_array[$val_explode['0']] = trim($val_explode['1']);
        continue;
      }
      if ($val_explode['0'] == "memberSubmissionDate") {
        $body_composed_array['formData'][$val_explode['0']] = $this->dateTime->format(time(), 'custom', 'Y-m-d\TH:i:s.v', 'UTC') . 'Z';
        continue;
      }
      if ($val_explode['0'] == "memberId") {
        $body_composed_array['formData'][$val_explode['0']] = substr($webform_submission->getElementData(trim($val_explode['1'])), 3);
        if ($bcbsma_webform_medicare_forms_api->get('medicare_field_append_zeros_to_memberid')) {
          $body_composed_array['formData'][$val_explode['0']] = substr($webform_submission->getElementData(trim($val_explode['1'])), 3) . "000000";
        }
        continue;
      }
      if ($val_explode['0'] == "alternatePhoneNumber" || $val_explode['0'] == "phoneNumber" || $val_explode['0'] == "designeePhone") {
        $body_composed_array['formData'][$val_explode['0']] = str_replace(' ', '', $webform_submission->getElementData(trim($val_explode['1'])));
        continue;
      }
      if ($val_explode['0'] == "mailingAddressType") {
        $mailingAddressTypeArray = [
          $webform_submission->getElementData('mail_address_same_as_your_perm_or_temp_or_new_mailing_address'),
          $webform_submission->getElementData('your_mail_addr_same_as_your_ct_addr_or_perm_addr_or_another_addr'),
          $webform_submission->getElementData('your_mailing_address_the_same_as_your_perm_or_separate_mailing'),
          $webform_submission->getElementData('your_mailing_address_same_as_your_current_address_or_new_mailing'),
        ];
        $mailingAddressTypeArrayFilter = reset(array_filter($mailingAddressTypeArray));
        $body_composed_array['formData'][$val_explode['0']] = $mailingAddressTypeArrayFilter;
        continue;
      }
      if ($val_explode['0'] == "acknowledgeBox") {
        $acknowledgeBox_field_classigication = explode("::", $val_explode['1']);
        if ($webform_submission->getElementData('are_you_self_enrollee_or_caregiver') == 'Member') {
          $body_composed_array['formData'][$val_explode['0']] = $webform_submission->getElementData($acknowledgeBox_field_classigication[0]);
        }
        else {
          $body_composed_array['formData'][$val_explode['0']] = $webform_submission->getElementData($acknowledgeBox_field_classigication[1]);
        }
        continue;
      }
      if (isset($val_explode['1']) && $val_explode['1'] != 'NA') {
        $body_composed_array['formData'][$val_explode['0']] = $webform_submission->getElementData(trim($val_explode['1']));
      }
      else {
        $body_composed_array['formData'][$val_explode['0']] = "";
      }
    }
    return $body_composed_array;
  }

}

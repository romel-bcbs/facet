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
 *   id = "medicare_forms_api_legal_rep",
 *   label = @Translation("MMSS - Medicare Forms API Legal Rep"),
 *   category = @Translation("MMSS Forms API"),
 *   description = @Translation("Sends a form submission data to form processing system through API"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformFormAPIHandlerForLegalRep extends WebformHandlerBase {

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
    $from_type_value['LEGAL_REP'] = "LEGAL_REP";
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
    $logger = $this->logger->get('WebformFormAPIHandlerForLegalRep - postSave');
    if ($update) {
      $logger->notice('Coming to webform submission - updatesubmission');
    }
    $logger->notice('Coming to webform submission - create submission');
    $apiKeys = $this->keyStore->getKey($this->configuration['api_keys'])->getKeyValues();
    $getAccessToken = \Drupal::service('mmss_form.getaccesstoken')->getAccessToken($this->configuration['api_keys'], $apiKeys['Auth']['form_token_api']);
    $this->processFormDataLegal($webform_submission, $getAccessToken['response'], $logger, $this->configuration['from_type'], $this->requestStack->fromOptions(), $apiKeys);
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
  private function processFormDataLegal(WebformSubmission $webform_submission, array $getAccessToken, LoggerChannel $logger, string $from_type, Client $requestStack, array $apiKeys): bool {
    $body_array_returned = $this->fieldToApiDataParsingLegalRep($webform_submission, $logger);
    $legalRepDocSection = $this->fieldToApiDataParsingLegalRepDocSection($webform_submission, $logger);
    foreach ($legalRepDocSection as $val) {
      if (!empty($val['legalRepFirstName']) && !empty($val['legalRepLastName'])) {
        $body_array_returned['formData']['designees'][] = $val;
      }
    }
    $body_array_returned_json = Json::encode($body_array_returned);
    $logger->info("Form Data JSON - " . $body_array_returned_json);
    try {
      $form_data_api_response = $requestStack->post($apiKeys['Auth']['form_data_api'], [
        'verify' => TRUE,
        'body' => $body_array_returned_json,
        'headers' => [
          'Authorization' => 'Bearer ' . $getAccessToken['access_token'],
          'X-ClientName' => $apiKeys['Auth']['x_clientname'],
          'X-ClientSessionId' => $apiKeys['Auth']['X-ClientSessionId'],
          'X-ClientVersion' => $apiKeys['Auth']['x_clientversion'],
          'X-ApiEnvironment' => $apiKeys['Auth']['x-apienvironment'],
          'Content-Type' => 'application/json',
        ],
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
      ])) {
        $webform_submission->setElementData('data_send_successfully_to_form_api', 1);
        $webform_submission->setElementData('documentum_id', $form_data_api_response_decoded['documentumId']);
        $webform_submission->resave();
      }
    }
    catch (\Exception $e) {
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
   *
   * @return mixed[]
   *   Body Composed Array.
   */
  private function fieldToApiDataParsingLegalRep(WebformSubmission $webform_submission, LoggerChannel $logger): array {
    $bcbsma_webform_medicare_forms_api = \Drupal::config('bcbsma_webform_medicare_forms_api.field_mapping');
    // Get field mapping data from Admin Config.
    $medicare_field_data_mapping_AOR_MAPD_exploded = explode(PHP_EOL, $bcbsma_webform_medicare_forms_api->get('medicare_field_data_mapping_legal_rep'));
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
      if ($val_explode['0'] == "acknowledgeBox") {
        $body_composed_array['formData'][$val_explode['0']] = (int) $webform_submission->getElementData(trim($val_explode['1']));
        continue;
      }
      if ($val_explode['0'] == "phoneNumber") {
        $body_composed_array['formData'][$val_explode['0']] = preg_replace('/\s+/', '', $webform_submission->getElementData(trim($val_explode['1'])));
        continue;
      }
      if ($val_explode['0'] == "memberId") {
        $body_composed_array['formData'][$val_explode['0']] = substr($webform_submission->getElementData(trim($val_explode['1'])), 3);
        if ($bcbsma_webform_medicare_forms_api->get('medicare_field_append_zeros_to_memberid')) {
          $body_composed_array['formData'][$val_explode['0']] = substr($webform_submission->getElementData(trim($val_explode['1'])), 3) . "000000";
        }
        continue;
      }
      if ($val_explode['1']) {
        $body_composed_array['formData'][$val_explode['0']] = $webform_submission->getElementData(trim($val_explode['1']));
      }
      else {
        $body_composed_array['formData'][$val_explode['0']] = "";
      }
    }
    return $body_composed_array;
  }

  /**
   * Compose body Array -  to send data to JSON.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Webform submission data.
   * @param \Drupal\Core\Logger\LoggerChannel $logger
   *   Logger Factory.
   *
   * @return mixed[]
   *   Body Composed Array.
   */
  private function fieldToApiDataParsingLegalRepDocSection(WebformSubmission $webform_submission, LoggerChannel $logger): array {
    // Get field option data from legal_representative_form webform.
    $webform = $this->entityTypeManager->getStorage('webform')->load('legal_representative_form');
    $docSourceType = $webform->getElement('power_of_attorney_select_type_of_document');
    $docSourceType_select_list = $docSourceType['#options'];

    // Section 1 - document consolidation.
    $document_section_values[0]['docType'] = $docSourceType_select_list[$webform_submission->getElementData('power_of_attorney_select_type_of_document')];
    $document_section_values[0]['otherRequiredFormsOfDocumentation'] = $webform_submission->getElementData('power_of_attorney_other_document');
    $document_section_values[0]['legalRepFirstName'] = $webform_submission->getElementData('first_name_power_of_attorney_legal_rep');
    $document_section_values[0]['legalRepLastName'] = $webform_submission->getElementData('last_name_power_of_attorney_legal_rep');
    $document_section_values[0]['legalRepPhone'] = preg_replace('/\s+/', '', $webform_submission->getElementData('phonenumber_power_of_attorney_legal_rep'));
    $document_section_values[0]['legalRepEmail'] = $webform_submission->getElementData('email_power_of_attorney_legal_rep');
    $document_section_values[0]['legalRepAddressStreet'] = $webform_submission->getElementData('street_address_power_of_attorney_legal_rep');
    $document_section_values[0]['legalRepAddressUnitNumber'] = $webform_submission->getElementData('apart_num_power_of_attorney_legal_rep');
    $document_section_values[0]['legalRepAddressCity'] = $webform_submission->getElementData('city_power_of_attorney_legal_rep');
    $document_section_values[0]['legalRepAddressState'] = $webform_submission->getElementData('state_power_of_attorney_legal_rep');
    $document_section_values[0]['legalRepAddressZipCode'] = $webform_submission->getElementData('zip_code_power_of_attorney_legal_rep');
    $document_section_values[0]['courtAppointedGuardianConservatorshipExpirationDate'] = $webform_submission->getElementData('power_of_attorney_court_guardianship_conservatorship_end_date');
    $document_section_values[0]['attachments'] = $this->consolidateFileAttachment($webform_submission->getElementData('power_of_attorney_upload_files'));
    // Section 2 - document consolidation.
    $document_section_values[1]['docType'] = $docSourceType_select_list[$webform_submission->getElementData('health_care_proxy_select_type_of_document')];
    $document_section_values[1]['otherRequiredFormsOfDocumentation'] = $webform_submission->getElementData('health_care_proxy_other_document');
    $document_section_values[1]['legalRepFirstName'] = $webform_submission->getElementData('first_name_health_care_proxy_legal_rep');
    $document_section_values[1]['legalRepLastName'] = $webform_submission->getElementData('last_name_health_care_proxy_legal_rep');
    $document_section_values[1]['legalRepPhone'] = preg_replace('/\s+/', '', $webform_submission->getElementData('phonenumber_health_care_proxy_legal_rep'));
    $document_section_values[1]['legalRepEmail'] = $webform_submission->getElementData('email_health_care_proxy_legal_rep');
    $document_section_values[1]['legalRepAddressStreet'] = $webform_submission->getElementData('street_address_health_care_proxy_legal_rep');
    $document_section_values[1]['legalRepAddressUnitNumber'] = $webform_submission->getElementData('apart_num_health_care_proxy_legal_rep');
    $document_section_values[1]['legalRepAddressCity'] = $webform_submission->getElementData('city_health_care_proxy_legal_rep');
    $document_section_values[1]['legalRepAddressState'] = $webform_submission->getElementData('state_health_care_proxy_legal_rep');
    $document_section_values[1]['legalRepAddressZipCode'] = $webform_submission->getElementData('zip_code_health_care_proxy_legal_rep');
    $document_section_values[1]['courtAppointedGuardianConservatorshipExpirationDate'] = $webform_submission->getElementData('health_care_proxy_court_guardianship_conservatorship_end_date');
    $document_section_values[1]['attachments'] = $this->consolidateFileAttachment($webform_submission->getElementData('health_care_proxy_upload_files'));
    // Section 3 - document consolidation.
    $document_section_values[2]['docType'] = $docSourceType_select_list[$webform_submission->getElementData('executor_of_estate_select_type_of_document')];
    $document_section_values[2]['otherRequiredFormsOfDocumentation'] = $webform_submission->getElementData('executor_of_estate_other_document');
    $document_section_values[2]['legalRepFirstName'] = $webform_submission->getElementData('first_name_executor_of_estate_legal_rep');
    $document_section_values[2]['legalRepLastName'] = $webform_submission->getElementData('last_name_executor_of_estate_legal_rep');
    $document_section_values[2]['legalRepPhone'] = preg_replace('/\s+/', '', $webform_submission->getElementData('phonenumber_executor_of_estate_legal_rep'));
    $document_section_values[2]['legalRepEmail'] = $webform_submission->getElementData('email_executor_of_estate_legal_rep');
    $document_section_values[2]['legalRepAddressStreet'] = $webform_submission->getElementData('street_address_executor_of_estate_legal_rep');
    $document_section_values[2]['legalRepAddressUnitNumber'] = $webform_submission->getElementData('apart_num_executor_of_estate_legal_rep');
    $document_section_values[2]['legalRepAddressCity'] = $webform_submission->getElementData('city_executor_of_estate_legal_rep');
    $document_section_values[2]['legalRepAddressState'] = $webform_submission->getElementData('state_executor_of_estate_legal_rep');
    $document_section_values[2]['legalRepAddressZipCode'] = $webform_submission->getElementData('zip_code_executor_of_estate_legal_rep');
    $document_section_values[2]['courtAppointedGuardianConservatorshipExpirationDate'] = $webform_submission->getElementData('executor_of_estate_court_guardianship_conservatorship_end_date');
    $document_section_values[2]['attachments'] = $this->consolidateFileAttachment($webform_submission->getElementData('executor_of_estate_upload_files'));
    // Section 4 - document consolidation.
    $document_section_values[3]['docType'] = $docSourceType_select_list[$webform_submission->getElementData('court_appointed_guardian_select_type_of_document')];
    $document_section_values[3]['otherRequiredFormsOfDocumentation'] = $webform_submission->getElementData('court_appointed_guardian_other_document');
    $document_section_values[3]['legalRepFirstName'] = $webform_submission->getElementData('first_name_court_appointed_guardian_legal_rep');
    $document_section_values[3]['legalRepLastName'] = $webform_submission->getElementData('last_name_court_appointed_guardian_legal_rep');
    $document_section_values[3]['legalRepPhone'] = preg_replace('/\s+/', '', $webform_submission->getElementData('phonenumber_court_appointed_guardian_legal_rep'));
    $document_section_values[3]['legalRepEmail'] = $webform_submission->getElementData('email_court_appointed_guardian_legal_rep');
    $document_section_values[3]['legalRepAddressStreet'] = $webform_submission->getElementData('street_address_court_appointed_guardian_legal_rep');
    $document_section_values[3]['legalRepAddressUnitNumber'] = $webform_submission->getElementData('apart_num_court_appointed_guardian_legal_rep');
    $document_section_values[3]['legalRepAddressCity'] = $webform_submission->getElementData('city_court_appointed_guardian_legal_rep');
    $document_section_values[3]['legalRepAddressState'] = $webform_submission->getElementData('state_court_appointed_guardian_legal_rep');
    $document_section_values[3]['legalRepAddressZipCode'] = $webform_submission->getElementData('zip_code_court_appointed_guardian_legal_rep');
    $document_section_values[3]['courtAppointedGuardianConservatorshipExpirationDate'] = $webform_submission->getElementData('guardianship_conservatorship_end_date');
    $document_section_values[3]['attachments'] = $this->consolidateFileAttachment($webform_submission->getElementData('court_appointed_guardian_upload_files'));
    // Section 5 - document consolidation.
    $document_section_values[4]['docType'] = $docSourceType_select_list[$webform_submission->getElementData('other_document_select_type_of_document')];
    $document_section_values[4]['otherRequiredFormsOfDocumentation'] = $webform_submission->getElementData('other_document_other_document');
    $document_section_values[4]['legalRepFirstName'] = $webform_submission->getElementData('first_name_other_document_legal_rep');
    $document_section_values[4]['legalRepLastName'] = $webform_submission->getElementData('last_name_other_document_legal_rep');
    $document_section_values[4]['legalRepPhone'] = preg_replace('/\s+/', '', $webform_submission->getElementData('phonenumber_other_document_legal_rep'));
    $document_section_values[4]['legalRepEmail'] = $webform_submission->getElementData('email_other_document_legal_rep');
    $document_section_values[4]['legalRepAddressStreet'] = $webform_submission->getElementData('street_address_other_document_legal_rep');
    $document_section_values[4]['legalRepAddressUnitNumber'] = $webform_submission->getElementData('apart_num_other_document_legal_rep');
    $document_section_values[4]['legalRepAddressCity'] = $webform_submission->getElementData('city_other_document_legal_rep');
    $document_section_values[4]['legalRepAddressState'] = $webform_submission->getElementData('state_other_document_legal_rep');
    $document_section_values[4]['legalRepAddressZipCode'] = $webform_submission->getElementData('zip_code_other_document_legal_rep');
    $document_section_values[4]['courtAppointedGuardianConservatorshipExpirationDate'] = $webform_submission->getElementData('other_document_court_guardianship_conservatorship_end_date');
    $document_section_values[4]['attachments'] = $this->consolidateFileAttachment($webform_submission->getElementData('other_document_upload_files'));
    $document_section_values_return_array = [];
    foreach ($document_section_values as $val) {
      if (!empty($val['legalRepFirstName']) && !empty($val['legalRepLastName'])) {
        $document_section_values_return_array[] = $val;
      }
    }
    return $document_section_values_return_array;
  }

  /**
   * Process the data.
   *
   * @param string $uploaded_files
   *   Uploaded files.
   *
   * @return mixed[]
   *   Attachemnt Array.
   */
  private function consolidateFileAttachment(string $uploaded_files): array {
    $attachments_field_raw_array = explode(',', $uploaded_files);
    $attachments_field_raw_array_formated = [];
    foreach ($attachments_field_raw_array as $attachments_field_raw_array_val) {
      if (!empty($attachments_field_raw_array_val)) {
        $attachments_field_raw_sub_array = explode('::', $attachments_field_raw_array_val);
        if ($attachments_field_raw_sub_array[0] != 'undefined') {
          $attachments_field_raw_array_formated_pre['id'] = $attachments_field_raw_sub_array[0];
          $attachments_field_raw_array_formated_pre['secretId'] = $attachments_field_raw_sub_array[1];
          $attachments_field_raw_array_formated[] = $attachments_field_raw_array_formated_pre;
        }
      }
    }
    return $attachments_field_raw_array_formated;
  }

}

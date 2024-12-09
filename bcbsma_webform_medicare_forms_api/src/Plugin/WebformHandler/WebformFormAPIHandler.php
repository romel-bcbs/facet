<?php

declare(strict_types=1);

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends Webform submission data to Form API.
 *
 * @WebformHandler(
 *   id = "medicare_forms_api",
 *   label = @Translation("MMSS - Medicare Forms API"),
 *   category = @Translation("MMSS Forms API"),
 *   description = @Translation("Sends a form submission data to form processing system through API"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformFormAPIHandler extends WebformHandlerBase {

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
    $webform_ids = $this->getWebform();
    $elements = $webform_ids->getElementsDecodedAndFlattened();
    $element_type = [
      "textfield",
      "email",
      "checkbox",
      "tel",
      "select",
      "radios",
      "text_format",
    ];
    $elements_fields_list = [];
    foreach ($elements as $key => $value) {
      if (in_array($value['#type'], $element_type)) {
        $elements_fields_list[$key] = $key;
      }
    }
    $this->logger->get('form_api_settings-webform_handler - MAPD/Medex')->notice('elements_fields_list : @elements_fields_list', [
      '@elements_fields_list' => implode('::', $elements_fields_list),
    ]);
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
    $from_type_value['AOR_MAPD'] = "AOR_MAPD";
    $from_type_value['AOR_MEDEX'] = "AOR_MEDEX";
    $form['form_api_settings']['from_type'] = [
      '#title' => $this->t('Form Type'),
      '#type' => 'select',
      '#default_value' => $this->configuration['from_type'],
      '#options' => $from_type_value,
    ];
    $form['form_api_settings']['field_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Fields List to send'),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#options' => $elements_fields_list,
      '#default_value' => $this->configuration['field_list'],
      '#description' => $this->t('Select the list you want to send this submission to. Alternatively, you can also use the Other field for token replacement.'),
    ];
    $form['form_api_settings']['token_tree_link'] = $this->webformTokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['field_list'] = $form_state->getValue([
      'form_api_settings',
      'field_list',
    ]);
    $this->configuration['from_type'] = $form_state->getValue([
      'form_api_settings',
      'from_type',
    ]);
    $this->configuration['api_keys'] = $form_state->getValue([
      'form_api_keys',
      'api_keys',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    if ($update) {
      $this->logger->get('form_api_settings-webform_handler - MAPD/Medex')->notice('Comming to webform submission - updatesubmission');
    }
    $this->logger->get('form_api_settings-webform_handler - MAPD/Medex')->notice('Comming to webform submission - create submission');
    $apiKeys = $this->keyStore->getKey($this->configuration['api_keys'])->getKeyValues();
    $get_access_token = \Drupal::service('mmss_form.getaccesstoken')->getAccessToken($this->configuration['api_keys'], $apiKeys['Auth']['form_token_api']);
    $this->processFormData($webform_submission, $this->configuration['api_keys'], $get_access_token['response'], $this->logger, $this->configuration['from_type'], $this->requestStack->fromOptions());
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * Process the data based on MAPD/MEDEX and construct required JSON.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Webform submission data.
   * @param string $apiKeys
   *   API Key.
   * @param mixed[] $get_access_token
   *   Access token Array.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Logger Factory.
   * @param string $from_type
   *   Submission type / form Type.
   * @param \GuzzleHttp\Client $requestStack
   *   Guzzle Http Client.
   */
  private function processFormData(WebformSubmission $webform_submission, string $apiKeys, array $get_access_token, LoggerChannelFactory $logger, string $from_type, Client $requestStack): void {
    $apiKeysArray = $this->keyStore->getKey($apiKeys)->getKeyValues();
    $body_array_returned = [];
    if ($from_type == 'AOR_MAPD') {
      $body_array_returned = $this->fieldToApiDataParsingAorMapd($webform_submission, $logger);
    }
    elseif ($from_type == 'AOR_MEDEX') {
      $body_array_returned = $this->fieldToApiDataParsingAorMedex($webform_submission, $logger);
    }
    $body_array_returned_json = Json::encode($body_array_returned);
    $logger->get('body_array_returned_json-webform_handler - MAPD/Medex')->info("Form Data JSON - " . $body_array_returned_json);
    try {
      $headers['Authorization'] = 'Bearer ' . $get_access_token['access_token'];
      $headers['X-ClientName'] = $apiKeysArray['Auth']['x_clientname'];
      $headers['X-ClientSessionId'] = $apiKeysArray['Auth']['X-ClientSessionId'];
      $headers['X-ClientVersion'] = $apiKeysArray['Auth']['x_clientversion'];
      $headers['Content-Type'] = 'application/json';
      if (isset($apiKeysArray['Auth']['X-Apienvironment'])) {
        $headers['X-Apienvironment'] = $apiKeysArray['Auth']['X-Apienvironment'];
      }
      $form_data_api_response = $requestStack->post($apiKeysArray['Auth']['form_data_api'], [
        'verify' => TRUE,
        'body' => $body_array_returned_json,
        'headers' => $headers,
      ])->getBody()->getContents();
      $logger->get('Data API Response -webform_handler - MAPD/Medex')->info("Form Data API Response - " . $form_data_api_response);
      $form_data_api_response_decoded = Json::decode($form_data_api_response);
      $statusCheckArray = [
        "NEED_SIGNATURE",
        "PENDING_SIGNATURE",
        "SUBMITTED_TO_DOCUMENTUM",
        "SUBMITTED_BY_USER",
        "SUBMITTED_TO_APPIAN",
        "SUCCESS_POSTED_TO_KAFKA",
      ];
      if (isset($form_data_api_response_decoded['status']) && in_array($form_data_api_response_decoded['status'], $statusCheckArray)) {
        $webform_submission->setElementData('data_send_successfully_to_form_api', 1);
        $webform_submission->setElementData('documentum_id', $form_data_api_response_decoded['documentumId']);
        $webform_submission->resave();
      }
    }

    catch (\Exception $e) {
      $logger->get('webform_handler - Exception - MAPD/Medex')->info("process_form_data - RequestException - getStatusCode - " . $e->getCode() . " -getReasonPhrase- " . $e->getMessage());
    }
  }

  /**
   * Build the data based on MEDEX and construct required array.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Webform submission data.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Logger Factory.
   *
   * @return mixed[]
   *   form submission information.
   */
  private function fieldToApiDataParsingAorMedex(WebformSubmission $webform_submission, LoggerChannelFactory $logger): array {
    $body_composed_array = [];
    $bcbsma_webform_medicare_forms_api = \Drupal::config('bcbsma_webform_medicare_forms_api.field_mapping');
    $medicare_field_data_mapping_AOR_MAPD_exploded = explode(PHP_EOL, $bcbsma_webform_medicare_forms_api->get('medicare_field_data_mapping_AOR_MEDEX'));
    foreach ($medicare_field_data_mapping_AOR_MAPD_exploded as $val) {
      $val_explode = explode('-', $val);
      if ($val_explode['0'] == 'formType') {
        $body_composed_array[$val_explode['0']] = trim($val_explode['1']);
        continue;
      }
      if ($val_explode['0'] == 'address') {
        $member_apartment_or_unit_number = $webform_submission->getElementData('member_apartment_or_unit_number') ? ', ' . $webform_submission->getElementData('member_apartment_or_unit_number') : "";
        $body_composed_array['formData'][$val_explode['0']] = $webform_submission->getElementData('member_street_address')
          . "" . $member_apartment_or_unit_number
          . ', ' . $webform_submission->getElementData('member_city')
          . ', ' . $webform_submission->getElementData('member_state')
          . ', ' . $webform_submission->getElementData('member_zip_code');
        continue;
      }
      if (
        $val_explode['0'] == "agreeToDiscloseSensitiveInfoHIVOrAids" ||
        $val_explode['0'] == "agreeToDiscloseSensitiveInfoMentalOrBehaviorialHealth" ||
        $val_explode['0'] == "agreeToDiscloseSensitiveInfoAlcoholAndSubstanceAbuse"
      ) {
        $agreeToDisclose_explode = explode('#', $val_explode['1']);
        if (trim($agreeToDisclose_explode['0']) != "NA") {
          $agreeToDisclose_explode_array = $webform_submission->getElementData(trim($agreeToDisclose_explode['0']));
          if (is_array($agreeToDisclose_explode_array)) {
            $body_composed_array['formData'][$val_explode['0']] = in_array(trim($agreeToDisclose_explode['1']), $agreeToDisclose_explode_array);
          }
          else {
            $body_composed_array['formData'][$val_explode['0']] = 0;
          }
          continue;
        }
      }
      if (
        $val_explode['0'] == "agreeToDiscloseAlcoholReasonCoordinationWithCare" ||
        $val_explode['0'] == "agreeToDiscloseAlcoholReasonAssistWithTreament" ||
        $val_explode['0'] == "agreeToDiscloseAlcoholReasonOther" ||
        $val_explode['0'] == "agreeToDiscloseAlcoholReasonToAssistWithClaims"
      ) {
        $agreeToDiscloseSubstanceAbuse_explode = explode('#', $val_explode['1']);
        if (trim($agreeToDiscloseSubstanceAbuse_explode['0']) != "NA") {
          $agreeToDiscloseSubstanceAbuse_explode_array = $webform_submission->getElementData(trim($agreeToDiscloseSubstanceAbuse_explode['0']));
          if (is_array($agreeToDiscloseSubstanceAbuse_explode_array)) {
            if (in_array('others', $agreeToDiscloseSubstanceAbuse_explode_array) && $val_explode['0'] == "agreeToDiscloseAlcoholReasonOther") {
              $body_composed_array['formData']['agreeToDiscloseAlcoholReasonOtherDetails'] = $webform_submission->getElementData('if_alcohol_and_substance_abuse_other');
            }
            $body_composed_array['formData'][$val_explode['0']] = in_array(trim($agreeToDiscloseSubstanceAbuse_explode['1']), $agreeToDiscloseSubstanceAbuse_explode_array);
          }
          else {
            $body_composed_array['formData'][$val_explode['0']] = FALSE;
          }
          continue;
        }
      }
      if ($val_explode['0'] == "designeeAuthorizationOneYearExpirationSignature") {
        $agreeToDiscloseSubstanceAbuse_explode = explode('#', $val_explode['1']);
        if (trim($agreeToDiscloseSubstanceAbuse_explode['0']) != "NA") {
          $body_composed_array['formData']['designeeAuthorizationExpirationDate'] = "";
          if ($webform_submission->getElementData(trim($val_explode['1'])) == "Other") {
            $body_composed_array['formData']['designeeAuthorizationExpirationDate'] = date("m/d/Y", strtotime($webform_submission->getElementData('this_authorization_expires_check_one_other')));
            $body_composed_array['formData'][$val_explode['0']] = 0;
            continue;
          }
        }
      }
      if (
        $val_explode['0'] == "agreeToDiscussAppeals" ||
        $val_explode['0'] == "agreeToDiscussClaimsAndPayments" ||
        $val_explode['0'] == "agreeToDiscussEligibilityAndEnrollment" ||
        $val_explode['0'] == "agreeToDiscussPharmacy" ||
        $val_explode['0'] == "agreeToDiscussBenefitsAndCoverage" ||
        $val_explode['0'] == "agreeToDiscussDental" ||
        $val_explode['0'] == "agreeToDiscussFinancial" ||
        $val_explode['0'] == "agreeToDiscussBilling" ||
        $val_explode['0'] == "agreeToDiscussDiagnosisAndProcedure" ||
        $val_explode['0'] == "agreeToDiscussMedicalRecords" ||
        $val_explode['0'] == "agreeToDiscussOther"
      ) {
        $agreeToExclude_explode = explode('#', $val_explode['1']);
        if (trim($agreeToExclude_explode['0']) != "NA" && !$webform_submission->getElementData('all_my_information')) {
          $agreeToExclude_explode_array = $webform_submission->getElementData(trim($agreeToExclude_explode['0']));
          if (is_array($agreeToExclude_explode_array)) {
            $body_composed_array['formData'][$val_explode['0']] = (in_array(trim($agreeToExclude_explode['1']), $agreeToExclude_explode_array))
              ? TRUE : FALSE;
          }
          else {
            $body_composed_array['formData'][$val_explode['0']] = FALSE;
          }
        }
        else {
          $body_composed_array['formData'][$val_explode['0']] = FALSE;
        }
        continue;
      }
      if ($val_explode['0'] == "agreeToDiscloseAlcoholReasonOtherDetails" || $val_explode['0'] == "designeeAuthorizationExpirationDate") {
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
      if ($val_explode['0'] == "agreeToGrantPermissionToDiscussAllInformation") {
        $body_composed_array['formData'][$val_explode['0']] = FALSE;
        $agreeToGrantPermissionToDiscussAllInformation_value = $webform_submission->getElementData(trim($val_explode['1']));
        if (!empty($agreeToGrantPermissionToDiscussAllInformation_value)) {
          $body_composed_array['formData'][$val_explode['0']] = TRUE;
        }
        continue;
      }
      if ($val_explode['0'] == "phoneNumber" || $val_explode['0'] == "designeePhone") {
        $body_composed_array['formData'][$val_explode['0']] = preg_replace('/\s+/', '', $webform_submission->getElementData(trim($val_explode['1'])));
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
   * Build the data based on MAPD and construct required array.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Webform submission data.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Logger Factory.
   *
   * @return mixed[]
   *   form submission information.
   */
  private function fieldToApiDataParsingAorMapd(WebformSubmission $webform_submission, LoggerChannelFactory $logger): array {
    $body_composed_array = [];
    $bcbsma_webform_medicare_forms_api = \Drupal::config('bcbsma_webform_medicare_forms_api.field_mapping');
    $medicare_field_data_mapping_AOR_MAPD_exploded = explode(PHP_EOL, $bcbsma_webform_medicare_forms_api->get('medicare_field_data_mapping_AOR_MAPD'));
    foreach ($medicare_field_data_mapping_AOR_MAPD_exploded as $val) {
      $val_explode = explode('-', $val);
      if ($val_explode['0'] == 'formType') {
        $body_composed_array[$val_explode['0']] = trim($val_explode['1']);
        continue;
      }
      if ($val_explode['0'] == 'address') {
        $member_apartment_or_unit_number = $webform_submission->getElementData('member_apartment_or_unit_number') ? ', ' . $webform_submission->getElementData('member_apartment_or_unit_number') : "";
        $body_composed_array['formData'][$val_explode['0']] = $webform_submission->getElementData('member_street_address')
          . "" . $member_apartment_or_unit_number
          . ', ' . $webform_submission->getElementData('member_city')
          . ', ' . $webform_submission->getElementData('member_state')
          . ', ' . $webform_submission->getElementData('member_zip_code');
        continue;
      }
      if (
        $val_explode['0'] == "agreeToDiscloseSensitiveInfoHIVOrAids" ||
        $val_explode['0'] == "agreeToDiscloseSensitiveInfoMentalOrBehaviorialHealth" ||
        $val_explode['0'] == "agreeToDiscloseSensitiveInfoAlcoholAndSubstanceAbuse" ||
        $val_explode['0'] == "agreeToGrantPermissionToDiscussAllInformation"
      ) {
        $agreeToDisclose_explode = explode('#', $val_explode['1']);
        if (trim($agreeToDisclose_explode['0']) != "NA") {
          $agreeToDisclose_explode_array = $webform_submission->getElementData(trim($agreeToDisclose_explode['0']));
          if (is_array($agreeToDisclose_explode_array)) {
            $body_composed_array['formData'][$val_explode['0']] = in_array(trim($agreeToDisclose_explode['1']), $agreeToDisclose_explode_array);
          }
          else {
            $body_composed_array['formData'][$val_explode['0']] = 0;
          }
          continue;
        }
      }
      if (
        $val_explode['0'] == "agreeToDiscloseAlcoholReasonCoordinationWithCare" ||
        $val_explode['0'] == "agreeToDiscloseAlcoholReasonAssistWithTreament" ||
        $val_explode['0'] == "agreeToDiscloseAlcoholReasonOther" ||
        $val_explode['0'] == "agreeToDiscloseAlcoholReasonToAssistWithClaims"
      ) {
        $agreeToDiscloseSubstanceAbuse_explode = explode('#', $val_explode['1']);
        if (trim($agreeToDiscloseSubstanceAbuse_explode['0']) != "NA") {
          $agreeToDiscloseSubstanceAbuse_explode_array = $webform_submission->getElementData(trim($agreeToDiscloseSubstanceAbuse_explode['0']));
          if (is_array($agreeToDiscloseSubstanceAbuse_explode_array)) {
            if (in_array('others', $agreeToDiscloseSubstanceAbuse_explode_array) && $val_explode['0'] == "agreeToDiscloseAlcoholReasonOther") {
              $body_composed_array['formData']['agreeToDiscloseAlcoholReasonOtherDetails'] = $webform_submission->getElementData('if_alcohol_and_substance_abuse_other');
            }
            $body_composed_array['formData'][$val_explode['0']] = in_array(trim($agreeToDiscloseSubstanceAbuse_explode['1']), $agreeToDiscloseSubstanceAbuse_explode_array);
          }
          else {
            $body_composed_array['formData'][$val_explode['0']] = 0;
          }
          continue;
        }
      }
      if ($val_explode['0'] == "agreeToDiscloseAlcoholReasonOtherDetails") {
        continue;
      }
      if ($val_explode['0'] == "memberSubmissionDate") {
        $body_composed_array['formData'][$val_explode['0']] = $this->dateTime->format(time(), 'custom', 'Y-m-d\TH:i:s.v', 'UTC') . 'Z';
        continue;
      }
      if ($val_explode['0'] == "phoneNumber" || $val_explode['0'] == "designeePhone") {
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
      if ($val_explode['0'] == "agreeToGrantPermissionToDiscuss" || $val_explode['0'] == 'acknowledgeBox') {
        $body_composed_array['formData'][$val_explode['0']] = (int) $webform_submission->getElementData(trim($val_explode['1']));
        continue;
      }

      $body_composed_array['formData'][$val_explode['0']] = $webform_submission->getElementData(trim($val_explode['1']));
    }
    return $body_composed_array;
  }

}

<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Render\Markup;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Webform data handler.
 *
 * @WebformHandler(
 *   id = "webform_sign_mapd_form_alter",
 *   label = @Translation("Alter form fields - Sign MAPD Form alter fields."),
 *   category = @Translation("MedicareProject - Sign MAPD Form alter fields"),
 *   description = @Translation("Hide fields - while editing submission"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class WebformFormSignMAPDFormAlter extends WebformHandlerBase {
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
   * Request stack.
   *
   * {@inheritdoc}
   */
  protected $requestQuery;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory');
    $instance->webformTokenManager = $container->get('webform.token_manager');
    $instance->keyStore = $container->get('key.repository');
    $instance->requestStack = $container->get('http_client_factory');
    $instance->requestQuery = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_keys' => '',
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
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $form_data_array = [];
    $form['#cache'] = ['max-age' => 0];
    $logger = $this->logger->get('WebformFormAPIHandlerForSign - alterForm');
    $logger->notice('WebformFormAPIHandlerForSeminar - create submission');
    $apiKeys = $this->keyStore->getKey($this->configuration['api_keys'])->getKeyValues();
    $getAccessToken = \Drupal::service('mmss_form.getaccesstoken')->getAccessToken($this->configuration['api_keys'], $apiKeys['Auth']['form_token_api']);
    $form_data_array = $this->processFormDataSignFormGet($webform_submission, $getAccessToken['response'], $logger, "SEMINAR", $this->requestStack->fromOptions(), $apiKeys, $this->requestQuery);
    if (empty($form_data_array)) {
      unset($form['elements']['expired_container']);
      unset($form['elements']['already_signed_container']);
      unset($form['elements']['form_container']);
      unset($form['actions']);
    }
    elseif (isset($form_data_array['status'])
    && $form_data_array['status']
    == 'FORM_SIGNATURE_DATE_EXPIRED') {
      unset($form['elements']['already_signed_container']);
      unset($form['elements']['form_container']);
      unset($form['elements']['token_invalid_container']);
      unset($form['actions']);
    }
    elseif (isset($form_data_array['error']['message'])
    && $form_data_array['error']['code'] == 'FORM_NOT_FOUND') {
      unset($form['elements']['already_signed_container']);
      unset($form['elements']['form_container']);
      unset($form['elements']['expired_container']);
      unset($form['actions']);
    }
    elseif (isset($form_data_array['status'])
    && $form_data_array['status'] == 'FORM_SIGNED') {
      unset($form['elements']['token_invalid_container']);
      unset($form['elements']['form_container']);
      unset($form['elements']['expired_container']);
      unset($form['actions']);
    }
    else {
      $authorized_representative_relationship_to_member = $form_data_array['formDetails']['designeeRelationshipToMember'];
      if ($form_data_array['formDetails']['designeeRelationshipToMember'] == 'Other') {
        $authorized_representative_relationship_to_member = $form_data_array['formDetails']['designeeRelationshipToMember'] . " - " . $form_data_array['formDetails']['designeeOtherRelationshipToMember'];
      }
      $form['elements']['form_container']['read_only_container']['member_name']['#default_value'] = $form_data_array['formDetails']['firstName'] . " " . $form_data_array['formDetails']['lastName'];
      $form['elements']['form_container']['sign_container']['member_details_container']['member_signature_markup']['#markup'] = Markup::create(str_replace("{{member_sign_value}}", $form['elements']['form_container']['read_only_container']['member_name']['#default_value'], $form['elements']['form_container']['sign_container']['member_details_container']['member_signature_markup']['#markup']));
      $form['elements']['form_container']['sign_container']['member_details_container']['member_sign_flexbox']['member_name_markup']['#markup'] = Markup::create(str_replace("{{member_name_value}}", $form['elements']['form_container']['read_only_container']['member_name']['#default_value'], $form['elements']['form_container']['sign_container']['member_details_container']['member_sign_flexbox']['member_name_markup']['#markup']));
      $form['elements']['form_container']['sign_container']['member_details_container']['member_sign_flexbox']['member_date_markup']['#markup'] = Markup::create(str_replace("{{member_date_value}}", date("m/d/Y", strtotime(substr($form_data_array['formDetails']['memberSubmissionDate'], 0, 10))), $form['elements']['form_container']['sign_container']['member_details_container']['member_sign_flexbox']['member_date_markup']['#markup']));
      $form['elements']['form_container']['read_only_container']['member_address']['#default_value'] = str_repeat("*", strlen($form_data_array['formDetails']['addressStreet'] . " " . $form_data_array['formDetails']['addressUnitNumber'] . " " . $form_data_array['formDetails']['addressCity'] . " " . $form_data_array['formDetails']['addressState'] . " " . $form_data_array['formDetails']['addressZipCode']));
      $form['elements']['form_container']['read_only_container']['authorized_representative_name']['#default_value'] = $form_data_array['formDetails']['designeeFirstName'] . " " . $form_data_array['formDetails']['designeeLastName'];
      $form['elements']['form_container']['read_only_container']['authorized_representative_relationship_to_member']['#default_value'] = $authorized_representative_relationship_to_member;
      $designeeAddressUnitNumber = $form_data_array['formDetails']['designeeAddressUnitNumber'] ? $form_data_array['formDetails']['designeeAddressUnitNumber'] . ", " : " ";
      $form['elements']['form_container']['read_only_container']['authorized_representative_street_address']['#default_value'] = $form_data_array['formDetails']['designeeAddressStreet'] . ", " . $designeeAddressUnitNumber . "" . $form_data_array['formDetails']['designeeAddressCity'] . ", " . $form_data_array['formDetails']['designeeAddressState'] . " " . $form_data_array['formDetails']['designeeAddressZipCode'];
      $form['elements']['form_container']['read_only_container']['authorized_representative_phone_number']['#default_value'] = $form_data_array['formDetails']['designeePhone'];
      $all_my_information_to_discuss_or_disclose = [];
      if ($form_data_array['formDetails']['agreeToGrantPermissionToDiscussAllInformation']) {
        $all_my_information_to_discuss_or_disclose = ['all_my_information_to_discuss_or_disclose'];
      }
      $form['elements']['form_container']['read_only_container']['all_my_information_to_discuss_or_disclose']['#default_value'] = $all_my_information_to_discuss_or_disclose;
      $approve_the_disclosure = [];
      if ($form_data_array['formDetails']['agreeToDiscloseSensitiveInfoHIVOrAids']) {
        $approve_the_disclosure[] = 'hiv_and_aids';
      }
      if ($form_data_array['formDetails']['agreeToDiscloseSensitiveInfoMentalOrBehaviorialHealth']) {
        $approve_the_disclosure[] = 'mental_or_behavioral_health';
      }
      if ($form_data_array['formDetails']['agreeToDiscloseSensitiveInfoAlcoholAndSubstanceAbuse']) {
        $approve_the_disclosure[] = 'alcohol_and_substance';
      }
      $form['elements']['form_container']['read_only_container']['approve_the_disclosure']['#default_value'] = $approve_the_disclosure;
      $if_alcohol_and_substance_abuse = [];
      if ($form_data_array['formDetails']['agreeToDiscloseAlcoholReasonToAssistWithClaims']) {
        $if_alcohol_and_substance_abuse[] = 'to_assist_with_claim';
      }
      if ($form_data_array['formDetails']['agreeToDiscloseAlcoholReasonCoordinationWithCare']) {
        $if_alcohol_and_substance_abuse[] = 'coordination_of_care';
      }
      if ($form_data_array['formDetails']['agreeToDiscloseAlcoholReasonAssistWithTreament']) {
        $if_alcohol_and_substance_abuse[] = 'assist_with_treatment';
      }
      if ($form_data_array['formDetails']['agreeToDiscloseAlcoholReasonOther']) {
        $if_alcohol_and_substance_abuse[] = 'others';
      }
      $form['elements']['form_container']['read_only_container']['if_alcohol_and_substance_abuse']['#default_value'] = $if_alcohol_and_substance_abuse;
      $form['elements']['form_container']['read_only_container']['if_alcohol_and_substance_abuse_other']['#default_value'] = "";
      if (isset($form_data_array['formDetails']['agreeToDiscloseAlcoholReasonOtherDetails'])) {
        $form['elements']['form_container']['read_only_container']['if_alcohol_and_substance_abuse_other']['#default_value'] = $form_data_array['formDetails']['agreeToDiscloseAlcoholReasonOtherDetails'];
      }
      $form['elements']['form_container']['read_only_container']['documentum_id']['#default_value'] = $form_data_array['formDetails']['documentumId'];
      unset($form['elements']['expired_container']);
      unset($form['elements']['already_signed_container']);
      unset($form['elements']['token_invalid_container']);
    }
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
   *
   * @return mixed[]
   *   Boolean.
   */
  private function processFormDataSignFormGet(WebformSubmission $webform_submission, array $getAccessToken, LoggerChannel $logger, string $from_type, Client $requestStack, array $apiKeys, RequestStack $requestQuery): array {
    $form_data_api_response_decoded = [];
    if ($requestQuery->getCurrentRequest()->query->get('token') != NULL) {
      try {
        $headers['Authorization'] = 'Bearer ' . $getAccessToken['access_token'];
        $headers['X-ClientName'] = $apiKeys['Auth']['x_clientname'];
        $headers['X-ClientSessionId'] = $apiKeys['Auth']['X-ClientSessionId'];
        $headers['X-ClientVersion'] = $apiKeys['Auth']['x_clientversion'];
        $headers['Content-Type'] = 'application/json';
        if (isset($apiKeys['Auth']['X-Apienvironment'])) {
          $headers['X-Apienvironment'] = $apiKeys['Auth']['X-Apienvironment'];
        }
        $form_data_api_response = $requestStack->get(str_replace("{{id}}", $requestQuery->getCurrentRequest()->query->get('token'), $apiKeys['Auth']['form_data_api']), [
          'verify' => TRUE,
          'headers' => $headers,
        ])->getBody()->getContents();
        $logger->info("Form Data API Response Seminar- " . $form_data_api_response);
        $form_data_api_response_decoded = Json::decode($form_data_api_response);
      }

      catch (ClientException $e) {
        $logger->info("SignMAPD - RequestException type - " . get_class($e));
        $type = get_class($e);
        if ($type == 'GuzzleHttp\Exception\ClientException') {
          $logger->info("SignMAPD - RequestException Inside - " . $e->getResponse()->getBody());
          $form_data_api_response_decoded = Json::decode($e->getResponse()->getBody());
        }
        $logger->info("SignMAPD - RequestException - " . $e->__toString() . "-getBody-" . $e->getMessage());
      }
    }
    if ($form_data_api_response_decoded == NULL) {
      $form_data_api_response_decoded = [];
    }
    return $form_data_api_response_decoded;
  }

}

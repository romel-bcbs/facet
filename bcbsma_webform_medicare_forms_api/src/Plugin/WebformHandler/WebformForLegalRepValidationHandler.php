<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform Validation handler.
 *
 * @WebformHandler(
 *   id = "legalrep_doc_validation_handler",
 *   label = @Translation("LegalRep Doc Validation handler"),
 *   category = @Translation("Medicare Project"),
 *   description = @Translation("validate before the form submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class WebformForLegalRepValidationHandler extends WebformHandlerBase {
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory');
    $instance->webformTokenManager = $container->get('webform.token_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, $webform_submission) {
    $data = $webform_submission->getData();
    $legal_rep_file_upload_config = \Drupal::config('bcbsma_webform_medicare_forms_api.legalrep_upload_config');
    // Error - if file is not uploaded.
    if (empty($data['power_of_attorney_upload_files']) && $data['power_of_attorney_upload_files_section_added'] == 1) {
      $form_state->setErrorByName('power_of_attorney_file_upload_error', $legal_rep_file_upload_config->get('required_documentation'));
    }
    if (empty($data['health_care_proxy_upload_files']) && $data['health_care_proxy_upload_files_section_added'] == 1) {
      $form_state->setErrorByName('health_care_proxy_file_upload_error', $legal_rep_file_upload_config->get('required_documentation'));
    }
    if (empty($data['executor_of_estate_upload_files']) && $data['executor_of_estate_upload_files_section_added'] == 1) {
      $form_state->setErrorByName('executor_of_estate_file_upload_error', $legal_rep_file_upload_config->get('required_documentation'));
    }
    if (empty($data['court_appointed_guardian_upload_files']) && $data['court_appointed_guardian_upload_files_section_added'] == 1) {
      $form_state->setErrorByName('court_appointed_guardian_file_upload_error', $legal_rep_file_upload_config->get('required_documentation'));
    }
    if (empty($data['other_document_upload_files']) && $data['other_document_upload_files_section_added'] == 1) {
      $form_state->setErrorByName('other_document_file_upload_error', $legal_rep_file_upload_config->get('required_documentation'));
    }
    // Error - if file is uploaded has error.
    if ($data['power_of_attorney_upload_files_status'] == 1 && $data['power_of_attorney_upload_files_section_added'] == 1) {
      $form_state->setErrorByName('power_of_attorney_file_upload_error', $legal_rep_file_upload_config->get('has_malware'));
    }
    if ($data['health_care_proxy_upload_files_status'] == 1 && $data['health_care_proxy_upload_files_section_added'] == 1) {
      $form_state->setErrorByName('health_care_proxy_file_upload_error', $legal_rep_file_upload_config->get('has_malware'));
    }
    if ($data['executor_of_estate_upload_files_status'] == 1 && $data['executor_of_estate_upload_files_section_added'] == 1) {
      $form_state->setErrorByName('executor_of_estate_file_upload_error', $legal_rep_file_upload_config->get('has_malware'));
    }
    if ($data['court_appointed_guardian_upload_files_status'] == 1 && $data['court_appointed_guardian_upload_files_section_added'] == 1) {
      $form_state->setErrorByName('court_appointed_guardian_file_upload_error', $legal_rep_file_upload_config->get('has_malware'));
    }
    if ($data['other_document_upload_files_status'] == 1 && $data['other_document_upload_files_section_added'] == 1) {
      $form_state->setErrorByName('other_document_file_upload_error', $legal_rep_file_upload_config->get('has_malware'));
    }
    // Error - if a file upload is inprogress.
    if ($data['power_of_attorney_upload_progress_status'] == 1) {
      $form_state->setErrorByName('power_of_attorney_file_upload_error', $legal_rep_file_upload_config->get('has_malware'));
    }
    if ($data['health_care_proxy_upload_progress_status'] == 1) {
      $form_state->setErrorByName('health_care_proxy_file_upload_error', $legal_rep_file_upload_config->get('has_malware'));
    }
    if ($data['executor_of_estate_upload_progress_status'] == 1) {
      $form_state->setErrorByName('executor_of_estate_file_upload_error', $legal_rep_file_upload_config->get('has_malware'));
    }
    if ($data['court_appointed_guardian_upload_progress_status'] == 1) {
      $form_state->setErrorByName('court_appointed_guardian_file_upload_error', $legal_rep_file_upload_config->get('has_malware'));
    }
    if ($data['other_document_upload_progress_status'] == 1) {
      $form_state->setErrorByName('other_document_file_upload_error', $legal_rep_file_upload_config->get('has_malware'));
    }
    // Empty document.
    if (
      $data['power_of_attorney_upload_files_section_added'] == 0 &&
      $data['health_care_proxy_upload_files_section_added	'] == 0 &&
      $data['executor_of_estate_upload_files_section_added	'] == 0 &&
      $data['court_appointed_guardian_upload_files_section_added	'] == 0 &&
      $data['other_document_upload_files_section_added	'] == 0
    ) {
      $form_state->setErrorByName('controll_select_type_of_document', $legal_rep_file_upload_config->get('atleast_one_file'));
    }
  }

}

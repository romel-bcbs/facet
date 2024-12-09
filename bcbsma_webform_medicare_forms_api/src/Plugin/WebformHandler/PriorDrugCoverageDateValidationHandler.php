<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform Validation handler.
 *
 * @WebformHandler(
 *   id = "lep_telephonic_date_validate_handler",
 *   label = @Translation("LEP From and to Date handler"),
 *   category = @Translation("MedicareProject"),
 *   description = @Translation("validate before the form submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class PriorDrugCoverageDateValidationHandler extends WebformHandlerBase {
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
  public function defaultConfiguration() {
    return [
      'error_message_member_date' => '',
      'error_message_aor_date' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['form_api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Form API settings'),
    ];
    $form['form_api_settings']['error_message_aor_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AOR Error Message'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['error_message_aor_date'],
    ];
    $form['form_api_settings']['error_message_member_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Member Error Message'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['error_message_member_date'],
    ];
    $form['form_api_settings']['token_tree_link'] = $this->webformTokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['error_message_member_date'] = $form_state->getValue([
      'form_api_settings',
      'error_message_member_date',
    ]);
    $this->configuration['error_message_aor_date'] = $form_state->getValue([
      'form_api_settings',
      'error_message_aor_date',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, $webform_submission) {
    $data = $webform_submission->getData();
    if ($data['are_you_filling_as_member_or_caregiver'] == 'Member') {
      if ($data['did_you_have_creditable_prescription_drug_coverage_in_the_past'] == 'Yes') {
        if (strtotime($data['from']) > strtotime($data['to'])) {
          $form_state->setErrorByName('to', $this->configuration['error_message_member_date']);
        }
      }
    }
    if ($data['are_you_filling_as_member_or_caregiver'] == 'Authorized Representative') {
      if ($data['did_he_she_have_prior_creditable_sup_sup_prescription_drug_cover'] == 'Yes') {
        if (strtotime($data['authorized_representative_from']) > strtotime($data['authorized_representative_to'])) {
          $form_state->setErrorByName('authorized_representative_to', $this->configuration['error_message_aor_date']);
        }
      }
    }
  }

}

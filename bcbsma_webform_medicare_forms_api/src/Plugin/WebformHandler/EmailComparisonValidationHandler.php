<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform Validation handler.
 *
 * @WebformHandler(
 *   id = "email_comparison_validate_handler",
 *   label = @Translation("Custom - Email Comparison validation handler"),
 *   category = @Translation("MedicareProject - Custom Email Comparison validation"),
 *   description = @Translation("Email Comparison validation before the form submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class EmailComparisonValidationHandler extends WebformHandlerBase {
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
      'email' => '',
      'compare_email' => '',
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
    ];
    $elements_fields_list = [];
    foreach ($elements as $key => $value) {
      if (in_array($value['#type'], $element_type)) {
        $elements_fields_list[$key] = $key;
      }
    }
    $this->logger->get('DateValidationHandler-webform_handler')->notice('elements_fields_list : @elements_fields_list', [
      '@elements_fields_list' => implode('::', $elements_fields_list),
    ]);
    $form['form_api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Form API settings'),
    ];
    $form['form_api_settings']['email'] = [
      '#type' => 'select',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#options' => $elements_fields_list,
      '#default_value' => $this->configuration['email'],
      '#description' => $this->t('Select the list you want to send this submission.'),
    ];
    $form['form_api_settings']['compare_email'] = [
      '#type' => 'select',
      '#title' => $this->t('Compare Email to'),
      '#required' => TRUE,
      '#options' => $elements_fields_list,
      '#default_value' => $this->configuration['compare_email'],
      '#description' => $this->t('Select the list you want to send this submission.'),
    ];
    $form['form_api_settings']['email_error_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error Message'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['email_error_message'],
    ];
    $form['form_api_settings']['token_tree_link'] = $this->webformTokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['email'] = $form_state->getValue([
      'form_api_settings',
      'email',
    ]);
    $this->configuration['compare_email'] = $form_state->getValue([
      'form_api_settings',
      'compare_email',
    ]);
    $this->configuration['email_error_message'] = $form_state->getValue([
      'form_api_settings',
      'email_error_message',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, $webform_submission) {
    $data = $webform_submission->getData();
    if (isset($data[(string) $this->configuration['email']]) && isset($data[(string) $this->configuration['compare_email']])) {
      if (strtolower($data[(string) $this->configuration['email']]) != strtolower($data[(string) $this->configuration['compare_email']])) {
        $form_state->setErrorByName((string) $this->configuration['compare_email'], $this->configuration['email_error_message']);
      }
    }
  }

}

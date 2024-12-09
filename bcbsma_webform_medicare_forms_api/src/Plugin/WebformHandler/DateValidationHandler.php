<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform Validation handler.
 *
 * @WebformHandler(
 *   id = "date_validate_handler",
 *   label = @Translation("Custom - Date validation handler"),
 *   category = @Translation("MedicareProject - Custom date validation"),
 *   description = @Translation("Date validation before the form submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class DateValidationHandler extends WebformHandlerBase {
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
      'from_date' => '',
      'to_date' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $webform_ids = $this->getWebform();
    $elements = $webform_ids->getElementsDecodedAndFlattened();
    $element_type = [
      "date",
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
    $form['form_api_settings']['from_date'] = [
      '#type' => 'select',
      '#title' => $this->t('From Date'),
      '#required' => TRUE,
      '#options' => $elements_fields_list,
      '#default_value' => $this->configuration['from_date'],
      '#description' => $this->t('Select the list you want to send this submission to. Alternatively, you can also use the Other field for token replacement.'),
    ];
    $form['form_api_settings']['to_date'] = [
      '#type' => 'select',
      '#title' => $this->t('To Date'),
      '#required' => TRUE,
      '#options' => $elements_fields_list,
      '#default_value' => $this->configuration['to_date'],
      '#description' => $this->t('Select the list you want to send this submission to. Alternatively, you can also use the Other field for token replacement.'),
    ];
    $form['form_api_settings']['error_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error Message'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['error_message'],
    ];
    $form['form_api_settings']['token_tree_link'] = $this->webformTokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['from_date'] = $form_state->getValue([
      'form_api_settings',
      'from_date',
    ]);
    $this->configuration['to_date'] = $form_state->getValue([
      'form_api_settings',
      'to_date',
    ]);
    $this->configuration['error_message'] = $form_state->getValue([
      'form_api_settings',
      'error_message',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, $webform_submission) {
    $data = $webform_submission->getData();
    if (isset($data[(string) $this->configuration['to_date']])) {
      if (strtotime($data[(string) $this->configuration['from_date']]) > strtotime($data[(string) $this->configuration['to_date']])) {
        $form_state->setErrorByName((string) $this->configuration['to_date'], $this->configuration['error_message']);
      }
    }
  }

}

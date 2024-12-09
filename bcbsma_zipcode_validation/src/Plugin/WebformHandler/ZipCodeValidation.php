<?php

declare(strict_types=1);

namespace Drupal\bcbsma_zipcode_validation\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validate ZipCode with the user entered value.
 *
 * @WebformHandler(
 *   id = "zipcode_validation",
 *   label = @Translation("ZipCode Validation - Custom Send mail"),
 *   category = @Translation("ZipCode Validation"),
 *   description = @Translation("User entered ZipCode Validation with the ZipCode in DataBase."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class ZipCodeValidation extends WebformHandlerBase {

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
   * A viewsExecutable instance.
   *
   * {@inheritdoc}
   */

  protected $viewsExecutable;

  /**
   * A EntityTypeManagerInterface instance.
   *
   * {@inheritdoc}
   */

  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->webformTokenManager = $container->get('webform.token_manager');
    $instance->logger = $container->get('logger.factory');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->viewsExecutable = $container->get('views.executable');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'field_list' => '',
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
      "text_format",
    ];
    $elements_fields_list = [];
    foreach ($elements as $key => $value) {
      $this->logger->get('ZipCodeValidation')->error('Fields: @sid', [
        '@sid' => $value['#type'],
      ]);
      if (in_array($value['#type'], $element_type)) {
        $elements_fields_list[$key] = $key;
      }
    }
    $form['zipcode_validation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('ZipCode Validation'),
    ];
    $form['zipcode_validation']['field_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Fields List'),
      '#required' => TRUE,
      '#options' => $elements_fields_list,
      '#default_value' => $this->configuration['field_list'],
      '#description' => $this->t('Select the list you want to send this submission to. Alternatively, you can also use the Other field for token replacement.'),
    ];
    $form['zipcode_validation']['error_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error Message'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['error_message'],
    ];
    $form['zipcode_validation']['zipcode_view'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View to get the zip code'),
      '#description' => $this->t('Should contain and field field_zips.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['zipcode_view'],
    ];
    $form['zipcode_validation']['token_tree_link'] = $this->webformTokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['field_list'] = $form_state->getValue([
      'zipcode_validation',
      'field_list',
    ]);
    $this->configuration['error_message'] = $form_state->getValue([
      'zipcode_validation',
      'error_message',
    ]);
    $this->configuration['zipcode_view'] = $form_state->getValue([
      'zipcode_validation',
      'zipcode_view',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, $webform_submission) {
    $data = $webform_submission->getData();
    $this->logger->get('ZipCodeValidation')->error('c1');
    if (isset($data[(string) $this->configuration['field_list']])) {
      $view_data_for_form_field_prepopulate = $this->entityTypeManager->getStorage('view')->load((string) $this->configuration['zipcode_view']);
      $view = $this->viewsExecutable->get($view_data_for_form_field_prepopulate);
      $view->setDisplay('rest_export_1');
      $view->execute();
      $zip_array = [];
      foreach ($view->result as $row) {
        $zips = $view->field['field_zips']->getItems($row);
        foreach ($zips as $zip) {
          $zip_array[] = $zip['raw']->getValue()['value'];
        }
      }
      $raw_zip_user_entered = explode("-", $data[(string) $this->configuration['field_list']]);
      if (!in_array(trim($raw_zip_user_entered[0]), $zip_array)) {
        $raw_zip_user_entered = explode("-", $data[(string) $this->configuration['field_list']]);
        $this->logger->get('ZipCodeValidation')->error('User entered zip not found - ' . $data[(string) $this->configuration['field_list']]);
        $form_state->setErrorByName((string) $this->configuration['field_list'], $this->configuration['error_message']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    return $this->webform;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Change Webform Confirmation Message when Form API failed.
 *
 * @WebformHandler(
 *   id = "medicare_forms_api_failure_msg",
 *   label = @Translation("API Failure - Confirmation message update"),
 *   category = @Translation("MMSS Forms API"),
 *   description = @Translation("Switch Confirmation message - when the API failed"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class APIFailureModalPopupConfirmation extends WebformHandlerBase {

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
    $instance->webformTokenManager = $container->get('webform.token_manager');
    $instance->logger = $container->get('logger.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'error_message' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['form_api_msg'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Error Message'),
    ];
    $form['form_api_msg']['error_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API Failure - Error Message'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['error_message'],
    ];
    $form['form_api_msg']['token_tree_link'] = $this->webformTokenManager->buildTreeLink();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['error_message'] = $form_state->getValue([
      'form_api_msg',
      'error_message',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    if ($form_state->get('webform_confirmation_modal') && !$webform_submission->getElementData('data_send_successfully_to_form_api')) {
      $form['webform_confirmation_modal'] = [
        'title' => '',
        'content' => [
          '#theme' => 'webform_html_editor_markup',
          '#markup' => $this->configuration['error_message'],
          '#cache"' => [
            "max-age" => 0,
          ],
        ],
      ];
      $form_state->set('webform_confirmation_modal', $form['webform_confirmation_modal']);
      $this->logger->get('webform_confirmForm')->notice('Failure Message Loaded');
    }
  }

}

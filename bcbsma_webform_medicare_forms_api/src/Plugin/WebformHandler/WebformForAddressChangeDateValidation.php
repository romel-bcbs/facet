<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform Validation handler.
 *
 * @WebformHandler(
 *   id = "address_change_date_mmyyyy_validation_handler",
 *   label = @Translation("Address Change Date Validation handler (MM/YYYY)"),
 *   category = @Translation("Medicare Project"),
 *   description = @Translation("validate before the form submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class WebformForAddressChangeDateValidation extends WebformHandlerBase {
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
    if (!empty($data['when_did_you_move_to_your_current_address'])) {
      $explode_when_did_you_move = explode('/', $data['when_did_you_move_to_your_current_address']);
      if (!in_array($explode_when_did_you_move[1], range(1900, 2099)) || !checkdate($explode_when_did_you_move[0], 1, $explode_when_did_you_move[1])) {
        $form_state->setErrorByName('when_did_you_move_to_your_current_address', "Current Move Date must be a valid date.");
      }
    }
    if (!empty($data['when_did_you_begin_living_at_this_temporary_address'])) {
      $explode_when_did_you_move = explode('/', $data['when_did_you_begin_living_at_this_temporary_address']);
      if (!in_array($explode_when_did_you_move[1], range(1900, 2099)) || !checkdate($explode_when_did_you_move[0], 1, $explode_when_did_you_move[1])) {
        $form_state->setErrorByName('when_did_you_begin_living_at_this_temporary_address', "Temporary Address Living Date must be a valid date.");
      }
    }
    if (!empty($data['when_you_expect_to_return_to_your_permanent_address'])) {
      $explode_when_did_you_move = explode('/', $data['when_you_expect_to_return_to_your_permanent_address']);
      if (!in_array($explode_when_did_you_move[1], range(1900, 2099)) || !checkdate($explode_when_did_you_move[0], 1, $explode_when_did_you_move[1])) {
        $form_state->setErrorByName('when_you_expect_to_return_to_your_permanent_address', "Permanent Address Return Date must be a valid date.");
      }
    }
  }

}

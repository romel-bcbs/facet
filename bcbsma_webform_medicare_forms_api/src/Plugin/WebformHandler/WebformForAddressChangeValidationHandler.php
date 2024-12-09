<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform Validation handler.
 *
 * @WebformHandler(
 *   id = "address_change_validation_handler",
 *   label = @Translation("Address Change Validation handler"),
 *   category = @Translation("Medicare Project"),
 *   description = @Translation("validate before the form submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class WebformForAddressChangeValidationHandler extends WebformHandlerBase {
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
    // Error - PO Box validation. Below are the list covered
    // 'PO Box 123'.
    // 'P.O. Box 123'.
    // 'PO 123'.
    // 'Post Office Box 123'.
    // 'P.O 123'.
    // 'Box 123' - will be allowed - refer DOAIP-38713.
    // 'POB 123'.
    // 'Post 123'.
    // 'Post Box 123'.
    if (!empty($data['members_permanent_street_address'])) {
      if (preg_match("/^\s*((P(OST)?.?\s*(O(FF(ICE)?)?)?.?\s(B(IN|OX))?))/i", $data['members_permanent_street_address'])) {
        $form_state->setErrorByName('members_permanent_street_address', "Please enter a valid Street Address.");
      }
    }
    if (!empty($data['current_street_address'])) {
      if (preg_match("/^\s*((P(OST)?.?\s*(O(FF(ICE)?)?)?.?\s(B(IN|OX))?))/i", $data['current_street_address'])) {
        $form_state->setErrorByName('current_street_address', "Please enter a valid Street Address.");
      }
    }
    if (!empty($data['temporary_street_address'])) {
      if (preg_match("/^\s*((P(OST)?.?\s*(O(FF(ICE)?)?)?.?\s(B(IN|OX))?))/i", $data['temporary_street_address'])) {
        $form_state->setErrorByName('temporary_street_address', "Please enter a valid Street Address.");
      }
    }
    if (!empty($data['members_permanent_apartment_or_unit_number_optional'])) {
      if (preg_match("/^\s*((P(OST)?.?\s*(O(FF(ICE)?)?)?.?\s(B(IN|OX))?))/i", $data['members_permanent_apartment_or_unit_number_optional'])) {
        $form_state->setErrorByName('members_permanent_apartment_or_unit_number_optional', "Please enter a valid 	Apartment or Unit Number.");
      }
    }
    if (!empty($data['current_apartment_or_unit_number'])) {
      if (preg_match("/^\s*((P(OST)?.?\s*(O(FF(ICE)?)?)?.?\s(B(IN|OX))?))/i", $data['current_apartment_or_unit_number'])) {
        $form_state->setErrorByName('current_apartment_or_unit_number', "Please enter a valid Apartment or Unit Number.");
      }
    }
    if (!empty($data['temporary_apartment_or_unit_number'])) {
      if (preg_match("/^\s*((P(OST)?.?\s*(O(FF(ICE)?)?)?.?\s(B(IN|OX))?))/i", $data['temporary_apartment_or_unit_number'])) {
        $form_state->setErrorByName('temporary_apartment_or_unit_number', "Please enter a valid Apartment or Unit Number.");
      }
    }
  }

}

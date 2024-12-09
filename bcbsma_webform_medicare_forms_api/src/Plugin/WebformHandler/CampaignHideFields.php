<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform data handler.
 *
 * @WebformHandler(
 *   id = "campaign_hide_field",
 *   label = @Translation("Disable address fields on - Medicare Choices Campaign fields."),
 *   category = @Translation("MedicareProject - Custom Medicare Choices Campaign hide fields"),
 *   description = @Translation("Hide fields - while editing submission"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class CampaignHideFields extends WebformHandlerBase {
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
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    if (!\Drupal::config('bcbsma_webform_medicare_forms_api.general_config')->get('enable_address_field_in_campaign')) {
      unset($form['elements']['street_address']);
      unset($form['elements']['apartment_or_unit_number']);
      unset($form['elements']['city']);
      unset($form['elements']['flexbox']['state']);
      unset($form['elements']['flexbox']['zip_code']);
      unset($form['elements']['actions']['#states']['enabled']['.webform-submission-medicare-choices-campaign-node-126-add-form :input[name="street_address"]']);
      unset($form['elements']['actions']['#states']['enabled']['.webform-submission-medicare-choices-campaign-node-126-add-form :input[name="city"]']);
      unset($form['elements']['actions']['#states']['enabled']['.webform-submission-medicare-choices-campaign-node-126-add-form :input[name="state"]']);
      unset($form['elements']['actions']['#states']['enabled']['.webform-submission-medicare-choices-campaign-node-126-add-form :input[name="zip_code"]']);
    }
  }

}

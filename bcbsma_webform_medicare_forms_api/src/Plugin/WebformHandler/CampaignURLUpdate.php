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
 *   id = "campaign_url_update",
 *   label = @Translation("Custom - Default value - for Campaign URL"),
 *   category = @Translation("MedicareCampaign - Custom URL setup"),
 *   description = @Translation("Default value - for Campaign URL - When vurl or cid is missing in Current URL"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class CampaignURLUpdate extends WebformHandlerBase {
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
   * A EntityTypeManagerInterface instance.
   *
   * {@inheritdoc}
   */

  protected $entityTypeManager;

  /**
   * A RequestStack instance.
   *
   * {@inheritdoc}
   */

  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory');
    $instance->webformTokenManager = $container->get('webform.token_manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['form_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default URL'),
    ];
    $form['form_settings']['default_url'] = [
      '#title' => $this->t('Default URL'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['default_url'],
    ];
    $form['form_api_settings']['token_tree_link'] = $this->webformTokenManager->buildTreeLink();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['default_url'] = $form_state->getValue([
      'form_settings',
      'default_url',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $getCurrentRequestQueryParameters = $this->requestStack->getCurrentRequest()->query->all();
    if (!isset($getCurrentRequestQueryParameters['cid']) && !isset($getCurrentRequestQueryParameters['vurl'])) {
      $form['elements']['url']['#default_value'] = $this->configuration['default_url'];
    }
  }

}

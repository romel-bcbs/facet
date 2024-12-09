<?php

declare(strict_types=1);

namespace Drupal\bcbsma_sendgrid\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use SendGrid\Mail\Content;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\To;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends Webform submission data to SendGrid.
 *
 * @WebformHandler(
 *   id = "sendgrid_custom_mail",
 *   label = @Translation("SendGrid - Custom Send mail"),
 *   category = @Translation("SendGrid"),
 *   description = @Translation("Sends a form submission mail through SendGrid."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformSendGridHandler extends WebformHandlerBase {

  /**
   * A SendGrid client instance.
   *
   * {@inheritdoc}
   */
  protected $sendGrid;

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
   * A KeyRepository instance.
   *
   * {@inheritdoc}
   */
  protected $keyStore;

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
    $instance->sendGrid = $container->get('sendgrid_api.client');
    $instance->webformTokenManager = $container->get('webform.token_manager');
    $instance->logger = $container->get('logger.factory');
    $instance->keyStore = $container->get('key.repository');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_keys' => '',
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
      "email",
      "checkbox",
      "tel",
      "select",
      "radios",
      "text_format",
      "webform_term_select",
      "date",
    ];
    $elements_fields_list = [];
    foreach ($elements as $key => $value) {
      $this->logger->get('sendgrid_email_presave_fields')->error('Fields: @sid', [
        '@sid' => $value['#type'],
      ]);
      if (in_array($value['#type'], $element_type)) {
        $elements_fields_list[$key] = $key;
      }
    }
    $form['sendgridcustommail'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SendGrid E-mail settings'),
    ];
    $api_keys = array_keys($this->keyStore->getKeys());
    $api_keys_associated = array_combine($api_keys, $api_keys);
    $form['sendgridcustommail']['api_keys'] = [
      '#type' => 'select',
      '#title' => $this->t('API Keys list'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['api_keys'],
      '#options' => $api_keys_associated,
    ];
    $form['sendgridcustommail']['field_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Fields List to send'),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#options' => $elements_fields_list,
      '#default_value' => $this->configuration['field_list'],
      '#description' => $this->t('Select the list you want to send this submission to. Alternatively, you can also use the Other field for token replacement.'),
    ];

    $form['sendgridcustommail']['token_tree_link'] = $this->webformTokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['field_list'] = $form_state->getValue(['sendgridcustommail',
      'field_list',
    ]);
    $this->configuration['api_keys'] = $form_state->getValue(['sendgridcustommail',
      'api_keys',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    if (trim($webform_submission->getElementData('email'))) {
      $this->logger->get('sendgrid_email_presave')->notice('User has entered an Email');
      $webform_submission->setElementData('email_send_confirmation', "Yes");
    }
    if (!trim($webform_submission->getElementData('email'))) {
      $this->logger->get('sendgrid_email_presave')->notice('Medicare - User has not entered any Email');
      $webform_submission->setElementData('email_send_confirmation', 'No Email');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // Only for new submissions.
    if ($update) {
    }
    $apiKeys = $this->configuration['api_keys'];
    $apiKeysArray = $this->keyStore->getKey($apiKeys)->getKeyValues();
    $request_body = $this->formatEmailContent($webform_submission, $this->configuration['field_list'], $apiKeysArray['mail']['template_id'], $apiKeysArray['mail']['from_mail_name'], $apiKeysArray['mail']['mail_category'], $apiKeysArray['mail']['from_email']);
    if (!($request_body instanceof Mail)) {
      $this->logger->get('sendgrid_email_presave')->error('Invalid request_body to send');
      return;
    }
    $this->logger->get('sendgrid_email_presave')->notice('Webform ID - @sid ', [
      '@sid' => $webform_submission->id(),
    ]);
    try {
      $response = $this->sendGrid->client->mail()->send()->post($request_body);
      if ($response->statusCode() != '202') {
        $webform_submission->setElementData('email_send_confirmation', "No");
        $this->logger->get('sendgrid_email_presave')->error('Sendgrid Email Update Error Response Code: @sid', [
          '@sid' => $response->statusCode(),
        ]);
        $webform_submission->resave();
      }
    }

    catch (\Exception $e) {
      $this->logger->get('sendgrid_email_presave')->error('Caught exception: @sid : @error_message', [
        '@sid' => $e->__toString(),
        '@error_message' => $e->getMessage(),
      ]);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * Process the data based on MAPD/MEDEX and construct required JSON.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Webform submission data.
   * @param mixed[] $elements_fields_list
   *   Elements List.
   * @param string $template_id
   *   Template ID.
   * @param string $from_mail_name
   *   From Mail Name.
   * @param string $mail_category
   *   Email Category.
   * @param string $from_email
   *   From Email.
   */
  private function formatEmailContent(WebformSubmission $webform_submission, array $elements_fields_list, string $template_id, string $from_mail_name, string $mail_category, string $from_email): Mail {
    try {
      $from = new From($from_email, $from_mail_name);
      $subject = "Dynamic Subject";
      $to = new To(trim($webform_submission->getElementData('email')));
      $addDynamicTemplateDatas_array = [];
      // Note - Should not remove below line.
      $content = new Content("text/html", "Set HTML Content");
      $mail = new Mail($from, $to, $subject, $content);
      $mail->setTemplateId(trim($template_id));
      $mail->addCategory(trim($mail_category));
      foreach ($elements_fields_list as $val) {
        $addDynamicTemplateDatas_array[$val] = $webform_submission->getElementData($val);
        if ($val == 'seminar_type') {
          $addDynamicTemplateDatas_array[$val] = $this->entityTypeManager->getStorage('taxonomy_term')->load($webform_submission->getElementData($val))->name->value;
        }
        if ($val == 'seminar_start_date') {
          $addDynamicTemplateDatas_array[$val] = date("m/d/Y", strtotime($webform_submission->getElementData($val)));
        }
      }
      $mail->addDynamicTemplateDatas($addDynamicTemplateDatas_array);
      return $mail;
    }

    catch (\Exception $e) {
      $this->logger->get('sendgrid_email_presave')->error('Caught exception: @sid : @error_message', [
        '@sid' => print_r($e, TRUE),
        '@error_message' => $e->getMessage(),
      ]);
    }
    return NULL;
  }

}

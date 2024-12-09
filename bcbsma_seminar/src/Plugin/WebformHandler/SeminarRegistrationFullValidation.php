<?php

namespace Drupal\bcbsma_seminar\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform data handler.
 *
 * @WebformHandler(
 *   id = "seminar_registration_full_validation",
 *   label = @Translation("Custom - Validation whether registration is full"),
 *   category = @Translation("MedicareProject - Custom Validation"),
 *   description = @Translation("Redirect if registration is full before the form submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class SeminarRegistrationFullValidation extends WebformHandlerBase {
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
   * A viewsExecutable instance.
   *
   * {@inheritdoc}
   */

  protected $viewsExecutable;

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
    $instance->viewsExecutable = $container->get('views.executable');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $getCurrentRequestQueryParameters = $this->requestStack->getCurrentRequest()->query->all();
    if (isset($getCurrentRequestQueryParameters['sid'])) {
      $view_seminar_registrations_count = $this->entityTypeManager->getStorage('view')->load('seminar_registrations_count');
      $view = $this->viewsExecutable->get($view_seminar_registrations_count);
      $view->setDisplay('submission_count');
      $args = [$getCurrentRequestQueryParameters['sid']];
      $view->setArguments($args);
      $view->execute();
      $peopleCount = 0;
      foreach ($view->result as $row) {
        if ($row->_entity instanceof WebformSubmission) {
          $peopleCount += $row->_entity->getElementData('including_yourself_how_many_people_are_attending');
        }
      }
      $including_yourself_how_many_people_are_attending = $webform_submission->getElementData('including_yourself_how_many_people_are_attending');
      $total_registration = $peopleCount + $including_yourself_how_many_people_are_attending;
      $seminar_detail = $this->entityTypeManager->getStorage('node')->load($getCurrentRequestQueryParameters['sid']);
      $seminar_detail_capacity = (int) $seminar_detail->get('field_capacity')->value;
      $available_registration = $seminar_detail_capacity - $peopleCount;
      if ($total_registration > $seminar_detail_capacity) {
        $form_errors = $form_state->getErrors();
        // Clear the form errors.
        $form_state->clearErrors();
        unset($form_errors['including_yourself_how_many_people_are_attending']);
        // Now loop through and re-apply the remaining form error messages.
        foreach ($form_errors as $name => $error_message) {
          $form_state->setErrorByName($name, $error_message);
        }
        if ($available_registration == 1) {
          $form_state->setErrorByName('including_yourself_how_many_people_are_attending', $this->t('Only %s seat is available. Please register the attendee.', ['%s' => $available_registration]));
        }
        else {
          $form_state->setErrorByName('including_yourself_how_many_people_are_attending', $this->t('Only %s seats are available. Please re-select the number of attendees.', ['%s' => $available_registration]));
        }
      }
    }
  }

}

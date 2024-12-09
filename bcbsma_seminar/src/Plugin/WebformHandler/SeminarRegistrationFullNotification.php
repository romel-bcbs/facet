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
 *   id = "seminar_registration_full_notification",
 *   label = @Translation("Custom - Notification if registration is full"),
 *   category = @Translation("MedicareProject - Custom Notification"),
 *   description = @Translation("Redirect if registration is full before the form submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class SeminarRegistrationFullNotification extends WebformHandlerBase {
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
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
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
      $seminar_detail = $this->entityTypeManager->getStorage('node')->load($getCurrentRequestQueryParameters['sid']);
      $seminar_detail_capacity = (int) $seminar_detail->get('field_capacity')->value;
      $seminar_full_or_cancel_flag = (int) $seminar_detail->get('field_seminar_full_cancelled')->value;
      $find_displayNone_key = "";
      if ($peopleCount >= $seminar_detail_capacity || $seminar_full_or_cancel_flag == 1) {
        $find_displayNone_key = array_search('displayNone', $form['elements']['registration_full_container']['#attributes']['class']);
        if (isset($find_displayNone_key)) {
          unset($form['elements']['registration_full_container']['#attributes']['class'][$find_displayNone_key]);
        }
        $form['elements']['form_container']['#attributes']['class'][] = 'displayNone';
        $form['elements']['form_container']['#attributes']['class'][] = 'form-item';
      }
    }
  }

}

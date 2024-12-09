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
 *   id = "seminar_registration_options_update",
 *   label = @Translation("Custom - including_yourself_how_many_people_are_attending options update"),
 *   category = @Translation("MedicareProject - Custom options update"),
 *   description = @Translation("Update including_yourself_how_many_people_are_attending options based on the availability"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class SeminarRegistrationOptionsUpdate extends WebformHandlerBase {
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
    unset($form['elements']['form_container']['including_yourself_how_many_people_are_attending']['#pre_render']);
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
      $option = [];
      $seminar_detail = $this->entityTypeManager->getStorage('node')->load($getCurrentRequestQueryParameters['sid']);
      $seminar_detail_capacity = (int) $seminar_detail->get('field_capacity')->value;
      $seminar_detail_capacity_diff = $seminar_detail_capacity - $peopleCount;
      if ($seminar_detail_capacity_diff < 10 && $seminar_detail_capacity_diff > 0) {
        for ($i = 1; $i <= $seminar_detail_capacity_diff; $i++) {
          $option[$i] = $i;
        }
        $form['elements']['form_container']['including_yourself_how_many_people_are_attending']['#options'] = $option;
      }
    }
  }

}

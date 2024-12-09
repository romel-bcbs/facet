<?php

namespace Drupal\bcbsma_seminar\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform data handler.
 *
 * @WebformHandler(
 *   id = "seminar_registration_data_alter",
 *   label = @Translation("Custom - Seminar data handler"),
 *   category = @Translation("MedicareProject - Custom data"),
 *   description = @Translation("Custom data before the form submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class SeminarRegistrationDataAlterHandler extends WebformHandlerBase {
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
      $view_data_for_form_field_prepopulate = $this->entityTypeManager->getStorage('view')->load('seminar_details_registration_page_form_fillup');
      $view = $this->viewsExecutable->get($view_data_for_form_field_prepopulate);
      $args = [$getCurrentRequestQueryParameters['sid']];
      $view->setDisplay('page_1');
      $view->setArguments($args);
      $view->execute();
      $type = [];
      foreach ($view->result as $row) {
        $type['field_seminar_date'] = $view->field['field_seminar_date']->getItems($row);
        $type['field_seminar_date_1'] = $view->field['field_seminar_date_1']->getItems($row);
        $type['seminar_venue'] = $view->field['field_location']->getItems($row);
        $type['field_seminar_type'] = $view->field['field_seminar_type']->getItems($row);
        $type['title'] = $view->field['field_seminar_city_town_online']->getItems($row);
      }
      if ($view->result) {
        $form['elements']['form_container']['seminar_start_date']['#default_value'] = $type['field_seminar_date'][0]['rendered']['start_date']['#text'];
        $form['elements']['form_container']['seminar_time']['#default_value'] = $type['field_seminar_date_1'][0]['rendered']['start_date']['#text'] . "" . $type['field_seminar_date_1'][0]['rendered']['separator']['#plain_text'] . "" . $type['field_seminar_date_1'][0]['rendered']['end_date']['#text'];
        $form['elements']['form_container']['seminar_venue']['#default_value'] = strip_tags($type['seminar_venue'][0]['rendered']['#text']);
        $form['elements']['form_container']['seminar_type']['#default_value'] = $type['field_seminar_type'][0]['raw']->getValue()['target_id'];
        $form['elements']['form_container']['seminar_city_or_town']['#default_value'] = $type['title'][0]['rendered']['#plain_text'];
      }
    }
  }

}

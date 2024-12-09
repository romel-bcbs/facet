<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Plugin\WebformHierarchy;

use Drupal\webform_hierarchy\Plugin\WebformHierarchyBase;

/**
 * Plan Change Hierarchy class.
 *
 * @WebformHierarchy(
 *   id = "plan_change",
 *   label = @Translation("Plan Change Hierarchy"),
 *   description = @Translation("List of Plans."),
 *   widgets = {
 *     "current_plans" = @Translation("Current Plan"),
 *     "updated_plans" = @Translation("Updated Plan"),
 *   },
 * )
 */
class PlanChangeHierarchy extends WebformHierarchyBase {

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->logger = \Drupal::logger('PlanChangeHierarchy');
  }

  /**
   * {@inheritdoc}
   */
  public function items($widget_name, $parent_value, $langcode) {
    $plan_details = $this->getCitiesHelperFunction($this->entityTypeManager->getStorage('taxonomy_term')->loadTree('plan_details'));
    if ($widget_name === 'current_plans') {
      $result = array_udiff($plan_details, ['no change' => 'no change'], 'strcasecmp');
      return $result;
    }
    $result = array_diff($plan_details, [$parent_value => $parent_value]);
    $this->logger->notice('Parent Plan' . $parent_value);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  private function getCitiesHelperFunction($terms): array {
    $term_data = [];
    foreach ($terms as $term) {
      $term_data[$term->name] = $term->name;
    }
    return $term_data;
  }

}

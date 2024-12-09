<?php

namespace Drupal\webform_hierarchy\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_hierarchy' element.
 *
 * @WebformElement(
 *   id = "webform_hierarchy",
 *   label = @Translation("Webform hierarchical widget"),
 *   description = @Translation("Provides dependent selectors with hierarchical items."),
 *   category = @Translation("Composite elements"),
 *   deriver = "\Drupal\webform_hierarchy\Plugin\Derivative\WebformHierarchyDeriver",
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformHierarchy extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties();

    $composite_elements = $this->getCompositeElements();
    foreach (array_keys($composite_elements) as $composite_key) {
      if (isset($properties[$composite_key . '__type'])) {
        $properties[$composite_key . '__title_display'] = 'before';
        $properties[$composite_key . '__hide_empty'] = FALSE;
      }
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#ui_preview' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    $this->initializeHierarchyElement($element);
    parent::initialize($element);
  }

  /**
   * {@inheritdoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::finalize($element, $webform_submission);
    \Drupal::logger('list finalize')->info($element['#type']);

    [$element['#type'], $a] = explode(':', $element['#type']);
  }

  /**
   * {@inheritdoc}
   */
  public function initializeCompositeElements(array &$element) {
    $this->initializeHierarchyElement($element);
    parent::initializeCompositeElements($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements() {
    $element = [];
    $this->initializeHierarchyElement($element);
    $class = $this->getFormElementClassDefinition();

    return $class::getCompositeElements($element);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['webform_hierarchy'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hierarchy settings'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCompositeElementsTable(array $form, FormStateInterface $form_state) {
    $table = parent::buildCompositeElementsTable($form, $form_state);

    $composite_elements = $this->getCompositeElements();
    foreach (array_keys($composite_elements) as $composite_key) {
      if (isset($table[$composite_key]['settings']['data'][$composite_key . '__type']['#options']['select'])) {
        $states = $table[$composite_key]['settings']['data'][$composite_key . '__type']['#states'];
        unset($table[$composite_key]['settings']['data'][$composite_key . '__type']);

        $table[$composite_key]['settings']['data'][$composite_key . '__hide_empty'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Hide if parent is not selected'),
          '#description' => $this->t('Hide this field if selection list is empty and parent item is not selected.'),
          '#return_value' => TRUE,
          '#states' => $states,
        ];
      }
    }

    return $table;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return ['lines' => implode(', ', parent::formatTextItemValue($element, $webform_submission, $options))];
  }

  /**
   * Initialize form element with hierarchy definition.
   *
   * {@inheritdoc}
   *   A composite element.
   */
  protected function initializeHierarchyElement(array &$element) {
    if (isset($element['#hierarchy_id'])) {
      return;
    }
    $widgets = [];
    if (isset($this->pluginDefinition['widgets'])) {
      $widgets = $this->pluginDefinition['widgets'];
    }
    [, $hierarchy_id] = explode(':', $this->getPluginId(), 2);
    \Drupal::logger('list initializeHierarchyElement')->info($this->getPluginId());
    $element['#widgets'] = $widgets;
    $element['#hierarchy_id'] = $hierarchy_id;
  }

}

<?php

namespace Drupal\webform_hierarchy\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'webform_hierarchy' element.
 *
 * @FormElement("webform_hierarchy")
 */
class WebformHierarchy extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo() + [
      '#theme' => 'webform_hierarchy',
      '#hierarchy_id' => '',
      '#widgets' => [],
    ];

    $info['#process'][] = [get_class($this), 'processWebformHierarchy'];

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];

    if (!empty($element['#widgets'])) {
      foreach ($element['#widgets'] as $widget_name => $widget_title) {
        $elements[$widget_name] = [
          '#type' => 'select',
          '#title' => $widget_title,
          '#options' => [],
        ];
      }
    }

    return $elements;
  }

  /**
   * Processes a hierarchy webform element.
   *
   * {@inheritdoc}
   */
  public static function processWebformHierarchy(&$element, FormStateInterface $form_state, &$complete_form) {
    if (empty($element['#widgets']) || !empty($element['#ui_preview'])) {
      return $element;
    }

    if (isset($element['#hierarchy_processed'])) {
      static::setHierarchyItems($element, $form_state);
      return $element;
    }

    $element['#hierarchy_processed'] = TRUE;
    $wrapper_id = Html::getUniqueId('webform-hierarchy-wrapper');

    $element['#prefix'] = '<div id="' . $wrapper_id . '">';
    $element['#suffix'] = '</div>';

    $last_widget = count($element['#widgets']) - 1;

    foreach (array_keys($element['#widgets']) as $i => $widget_name) {
      if ($i < $last_widget) {
        $element[$widget_name]['#ajax'] = [
          'wrapper' => $wrapper_id,
          'callback' => [WebformHierarchy::class,
            'hierarchyItemChangeAjaxHandler',
          ],
          'event' => 'change',
          'progress' => 'none',
        ];
      }
    }

    static::setHierarchyItems($element, $form_state);

    return $element;
  }

  /**
   * Fills widget options of hierarchy widgets.
   *
   * {@inheritdoc}
   */
  public static function setHierarchyItems(&$element, FormStateInterface $form_state) {
    if (empty($element['#widgets'])) {
      return;
    }

    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    /** @var \Drupal\webform_hierarchy\Plugin\WebformHierarchyManagerInterface $hierarchy_manager */
    $hierarchy_manager = \Drupal::service('plugin.manager.webform_hierarchy');

    try {
      /** @var \Drupal\webform_hierarchy\Plugin\WebformHierarchyInterface $hierarchy */
      $hierarchy = $hierarchy_manager->createInstance($element['#hierarchy_id']);
    }
    catch (\Exception $e) {
      return;
    }

    $widget_names = array_keys($element['#widgets']);

    foreach ($widget_names as $i => $widget_name) {
      $widget = &$element[$widget_name];

      if ($i === 0) {
        $widget['#options'] = $hierarchy->items($widget_name, NULL, $langcode);
        continue;
      }

      $parent_widget_name = $widget_names[$i - 1];
      $parent_widget_value = $value[$parent_widget_name] ?? $value[$parent_widget_name] ?? NULL;
      $widget['#options'] = $hierarchy->items($widget_name, $parent_widget_value, $langcode);
      $prev_parent_widget_value = $widget['#parent_value'] ?? $widget['#parent_value'] ?? NULL;
      $current_value = $widget['#value'] ?? $widget['#value'] ?? NULL;
      if (is_null($current_value)) {
        $current_value = $widget['#default_value'] ?? $widget['#default_value'] ?? NULL;
      }

      if (is_null($parent_widget_value) || !isset($widget['#options'][$current_value]) || (!is_null($prev_parent_widget_value) && ($parent_widget_value !== $prev_parent_widget_value))) {
        $widget['#value'] = '';
        unset($value[$widget_name]);
      }

      if (!empty($widget['#hide_empty']) && (is_null($parent_widget_value) || ($parent_widget_value === '')) && empty($widget['#options']) && (empty($widget['#required']) || !empty($element[$parent_widget_name]['#required']))) {
        $widget['#access'] = FALSE;
      }

      $widget['#parent_value'] = $parent_widget_value;
    }
  }

  /**
   * AJAX handler for change event for hierarchy widgets.
   *
   * {@inheritdoc}
   */
  public static function hierarchyItemChangeAjaxHandler(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($trigger['#array_parents'], 0, -1));

    return $element;
  }

}

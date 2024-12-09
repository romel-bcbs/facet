<?php

namespace Drupal\webform_hierarchy\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for webform hierarchy plugins.
 */
abstract class WebformHierarchyBase extends PluginBase implements WebformHierarchyInterface {

  /**
   * {@inheritdoc}
   */
  public function items($widget_name, $parent_value, $langcode) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return TRUE;
  }

}

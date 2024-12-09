<?php

namespace Drupal\webform_hierarchy\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a webform hierarchy annotation object.
 *
 * @Annotation
 */
class WebformHierarchy extends Plugin {

  /**
   * The plugin ID.
   *
   * {@inheritdoc}
   */
  public $id;

  /**
   * The label of the hierarchy.
   *
   * {@inheritdoc}
   *
   * @ingroup plugin_translatable
   */
  public $label = '';

  /**
   * The description of the hierarchy.
   *
   * {@inheritdoc}
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * An list of widgets.
   *
   * {@inheritdoc}
   */
  public $widgets = [];

}

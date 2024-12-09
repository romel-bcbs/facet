<?php

namespace Drupal\medicare_options\Plugin\CustomElement;

use Drupal\cohesion_elements\CustomElementPluginBase;

/**
 * Generic HTML element plugin for DX8.
 *
 * @package Drupal\cohesion\Plugin\CustomElement
 *
 * @CustomElement(
 *   id = "brightcove_element",
 *   label = @Translation("Brightcove Element")
 * )
 */
class BrightcoveElement extends CustomElementPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return [
      'video_id' => [
        'htmlClass' => 'col-xs-12',
        'title' => 'Video ID',
        'type' => 'textfield',
        'required' => TRUE,
        'validationMessage' => 'This field is required.',
      ],
      'player_id' => [
        'htmlClass' => 'col-xs-12',
        'title' => 'Player ID',
        'type' => 'textfield',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($element_settings, $element_markup, $element_class, $element_context = []) {
    // Render the element.
    return [
      '#theme' => 'brightcove_element',
      '#template' => 'brightcove-element',
      '#elementSettings' => $element_settings,
      '#elementMarkup' => $element_markup,
      '#elementContext' => $element_context,
      '#elementClass' => $element_class,
      '#attached' => [
        'library' => [
          'medicare_options/brightcove',
        ],
      ],
    ];
  }

}

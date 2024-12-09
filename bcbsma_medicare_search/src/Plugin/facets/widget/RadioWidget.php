<?php

namespace Drupal\bcbsma_medicare_search\Plugin\facets\widget;

use Drupal\facets\Plugin\facets\widget\LinksWidget;

/**
 * The checkbox / radios widget.
 *
 * @FacetsWidget(
 *   id = "radiobox",
 *   label = @Translation("List of radioboxes"),
 *   description = @Translation("A configurable widget that shows a list of radioboxes"),
 * )
 */
class RadioWidget extends LinksWidget {

  /**
   * {@inheritdoc}
   */
  protected function appendWidgetLibrary(array &$build) {
    $build['#attributes']['class'][] = 'js-facets-radiobox-links';
    $build['#attached']['library'][] = 'bcbsma_medicare_search/drupal.facets.radiobox-widget';
  }

}

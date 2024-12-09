<?php

/**
 * @file
 * Contains bcbsma_plans.views.
 *
 * @codingStandardsIgnoreFile
 */

 use Drupal\field\Entity\FieldConfig;

 /**
  * Implement hook_views_data().
  */
  function bcbsma_plans_views_data(){
    $data = [];
    $data['views']['medicare_sort'] = [
      'title' => t('Medicare Sort Filter'),
      'filter' => [
        'title' => t('Medicare Sort'),
        'help' => t('Filte plans based on sort options'),
        'id' => 'bcbsma_medicare_sort_filter',
      ]
    ];
    return $data;
  }

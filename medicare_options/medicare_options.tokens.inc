<?php

/**
 * @file
 * Builds placeholder replacement tokens system-wide data.
 *
 * This file handles tokens for the global 'Medicare' sites.
 *
 * @codingStandardsIgnoreFile
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function medicare_options_token_info(): array {
  $types['medicareSiteConfig'] = [
    'name' => t("Medicare Configurations"),
    'description' => t("Tokens for site-wide Medicare Configurations."),
  ];

  $medicareSiteConfig['yearType'] = [
    'name' => t("Dual Year"),
    'description' => t("Dual Year Configurations."),
  ];

  $medicareSiteConfig['enrollment'] = [
    'name' => t("Enrollment Status"),
    'description' => t("Define if the Enrollment is closed"),
  ];

  $medicareSiteConfig['drugService'] = [
    'name' => t("Drug Service"),
    'description' => t("Drug Service Aviability."),
  ];
  $medicareSiteConfig['doctorService'] = [
    'name' => t("Drug Service"),
    'description' => t("Doctor Service Aviability."),
  ];

  $types['medicarePlans'] = [
    'name' => t("Medicare Plans"),
    'description' => t("Tokens for Medicare Plans."),
  ];

  $medicarePlans['planAvailable'] = [
    'name' => t("Plan Availability"),
    'description' => t("Plan Availability based on plan and zipcode"),
  ];

  $medicarePlans['planPremium'] = [
    'name' => t("Plan Premium"),
    'description' => t("Plan Premium."),
  ];

  $medicarePlans['planEnrollNow'] = [
    'name' => t("Plan Enroll Now"),
    'description' => t("Provide Enroll Now Href Tag based on plan and zipcode"),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'medicareSiteConfig' => $medicareSiteConfig,
      'medicarePlans' => $medicarePlans,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function medicare_options_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  $currentPath = \Drupal::service('path.current');
  $pathAlias = \Drupal::service('path_alias.manager');
  $entityManager = \Drupal::service('entity_type.manager')->getStorage('node');
  $config = \Drupal::config('medicare_options.site_config_form');
  if ($type == 'medicareSiteConfig') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'yearType':
          $bubbleable_metadata->addCacheableDependency($config);
          $dual = $config->get('year_type');
          $replacements[$original] = $dual;
          break;
        case 'enrollment':
          $uri =  $currentPath->getPath();
          $path = $pathAlias->getPathByAlias($uri);
          $medicareData = \Drupal::service('bcbsma_plans.service');
          $info = null;
          if (preg_match('/node\/(\d+)/', $path, $matches)) {
            $node = $entityManager->load($matches[1]);
            if (isset($node) && $node->hasField('field_plan')) {
              $planId = $node->field_plan->getValue()[0]['target_id'];
              $planInfo = $medicareData->getPlanInfo([$planId]);
              foreach ($planInfo as $plan) {
                $info = $plan;
              }
            }
          }
          $configMonth = $config->get('month');
          $month = (empty($configMonth) && $configMonth == NULL) ? idate('m') : (int) $configMonth;
          if ($info['year'] == date("Y") && ($month == 12)){
            $replacements[$original] = 'closed';
          }
          else {
            $replacements[$original] = 'open';
          }
          break;
        case 'drugService':
          $bubbleable_metadata->addCacheableDependency($config);
          $dual = $config->get('drug_search_service') ? 'available' : 'disabled';
          $replacements[$original] = $dual;
          break;
        case 'doctorService':
          $bubbleable_metadata->addCacheableDependency($config);
          $dual = $config->get('doctor_search_service') ? 'available' : 'disabled';
          $replacements[$original] = $dual;
          break;
      }
    }
  }
  if ($type == 'medicarePlans') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'planPremium':
          $replacements[$original] = 'XX.XX';
          break;
        case 'planEnrollNow':
          try {
            $info = [];
            $planId = '';
            $session = \Drupal::request()->getSession();
            $zipCode = !is_null($session->get("zipCode")) ? $session->get("zipCode") : '';
            $uri =  $currentPath->getPath();
            $path = $pathAlias->getPathByAlias($uri);
            $medicareData = \Drupal::service('bcbsma_plans.service');
            if (preg_match('/node\/(\d+)/', $path, $matches)) {
              $node = $entityManager->load($matches[1]);
              if (isset($node) && $node->hasField('field_plan')) {
                $planId = $node->field_plan->getValue()[0]['target_id'];
                $planInfo = $medicareData->getPlanInfo([$planId]);
                foreach ($planInfo as $plan) {
                  $info = $plan;
                }
              }
            }
            if (strpos($info['type'], 'Prescription') !== FALSE) {
              $replacements[$original] = \Drupal::configFactory()->getEditable('medicare_options.site_config_form')->get('prescription_url');
            }
            else {
              $replacements[$original] = "/medicare/enroll-now?planName=". preg_replace('/[^A-Za-z0-9 ]/', '', $info['name']) . '&planType=' . $info['type'] . '&planYear=' . $info['year'] . '&zipCode=' . $zipCode;
            }
          }
          catch (Exception $exception) {
            \Drupal::logger('Enroll Now TOken Exception')->info($exception->getMessage());
          }
          break;
        case 'planAvailable':
          try {
            $planId = '';
            $session = \Drupal::request()->getSession();
            $county = !is_null($session->get("county")) ? $session->get("county") : '';
            $uri =  $currentPath->getPath();
            $path = $pathAlias->getPathByAlias($uri);
            $medicareData = \Drupal::service('bcbsma_plans.service');
            if (preg_match('/node\/(\d+)/', $path, $matches)) {
              $node = $entityManager->load($matches[1]);
              if (isset($node) && $node->hasField('field_plan')) {
                $planId = $node->field_plan->getValue()[0]['target_id'];
              }
            }
            $premium = $medicareData->getPremium($county, $planId);
            $replacements[$original] = ($premium || $premium == '0') ? 'true' : 'false';
          }
          catch (Exception $exception) {
            \Drupal::logger('Plan Availability Token Exception')->info($exception->getMessage());
          }
          break;
      }
    }
  }

  return $replacements;
}

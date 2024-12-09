<?php

/**
 * @file
 * Contains bcbsma_tokens.tokens.inc.
 */

/**
 * Implements hook_token_info().
 *
 * @inheritdoc
 */
function bcbsma_tokens_token_info() {
  $types['medicare-keys'] = [
    'name' => t("BCBS Custom - Config Keys"),
    'description' => t("Secure config keys."),
  ];
  $keys_to_tokenize = array_map('trim', explode(PHP_EOL, \Drupal::config('bcbsma_webform_medicare_forms_api.general_config')->get('keys_to_tokenize')));
  foreach ($keys_to_tokenize as $val) {
    $keys_to_tokenize_array = explode(':', $val);
    \Drupal::logger('bcbsma_tokens_token_info')->notice("keys_to_tokenize_array 0 - " . $keys_to_tokenize_array[0] . "keys_to_tokenize_array 1 - " . $keys_to_tokenize_array[1]);
    $custom[$keys_to_tokenize_array[0]] = [
      'name' => $keys_to_tokenize_array[1],
    ];
  }
  return [
    'types' => $types,
    'tokens' => [
      'medicare-keys' => $custom,
    ],
  ];
}

/**
 * Implements hook_tokens().
 *
 * @inheritdoc
 */
function bcbsma_tokens_tokens(
  $type,
  $tokens,
  array $data,
  array $options
) {

  $replacements = [];
  if ($type == 'medicare-keys') {
    foreach ($tokens as $name => $original) {
      $replacements[$original] = Drupal::service('key.repository')->getKey($name)->getKeyValue();
    }
  }
  return $replacements;
}

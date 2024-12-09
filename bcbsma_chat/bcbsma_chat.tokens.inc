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
function bcbsma_chat_token_info(): array {
  $types['livechat'] = [
    'name' => t("Live Chat"),
    'description' => t("Tokens for site-wide Live chat Configurations."),
  ];

  $liveChat['servicename'] = [
    'name' => t("Chat Name"),
    'description' => t("Live chat Service Name"),
  ];
  $liveChat['ameliaiframeurl'] = [
    'name' => t("Amelia chat Iframe src"),
    'description' => t("Live chat Service src"),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'livechat' => $liveChat,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function bcbsma_chat_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'livechat') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'servicename':
          $config = \Drupal::config('bcbsma_chat.settings');
          $bubbleable_metadata->addCacheableDependency($config);
          $serviceName = $config->get('chat_type');
          $replacements[$original] = $serviceName;
          break;
        case 'ameliaiframeurl':
          $config = \Drupal::config('bcbsma_chat.settings');
          $bubbleable_metadata->addCacheableDependency($config);
          $iframeUrl = $config->get('iframe_url');
          $currentUrl = \Drupal::request()->getUri();
          if(!is_null($iframeUrl) && $iframeUrl != ''){
            $url = array_map('trim', explode(PHP_EOL, $iframeUrl));
            $replacements[$original] =  $url[0] . '&url=' . $currentUrl;
          } else {
            $replacements[$original] = '';
          }

          break;
      }
    }
  }
  return $replacements;
}

<?php

namespace Drupal\bcbsma_plans\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Used to integrate Web component.
 *
 * @Block(
 *   id = "myblue_login",
 *   admin_label = @Translation("Myblue Login"),
 *   category = @Translation("Custom"),
 * )
 */
class MyblueLoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'html_tag',
      // Selector of the your app root component from the Angular app.
      '#tag' => 'myblue-login',

      '#attached' => [
        'library' => [
          // To load the library only with this block.
          'bcbsma_plans/myblue_login_component',
        ],
      ],
    ];
  }

}

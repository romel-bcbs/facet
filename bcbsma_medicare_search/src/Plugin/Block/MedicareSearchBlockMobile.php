<?php

namespace Drupal\bcbsma_medicare_search\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Used to integrate Web component.
 *
 * @Block(
 *   id = "medicare_search_mobile",
 *   admin_label = @Translation("Medicare Global search Mobile"),
 *   category = @Translation("Custom"),
 * )
 */
class MedicareSearchBlockMobile extends BlockBase implements ContainerFactoryPluginInterface {

  const FORM_PATH = '\Drupal\bcbsma_medicare_search\Form\MedicareSearchForm';

  /**
   * Form builder will be used via Dependency Injection.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  // phpcs:ignore
  protected $formBuilder;

  /**
   * Constructs a \Drupal\medicare_options\Plugin\Block object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Build the content of the block.
    $build = [];
    $build['form'] = $this->formBuilder->getForm(self::FORM_PATH);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // Define the access permissions for the block.
    // You can add your custom access logic here.
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Define cache tags to invalidate the block.
    // When related entities are updated.
    return Cache::mergeTags(parent::getCacheTags(), ['medicare_options_custom_sort']);
  }

}

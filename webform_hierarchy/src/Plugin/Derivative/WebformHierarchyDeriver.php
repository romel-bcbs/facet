<?php

namespace Drupal\webform_hierarchy\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\webform_hierarchy\Plugin\WebformHierarchyManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides webform hierarchy element derivatives.
 *
 * @see \Drupal\webform_hierarchy\Plugin\WebformElement\WebformHierarchy
 */
class WebformHierarchyDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The hierarchy information manager.
   *
   * {@inheritdoc}
   */
  protected $hierarchyManager;

  /**
   * Constructs new WebformHierarchyDeriver.
   *
   * @param \Drupal\webform_hierarchy\Plugin\WebformHierarchyManagerInterface $hierarchy_manager
   *   The hierarchy information manager.
   */
  public function __construct(WebformHierarchyManagerInterface $hierarchy_manager) {
    $this->hierarchyManager = $hierarchy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.webform_hierarchy')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $definitions = $this->hierarchyManager->getDefinitions();

    foreach ($definitions as $id => $definition) {
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['label'] = $definition['label'];
      $this->derivatives[$id]['description'] = $definition['description'];
      $this->derivatives[$id]['widgets'] = $definition['widgets'];
    }

    return $this->derivatives;
  }

}

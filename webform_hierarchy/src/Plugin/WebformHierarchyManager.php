<?php

namespace Drupal\webform_hierarchy\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the webform hierarchy plugin manager.
 */
class WebformHierarchyManager extends DefaultPluginManager implements WebformHierarchyManagerInterface {

  /**
   * Loaded plugin instances.
   *
   * {@inheritdoc}
   */
  protected $hierarchyPlugins = [];

  /**
   * Constructs a new WebformHierarchyManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/WebformHierarchy', $namespaces, $module_handler, 'Drupal\webform_hierarchy\Plugin\WebformHierarchyInterface', 'Drupal\webform_hierarchy\Annotation\WebformHierarchy');

    $this->alterInfo('webform_hierarchy_info');
    $this->setCacheBackend($cache_backend, 'webform_hierarchy_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    // Sort definitions by plugin label.
    uasort($definitions, function ($a, $b) {
      return strnatcasecmp($a['label'], $b['label']);
    });

    parent::alterDefinitions($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if (!isset($this->hierarchyPlugins[$plugin_id])) {
      $this->hierarchyPlugins[$plugin_id] = parent::createInstance($plugin_id, $configuration);
    }

    return $this->hierarchyPlugins[$plugin_id];
  }

}

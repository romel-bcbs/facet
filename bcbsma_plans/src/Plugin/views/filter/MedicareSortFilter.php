<?php

namespace Drupal\bcbsma_plans\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Medicare Sort filter.
 *
 * @ingroup views_medicare_sort_handler
 *
 * @ViewsFilter("bcbsma_medicare_sort_filter")
 */
class MedicareSortFilter extends ManyToOne {
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  // phpcs:ignore
  protected $connection;

  /**
   * The entityType Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  // phpcs:ignore
  protected $entityTypeManager;

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  // phpcs:ignore
  protected string $currentDisplay;

  /**
   * Constructs a Drupal\views\Plugin\views\display\DisplayPluginBase object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityType Manager.
   */
  public function __construct(array $configuration,
    string $plugin_id,
    array $plugin_definition,
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = $this->t('Sort Medicare Plans');
    $this->definition['options callback'] = [$this, 'getSortList'];
    $this->currentDisplay = $view->current_display;
  }

  /**
   * Helper function that generates the options.
   *
   * @return mixed[]
   *   An array of Course Features.
   */
  public function getSortList(): array {
    $list = [];
    $terms = [];
    $query = $this->entityTypeManager->getStorage('taxonomy_term');
    switch ($this->currentDisplay) {
      case 'medicare_advantage_plans':
        $queryResult = $query->getQuery()
          ->accessCheck(FALSE)
          ->condition('vid', "sort")
          ->sort('weight', 'ASC')
          ->execute();
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($queryResult);
        break;

      default:
        $queryResult = $query->getQuery()
          ->accessCheck(FALSE)
          ->condition('vid', "sort")
          ->range(0, 2)
          ->sort('weight', 'ASC')
          ->execute();
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($queryResult);
        break;

    }
    foreach ($terms as $term) {
      if ($term->isPublished()) {
        $termName = $term->getName();
        $machineName = str_replace(' ', '_', $termName);
        $list[$machineName] = $term->get('description')->value;
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    switch ($this->currentDisplay) {
      case 'medicare_advantage_plans':
        if (!empty($this->value)) {
          $label = str_replace('_', ' ', $this->value[0]);
          $terms = $this->entityTypeManager->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $label]);
          $term = reset($terms);
          if ($term != NULL) {
            $dbTable = $term->field_table_name->getValue()[0]['value'] ?? '';
            $sort = $term->field_sort->getValue()[0]['value'] ?? '';
            if (method_exists($this->query, 'sort') && !empty($dbTable)) {
              $this->query->sort($dbTable, $sort);
            }
          }
        }
        break;
    }
  }

}

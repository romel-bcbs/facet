<?php

namespace Drupal\medicare_options\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a custom form.
 */
class CustomSortForm extends FormBase {

  /**
   * A entity query.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  // phpcs:ignore
  protected $entityTypeManager;

  /**
   * A Current Path Stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  // phpcs:ignore
  protected $pathStack;

  /**
   * Constructor for Drupal\medicare_options\Form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A database connection.
   * @param \Drupal\Core\Path\CurrentPathStack $pathStack
   *   A Current Path Stack.
   */
  public function __construct(EntityTypeManager $entityTypeManager, CurrentPathStack $pathStack) {
    $this->entityTypeManager = $entityTypeManager;
    $this->pathStack = $pathStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity_type.manager'),
      $container->get('path.current'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'medicare_options_custom_sort_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $uri = $this->pathStack->getPath();
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('custom_sort');
    $sortItems['all'] = 'All';
    foreach ($terms as $term) {
      $termObject = $this->entityTypeManager->getStorage('taxonomy_term')->load($term->tid);
      if (isset($termObject)) {
        $termUrl = $termObject->get('field_url')->getValue()[0]['value'] ?? '';
      }
      else {
        $termUrl = '';
      }
      if ($term->{'parents'}[0] === "0" && $termUrl === $uri) {
        $termsList = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('custom_sort', $term->tid, 1, TRUE);
        foreach ($termsList as $termList) {
          $sortItems[$termList->get('field_key')->getValue()[0]['value']] = $termList->label();
        }
      }
    }

    $form['custom_sort'] = [
      '#type' => 'select2',
      '#title' => $this->t('Sort by'),
      '#name' => 'custom_sort',
      '#options' => $sortItems,
      '#default_value' => 'all',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    $this->messenger()->addMessage($this->t('Hello @name, your form has been submitted!', ['@name' => $name]));
  }

}

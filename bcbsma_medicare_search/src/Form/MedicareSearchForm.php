<?php

namespace Drupal\bcbsma_medicare_search\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Implements a custom form.
 */
class MedicareSearchForm extends FormBase {

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
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  // phpcs:ignore
  protected $configFactory;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  // phpcs:ignore
  protected $request;

  /**
   * Constructor for Drupal\bcbsma_medicare_search\Form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A database connection.
   * @param \Drupal\Core\Path\CurrentPathStack $pathStack
   *   A Current Path Stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   config Factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request params instance.
   */
  public function __construct(EntityTypeManager $entityTypeManager, CurrentPathStack $pathStack, ConfigFactoryInterface $configFactory, RequestStack $request) {
    $this->entityTypeManager = $entityTypeManager;
    $this->pathStack = $pathStack;
    $this->configFactory = $configFactory->get('search.site_config_form');
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity_type.manager'),
      $container->get('path.current'),
      $container->get('config.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'medicare_global_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get Request Query Perams.
    $query_params = $this->request->getCurrentRequest()->query->all();

    if (!empty($query_params) && isset($query_params['keyword'])) {
      $keyword = $query_params['keyword'];
    }
    else {
      $keyword = "";
    }

    $form['global_search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#default_value' => $keyword,
      '#attributes' => [
        'placeholder' => $this->t('Search'),
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
    $form['#cache'] = ['max-age' => 0];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('global_search');
    $url = Url::fromUri($this->configFactory->get('search_results_page'), ['query' => ['keyword' => $name]]);
    $localRedirect = new LocalRedirectResponse($url->toString());
    $form_state->setResponse($localRedirect);
  }

}

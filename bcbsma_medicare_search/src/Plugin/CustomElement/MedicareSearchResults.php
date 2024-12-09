<?php

namespace Drupal\bcbsma_medicare_search\Plugin\CustomElement;

use Drupal\cohesion_elements\CustomElementPluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Pager\PagerManager;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Generic HTML element plugin for DX8.
 *
 * @package Drupal\cohesion\Plugin\CustomElement
 *
 * @CustomElement(
 *   id = "medicare_search_results",
 *   label = @Translation("Medicare Search Results")
 * )
 */
class MedicareSearchResults extends CustomElementPluginBase {

  const BLOCKNAME = "medicareglobalsearchpage";

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  // phpcs:ignore
  protected $logger;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  // phpcs:ignore
  protected $request;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  // phpcs:ignore
  protected $configFactory;

  /**
   * Guzzle\Client instance.
   *
   * @var \GuzzleHttp\Client
   */
  // phpcs:ignore
  protected $httpClient;

  /**
   * PagerManager.
   *
   * @var \Drupal\Core\Pager\PagerManager
   */
  // phpcs:ignore
  protected $pager;

  /**
   * A entity query.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  // phpcs:ignore
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\cohesion\Plugin\CustomElement object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger Factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request params instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   config Factory.
   * @param \GuzzleHttp\Client $httpClient
   *   Http Client.
   * @param \Drupal\Core\Pager\PagerManager $pager
   *   Pager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A database connection.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, LoggerChannelFactoryInterface $logger, RequestStack $request, ConfigFactoryInterface $configFactory, Client $httpClient, PagerManager $pager, EntityTypeManager $entityTypeManager) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->logger = $logger;
    $this->request = $request;
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
    $this->pager = $pager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('pager.manager'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return [
      'search_results' => [
        'type' => 'markup',
        'markup' => 'Search Results',
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function render($element_settings, $element_markup, $element_class, $element_context = []) {
    // Get all the parameters from the request.
    $config = $this->configFactory->get('search.site_config_form');
    $url_paramters = $this->request->getCurrentRequest()->query->all();
    $keyword = (!empty($url_paramters) && isset($url_paramters['keyword'])) ? $url_paramters['keyword'] : '';
    $site_a_endpoint = $config->get('site_a_endpoint');
    $site_b_endpoint = $config->get('site_b_endpoint');

    if (!is_null($site_a_endpoint) && !is_null($site_b_endpoint)) {
      $site_a = $this->apiResponse($site_a_endpoint . $keyword) ?? [];
      $site_b = $this->apiResponse($site_b_endpoint . $keyword) ?? [];
    }
    else {
      $site_a = [];
      $site_b = [];
    }

    $results = array_merge($site_a, $site_b);

    $limit = $config->getOriginal('results_limit');
    // Create a new pager object.
    $pager = $this->pager->createPager(count($results), $limit);
    $currentPage = $pager->getCurrentPage();
    $form = NULL;
    $block = $this->entityTypeManager->getStorage('block')->load(self::BLOCKNAME);
    if (!is_null($block)) {
      $form = $this->entityTypeManager->getViewBuilder('block')->view($block);
    }

    // Use currentPage to limit items for the page.
    $items = array_slice($results, $currentPage * $limit, $limit);
    $build['list'] = [];
    $build['list']['#items'][] = [
      '#wrapper_attributes' => [
        'class' => ['item'],
      ],
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];

    $render = [
      '#theme' => 'search_results',
      '#template' => 'search_results',
      '#results' => $items,
      '#elementSettings' => $element_settings,
      '#elementMarkup' => $element_markup,
      '#elementContext' => $element_context,
      '#elementClass' => $element_class,
      "#keyword" => $keyword,
      '#form' => $form,
      '#cache' => [
        'max-age' => 0,
      ],
      '#elementPager' => $build,
    ];

    return $render;
  }

  /**
   * API Response.
   *
   * @param string $api_url
   *   URL.
   *
   * @return mixed[]
   *   Return Response Data.
   */
  public function apiResponse(string $api_url): array {
    $body_decoded = [];
    try {
      $method = 'GET';
      $authorization = $this->configFactory->get('search.site_config_form')->get('authorization');
      if (!is_null($authorization)) {
        $options = ['headers' => ['Authorization' => $authorization]];
      }
      else {
        $options = [];
      }
      $response = $this->httpClient->request($method, $api_url, $options);
      $code = $response->getStatusCode();
      $body = $response->getBody()->getContents();
      if ($code == 200) {
        $body_decoded = json_decode($body, TRUE);
      }
      return $body_decoded;
    }
    catch (\Exception $e) {
      $this->logger->get('Medicare_search_results')->error($e->getMessage());
    }
    return $body_decoded;
  }

}

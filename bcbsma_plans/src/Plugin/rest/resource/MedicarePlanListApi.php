<?php

// phpcs:ignore
namespace Drupal\bcbsma_plans\Plugin\rest\resource;

use Drupal\bcbsma_plans\Service\MedicarePlanData;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Plans List.
 *
 * @RestResource(
 *   id = "medicare_plans",
 *   label = @Translation("Medicare Plans"),
 *   uri_paths = {
 *     "canonical" = "/v1/medicareplanslist"
 *   }
 * )
 */
class MedicarePlanListApi extends ResourceBase {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  // phpcs:ignore
  protected $request;

  /**
   * A Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  // phpcs:ignore
  protected $database;

  /**
   * A Database Connection.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  // phpcs:ignore
  protected $entityTypeManager;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  // phpcs:ignore
  protected $configFactory;

  /**
   * Medicare Plan Service.
   *
   * @var \Drupal\bcbsma_plans\Service\MedicarePlanData
   */
  // phpcs:ignore
  protected $medicarePlans;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param mixed[] $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request params instance.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A Database Connection.
   * @param \Drupal\Core\Database\Connection $database
   *   A Database Connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   config Factory.
   * @param \Drupal\bcbsma_plans\Service\MedicarePlanData $medicarePlans
   *   Medicare Plan Service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, array $serializer_formats, LoggerInterface $logger, RequestStack $request, EntityTypeManager $entityTypeManager, Connection $database, ConfigFactoryInterface $configFactory, MedicarePlanData $medicarePlans) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->request = $request;
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->configFactory = $configFactory;
    $this->medicarePlans = $medicarePlans;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('bcbsma_plans.service')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   Throws exception expected.
   */
  // phpcs:ignore
  public function get(): \Drupal\rest\ModifiedResourceResponse {
    $data = [];
    $yearId = '';

    // Get Request Query Perams.
    $query_params = $this->request->getCurrentRequest()->query->all();

    $year = (!empty($query_params) && !is_null($query_params['year'])) ? $query_params['year'] : '';

    // Validating if year is empty if emty we will return all active plans
    // If not we will return specific year plans.
    if ($year != "") {
      // Retriving year Term ID.
      $term_ids = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $year]);
      // Looping through Term id array to access term object.
      foreach ($term_ids as $termId) {
        $yearId = $termId->id();
      }
      // Get Plan info By Year.
      $plansInfo = $this->getplanInfoByYear($yearId);
    }
    else {
      // Get all active plans.
      $plansInfo = $this->getplanInfo();
    }
    // Looping through all plans and setting $data Array.
    foreach ($plansInfo as $key => $plan) {
      $data[$key]['year'] = $plan['year'];
      $data[$key]['id'] = $plan['id'];
      $data[$key]['planTypeId'] = $plan['planTypeId'];
      $data[$key]['planType'] = $plan['type'];
      $data[$key]['planName'] = $plan['name'];
      $data[$key]['formularyId'] = $plan['formularyId'];
      $data[$key]['planDetailsUrl'] = $this->getPlanDetailPageUrl($plan['id']);
    }

    // Returing Response Object with Data Values.
    return new ModifiedResourceResponse(array_values($data), 200);
  }

  /**
   * Responds to GET requests.
   *
   * @param string $yearId
   *   Year id as sting.
   *
   * @return mixed[]
   *   Plan Info.
   */
  public function getplanInfoByYear(string $yearId): array {
    $dataId = [];
    // Entity query for Node.
    $query = $this->entityTypeManager->getStorage('node');
    // Get Node Entities.
    $queryResult = $query->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'medicare_plans')
      ->condition('field_plan_year', $yearId)
      ->condition('status', 1)
      ->execute();
    // Looping through results.
    foreach ($queryResult as $planId) {
      // Setting DataId variable.
      $dataId[] = $planId;
    }
    // Getting info from medicare plan service.
    $plansInfo = $this->medicarePlans->getPlanInfo($dataId);

    // Returning plans info.
    return $plansInfo;
  }

  /**
   * Responds to GET requests.
   *
   * @return mixed[]
   *   Plan Info.
   */
  public function getplanInfo(): array {
    $dataId = [];
    // Entity query for Node.
    $query = $this->entityTypeManager->getStorage('node');
    // Get Node Entities.
    $queryResult = $query->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'medicare_plans')
      ->condition('status', 1)
      ->execute();
    // Looping through results.
    foreach ($queryResult as $planId) {
      // Setting DataId variable.
      $dataId[] = $planId;
    }
    // Getting info from medicare plan service.
    $plansInfo = $this->medicarePlans->getPlanInfo($dataId);
    // Returning plans info.
    return $plansInfo;
  }

  /**
   * Responds to GET plan detail pageurl.
   *
   * @param string $planId
   *   planID.
   *
   * @return string
   *   Plan details url.
   */
  public function getPlanDetailPageUrl(string $planId): string {
    $url = '';
    $query = $this->entityTypeManager->getStorage('node');
    $queryResult = $query->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'medicare_page')
      ->condition('field_plan', $planId)
      ->condition('status', 1)
      ->execute();
    foreach ($queryResult as $result) {
      if (!empty($result)) {
        $entity = $this->entityTypeManager->getStorage('node')->load($result);
        if (!is_null($entity)) {
          $url = $entity->toUrl()->toString();
        }
      }
    }
    return $url;
  }

}

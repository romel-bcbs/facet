<?php

namespace Drupal\bcbsma_plans\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A Class for GetSessionController.
 *
 * @package Drupal\bcbsma_plans\Controller
 */
class GetSessionController extends ControllerBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  // phpcs:ignore
  protected $requestStack;

  /**
   * A entity query.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  // phpcs:ignore
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\bcbsma_plans\Controller object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request Stack.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A database connection.
   */
  public function __construct(RequestStack $requestStack, EntityTypeManager $entityTypeManager) {
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Set Session.
   *
   * @param string $county
   *   CountyName.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response.
   */
  public function setSession(string $county = NULL): JsonResponse {
    $params = $this->requestStack->getCurrentRequest()->query->all();
    // Get Request Query Perams.
    $session = $this->requestStack->getSession();
    if (!empty($params) && isset($params['zipCode'])) {
      $session->set('zipCode', $params['zipCode']);
    }
    // Entity query to get node ID of county Node.
    $countyQuery = $this->entityTypeManager->getStorage('node');
    $countyCount = $countyQuery->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'counties')
      ->condition('title', $county)
      ->condition('status', 1)
      ->count()->execute();
    if ($countyCount > 0) {
      $session->set('county', $county);
    }
    else {
      return new JsonResponse(['county' => 'not found', 'staus' => 404], 404);
    }

    return new JsonResponse(['county' => $session->get("county")], 200);
  }

  /**
   * Clear Session.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response.
   */
  public function clearSession(): JsonResponse {
    $session = $this->requestStack->getSession();
    if (!is_null($session)) {
      $session->remove('zipCode');
      $session->remove('county');
      return new JsonResponse(['status' => TRUE], 200);
    }
    return new JsonResponse(['status' => FALSE], 200);
  }

}

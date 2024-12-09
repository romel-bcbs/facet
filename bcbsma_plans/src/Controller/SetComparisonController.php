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
class SetComparisonController extends ControllerBase {

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
   * Set comparison Session.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response.
   */
  public function setComparisonSession(): JsonResponse {
    $data = $this->requestStack->getCurrentRequest()->getContent();
    // Get Request Query Perams.
    $session = $this->requestStack->getSession();
    if (!empty($data) && !is_null($data)) {
      $comparisonIds = json_decode($data, TRUE);
      $session->set('comparisonIds', $comparisonIds);
      return new JsonResponse($session->get("comparisonIds"), 200);
    }
    return new JsonResponse(['messgae' => "Bad Request"], 400);
  }

}

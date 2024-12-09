<?php

namespace Drupal\bcbsma_plans\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A Class for GetRedirectUrl.
 *
 * @package Drupal\bcbsma_plans\Controller
 */
class GetRedirectUrl extends ControllerBase {

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
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  // phpcs:ignore
  protected $logger;

  /**
   * Constructs a Drupal\bcbsma_plans\Controller object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request Stack.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger Factory.
   */
  public function __construct(RequestStack $requestStack,
    EntityTypeManager $entityTypeManager,
    LoggerChannelFactoryInterface $logger) {
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Get Rediect URL from Session.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response.
   */
  public function getRedirectUrl(): JsonResponse {
    $rediectUrl = "";
    try {
      $session = $this->requestStack->getSession();
      $rediectUrl = !is_null($session->get("redirectUrl")) ? $session->get("redirectUrl") : '';
    }
    catch (\Exception $exception) {
      $this->logger->get('get redirect url')->info($exception->getMessage());
    }
    return new JsonResponse(['redirectUrl' => $rediectUrl]);
  }

}

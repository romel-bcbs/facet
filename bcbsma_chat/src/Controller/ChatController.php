<?php

namespace Drupal\bcbsma_chat\Controller;

use Drupal\bcbsma_chat\Service\ChatEnableLogic;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for ChatController.
 */
class ChatController extends ControllerBase {

  /**
   * Custom Service to check CHat enable logic.
   *
   * @var \Drupal\bcbsma_chat\Service\ChatEnableLogic
   */
  // phpcs:ignore
  protected $bcbsmaChat;

  /**
   * Constructs a ChatController object.
   *
   * @param \Drupal\bcbsma_chat\Service\ChatEnableLogic $bcbsmaChat
   *   Request Stack.
   */
  public function __construct(ChatEnableLogic $bcbsmaChat) {
    $this->bcbsmaChat = $bcbsmaChat;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bcbsma_chat.chatLogic')
    );
  }

  /**
   * AjaxReqChatAvailablity function.
   */
  public function ajaxReqChatAvailablity(): JsonResponse {
    $responseStatus = [];
    $chatBlockVisibility = $this->bcbsmaChat->chatLogic();
    if ($chatBlockVisibility) {
      $responseStatus['chat_status'] = 'success';
    }
    else {
      $responseStatus['chat_status'] = 'fail';
    }
    return new JsonResponse($responseStatus);
  }

}

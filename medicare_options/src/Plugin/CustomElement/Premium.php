<?php

namespace Drupal\medicare_options\Plugin\CustomElement;

use Drupal\bcbsma_plans\Service\MedicarePlanData;
use Drupal\cohesion_elements\CustomElementPluginBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Generic HTML element plugin for DX8.
 *
 * @package Drupal\cohesion\Plugin\CustomElement
 *
 * @CustomElement(
 *   id = "price",
 *   label = @Translation("Premium")
 * )
 */
class Premium extends CustomElementPluginBase {

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
   * Medicare Plans Data.
   *
   * @var \Drupal\bcbsma_plans\Service\MedicarePlanData
   */
  // phpcs:ignore
  protected $planData;

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
   * @param \Drupal\bcbsma_plans\Service\MedicarePlanData $planData
   *   Medicare Plans Data.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, LoggerChannelFactoryInterface $logger, RequestStack $request, MedicarePlanData $planData) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->logger = $logger;
    $this->request = $request;
    $this->planData = $planData;
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
      $container->get('bcbsma_plans.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {

    return [
      'plan_id' => [
        'htmlClass' => 'col-xs-6',
        'title' => 'PlanID',
        'type' => 'textfield',
        'required' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($element_settings, $element_markup, $element_class, $element_context = []) {

    try {
      // Calling Medicare Service.
      $medicareData = $this->planData;
      // Calling session Service.
      $session = $this->request->getSession();
      // Checking county is set or not.
      $county = !is_null($session->get("county")) ? $session->get("county") : '';
      // Retreving plan info.
      $planInfo = $medicareData->getPlanInfo([$element_settings['plan_id']]);
      $info = [];
      foreach ($planInfo as $plan) {
        $info = $plan;
      }
      // Getting premium for plans based on Plan Type.
      if (strpos($info['type'], 'Prescription') !== FALSE || strpos($info['type'], 'Dental') !== FALSE || strpos($info['type'], 'Travel') !== FALSE) {
        $premium = $info['premium'];
      }
      else {
        $premium = $medicareData->getPremium($county, $element_settings['plan_id']);
      }

      // Checking if premium is empty.
      $premium = (!empty($premium) || $premium === '0') ? $premium : 'X.XX';
      if ($premium !== 'X.XX') {
        $premium = number_format((float) $premium, 2, '.', '');
      }
      // Setting premium to element Settings.
      $element_settings['premium'] = explode('.', $premium);
    }
    catch (\Exception $exception) {
      $this->logger->get('Exception From Premium Element')->info($exception->getMessage());
    }
    // Return rendered array.
    return [
      '#theme' => 'premium',
      '#template' => 'premium',
      '#elementSettings' => $element_settings,
      '#elementMarkup' => $element_markup,
      '#elementContext' => $element_context,
      '#elementClass' => $element_class,
      '#cache' => [
        'max-age' => 0,
      ],
    ];

  }

}

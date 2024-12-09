<?php

namespace Drupal\medicare_options\Plugin\CustomElement;

use Drupal\bcbsma_plans\Service\MedicarePlanData;
use Drupal\cohesion_elements\CustomElementPluginBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Generic HTML element plugin for DX8.
 *
 * @package Drupal\cohesion\Plugin\CustomElement
 *
 * @CustomElement(
 *   id = "benefits",
 *   label = @Translation("Benefits")
 * )
 */
class Benefits extends CustomElementPluginBase {

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
   * @param \Drupal\bcbsma_plans\Service\MedicarePlanData $planData
   *   Medicare Plans Data.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A database connection.
   */
  public function __construct(array $configuration,
    string $plugin_id,
    array $plugin_definition,
    LoggerChannelFactoryInterface $logger,
    RequestStack $request,
    MedicarePlanData $planData,
    EntityTypeManager $entityTypeManager) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->logger = $logger;
    $this->request = $request;
    $this->planData = $planData;
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
      $container->get('bcbsma_plans.service'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  private $benefitsCategories;

  /**
   * {@inheritdoc}
   */
  public function getFields() {

    return [
      'PlanID' => [
        'htmlClass' => 'col-xs-2',
        'title' => 'PlanID',
        'type' => 'textfield',
        'required' => FALSE,
      ],
      'highlights' => [
        'htmlClass' => 'ssa-grid-col-6',
        'type' => 'checkbox',
        'title' => 'Highlights?',
        'notitle' => FALSE,
        'defaultValue' => TRUE,
      ],
      'category' => [
        'htmlClass' => 'col-xs-10',
        'title' => 'Category',
        'type' => 'textfield',
        'required' => FALSE,
      ],
      'displayas' => [
        'htmlClass' => 'col-xs-10',
        'title' => 'Disppay as',
        'type' => 'textfield',
        'required' => FALSE,
        'defaultValue' => 'table',
      ],
      'viewMore' => [
        'htmlClass' => 'ssa-grid-col-6',
        'type' => 'checkbox',
        'title' => 'View More Details',
        'notitle' => FALSE,
        'defaultValue' => TRUE,
      ],
      'rows' => [
        'htmlClass' => 'col-xs-2',
        'title' => 'How many Rows',
        'type' => 'textfield',
        'required' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($element_settings, $element_markup, $element_class, $element_context = []) {
    $category = $element_settings['category'];
    $medicareData = $this->planData;
    $networkDisplays = new \stdClass();
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'network_type')
      ->execute();
    if (!empty($terms)) {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($terms);
      foreach ($terms as $term) {
        $networkDisplay = new \stdClass();
        $networkDisplay->{'name'} = $term->label();
        $networkDisplay->{'display'} = $term->getDescription();
        $networkDisplays->{$networkDisplay->{'name'}} = $networkDisplay;
      }
    }
    $planInfo = [];
    if ($element_settings['highlights']) {
      $element_settings['header'] = 'Cost & Copay';

      foreach ($medicareData->getPlanInfo([$element_settings['PlanID']]) as $plan) {
        $planInfo = $plan;
      }

      $session = $this->request->getSession();
      $county = !is_null($session->get("county")) ? $session->get("county") : '';
      if (strpos($planInfo['type'], 'Prescription') !== FALSE || strpos($planInfo['type'], 'Dental') !== FALSE) {
        $premium = $planInfo['premium'];
      }
      else {
        $premium = $medicareData->getPremium($county, $element_settings['PlanID']);
      }
      $premiumAmount = (!empty($premium) || $premium === '0') ? $premium : 'X.XX';

      if ($premiumAmount !== 'X.XX') {
        $premiumAmount = number_format((float) $premium, 2, '.', '');
        $premiumAmount = $premiumAmount == '0.00' ? '0' : $premiumAmount;
      }

      $highlights = [];
      $highlightObject = new \stdClass();
      $labelObject = new \stdClass();
      $labelObject->{'display'} = 'Premium Per Month';
      $labelObject->{'tooltip'} = $planInfo['helpText']['Premium Per Month'];
      $highlightObject->{'label'} = $labelObject;
      $highlightObject->{'text'} = '$' . $premiumAmount;
      $highlights[] = $highlightObject;
      foreach ($planInfo['highlights'] as $highlight) {
        $highlightObject = new \stdClass();
        $labelObject = new \stdClass();
        $highlight = explode(': ', $highlight);
        $labelObject->{'display'} = $highlight[0];
        $labelObject->{'tooltip'} = $planInfo['helpText'][$highlight[0]];
        $highlightObject->{'label'} = $labelObject;
        $highlightObject->{'text'} = $highlight[1] == '$0.00' ? '$0' : $highlight[1];
        $highlights[] = $highlightObject;
      }

      $highlightObject = new \stdClass();
      $labelObject = new \stdClass();
      $labelObject->{'display'} = 'Benefit Highlights';
      $highlightObject->{'label'} = $labelObject;
      $highlightObject->{'text'} = $planInfo['topBenefits'];
      $highlights[] = $highlightObject;
      $element_settings['benefits'] = $highlights;
    }
    else {

      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
        ->accessCheck(FALSE)
        ->condition('vid', 'medicare_benefit_category')
        ->execute();
      if (!empty($terms)) {
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($terms);
        foreach ($terms as $term) {
          if ($term->label() == $category) {
            $element_settings['display'] = $term->get('field_display')->value ?? $term->label();
            $element_settings['header'] = $term->get('field_details_table_header')->value ?? $term->label();
            $blockMachineName = $term->get('field_additional_block')->value ?? '';
            if (!empty($blockMachineName)) {
              $block = $this->entityTypeManager->getStorage('block')->load($blockMachineName);
              if (!is_null($block)) {
                $element_settings['additionalBlock'] = $this->entityTypeManager->getViewBuilder('block')->view($block);
              }
            }
          }
        }
      }

      foreach ($medicareData->getPlanDetails([$element_settings['PlanID']]) as $plan) {
        $planInfo = $plan;
      }
      $planBenefits = array_filter($planInfo['planBenefits'], function ($benefit) use ($category) {
        return $benefit['benefitCategory'] == $category;
      });

      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
        ->accessCheck(FALSE)
        ->condition('vid', 'medicare_benefit_name')
        ->sort('weight', 'ASC')
        ->execute();
      $benefitsLables = [];
      if (!empty($terms)) {
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($terms);
        foreach ($terms as $term) {
          $benefitNameObject = new \stdClass();
          $benefitNameObject->{'display'} = $term->get('field_display')->value
            ? $term->get('field_display')->value
            : $term->label();
          $benefitNameObject->{'tooltip'} = $term->get('field_tooltip')->value;
          $benefitNameObject->{'tags'} = $term->get('field_tags')->value;
          $benefitsLables[$term->label()] = $benefitNameObject;
        }
      }

      $benefitList = [];
      foreach ($planBenefits as $key => $value) {
        $benefitObject = new \stdClass();
        $benefitObject->{'key'} = $key;
        $benefitObject->{'label'} = $benefitsLables[$key];
        $benefitTextObject = new \stdClass();

        $aggregateType = $value['aggregateType'];

        $headers = [];
        foreach ($value['benefitText'] as $benefitText) {
          foreach ($benefitText['networkType'] as $networkType) {
            if (!isset($benefitTextObject->{$networkType})) {
              if (!in_array($networkType, $headers)) {
                $headers[] = $networkType;
              }
              $benefitTextObject->{$networkType} = [];
            }
            $benefitTextObject->{$networkType}[] = $benefitText;
          }
        }

        $benefitTexts = [];
        foreach ($networkDisplays as $networkDisplay) {
          foreach ($benefitTextObject as $key => $value) {
            if ($networkDisplay->{'name'} == $key) {
              $benefitTexts[] = $value;
            }
          }
        }

        $containerStart = '';
        $containerEnd = '';
        $itemStart = '';
        $itemEnd = '';
        switch ($aggregateType) {
          case 'li':
            $containerStart = "<ul>";
            $containerEnd = "</ul>";
            $itemStart = '<li>';
            $itemEnd = '</li>';
            break;

          case 'p':
            $itemStart = '<p>';
            $itemEnd = '</p>';
            break;
        }

        $benefitTextsObject = new \stdClass();
        foreach ($benefitTexts as $key => $value) {
          foreach ($value as $key => $value) {
            foreach ($value['networkType'] as $networkTypes) {
              if (!isset($benefitTextsObject->{$networkTypes})) {
                $networkDisplay = $networkDisplays->{$networkTypes}->{'display'}
                ? '<div class="network-name">' . $networkDisplays->{$networkTypes}->{'display'}
                : '<div>';
                $benefitTextsObject->{$networkTypes} = [$networkDisplay,
                  $containerStart,
                ];
              }

              $benefitNetwork = $itemStart . $value['prefix'];
              switch ($value['unitType']) {
                case '$':
                  $benefitNetwork .= ' $' . $value['unit'] . ' ' . $value['suffix'];
                  break;

                case '%':
                  $benefitNetwork .= ' ' . $value['unit'] . '% ' . $value['suffix'];
                  break;

                default:
                  $benefitNetwork .= $value['suffix'];
                  break;
              }
              $benefitNetwork .= $itemEnd;
              $benefitTextsObject->{$networkTypes}[] = $benefitNetwork;
            }
          }
        }

        $benefitObject->{'text'} = '';
        foreach ($benefitTextsObject as $benefitTexts) {
          $benefitObject->{'text'} .= (implode(' ', $benefitTexts) . $containerEnd) . '</div>';
        }

        $benefitList[] = $benefitObject;
      }

      $benefitListSorted = [];
      foreach ($benefitsLables as $key => $value) {
        foreach ($benefitList as $benefit) {
          if ($benefit->{'key'} === $key) {
            $benefitListSorted[] = $benefit;
          }
        }
      }

      $element_settings['benefits'] = $benefitListSorted;
    }

    return [
      '#theme' => 'benefits',
      '#template' => 'benefits',
      '#elementSettings' => $element_settings,
      '#elementMarkup' => $element_markup,
      '#elementContext' => $element_context,
      '#elementClass' => $element_class,
    ];
  }

}

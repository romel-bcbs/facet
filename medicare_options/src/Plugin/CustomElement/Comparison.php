<?php

namespace Drupal\medicare_options\Plugin\CustomElement;

use Drupal\bcbsma_plans\Service\MedicarePlanData;
use Drupal\cohesion_elements\CustomElementPluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Generic HTML element plugin for DX8.
 *
 * @package Drupal\cohesion\Plugin\CustomElement
 *
 * @CustomElement(
 *   id = "comparison",
 *   label = @Translation("Comparison")
 * )
 */
class Comparison extends CustomElementPluginBase {

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
   * A current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  // phpcs:ignore
  protected $currentPath;

  /**
   * A alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  // phpcs:ignore
  protected $aliasManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  // phpcs:ignore
  protected $configFactory;

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
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   A current path.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   A alias manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    LoggerChannelFactoryInterface $logger,
    RequestStack $request,
    MedicarePlanData $planData,
    EntityTypeManager $entityTypeManager,
    CurrentPathStack $currentPath,
    AliasManagerInterface $aliasManager,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->logger = $logger;
    $this->request = $request;
    $this->planData = $planData;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentPath = $currentPath;
    $this->aliasManager = $aliasManager;
    $this->configFactory = $configFactory;
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
      $container->get('path.current'),
      $container->get('path_alias.manager'),
      $container->get('config.factory'),
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  private function getBenefitsText($planBenefits, $networkDisplays, $benefitName) {
    $benefitList = [];
    if (!empty($planBenefits)) {
      foreach ($planBenefits as $key => $value) {
        $benefitObject = new \stdClass();
        $benefitObject->{'label'} = $key;
        $benefitTextObject = new \stdClass();
        $aggregateType = $value['aggregateType'];
        $headers = [];
        // Looping through benefitText.
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
                $benefitTextsObject->{$networkTypes} = [
                  $networkDisplay,
                  $containerStart,
                ];
              }

              switch ($value['unitType']) {
                case '$':
                  $benefitTextsObject->{$networkTypes}[] = $itemStart . $value['prefix'] . ' $' . $value['unit'] . ' ' . $value['suffix'] . $itemEnd;
                  break;

                case '%':
                  $benefitTextsObject->{$networkTypes}[] = $itemStart . $value['prefix'] . ' ' . $value['unit'] . '% ' . $value['suffix'] . $itemEnd;
                  break;

                default:
                  $benefitTextsObject->{$networkTypes}[] = $itemStart . $value['prefix'] . $value['suffix'] . $itemEnd;
              }
            }
          }
        }

        $benefitObject->{'text'} = '';
        foreach ($benefitTextsObject as $benefitTexts) {
          $benefitObject->{'text'} .= (implode(' ', $benefitTexts) . $containerEnd) . '</div>';
        }
        $benefitList[] = $benefitObject;
      }
    }

    return $benefitList;
  }

  /**
   * {@inheritdoc}
   */
  private function getBenefitsByCategory($planInfoData, $benefitsLables, $networkDisplays, $category) {
    $benefitsT = [];
    foreach ($planInfoData as $plan) {
      foreach ($plan['planBenefits'] as $benefits) {
        if ($benefits['benefitCategory'] == $category) {
          $benefitsT[] = $benefits['benefitName'];
        }
      }
    }
    $benefitsT = array_values(array_unique($benefitsT));

    $benefits = [];
    foreach ($benefitsLables as $key => $valus) {
      if (in_array($key, $benefitsT)) {
        $benefit = new \stdClass();
        $benefit->{'name'} = $key;
        $benefit->{'label'} = $valus->{'label'};
        $benefit->{'tooltip'} = $valus->{'tooltip'};
        $benefit->{'tags'} = $valus->{'tags'};
        $benefit->{'values'} = [];

        $benefitName = $benefit->{'name'};
        foreach ($planInfoData as $plan) {
          $planBenefits = array_filter($plan['planBenefits'], function ($benefit) use ($category, $benefitName) {
            return $benefit['benefitCategory'] == $category && $benefit['benefitName'] == $benefitName;
          });
          $benefit->{'values'}[] = $this->getBenefitsText($planBenefits, $networkDisplays, $benefit->{'name'});
        }
        $benefits[] = $benefit;
      }
    }
    return $benefits;
  }

  /**
   * {@inheritdoc}
   */
  public function render($element_settings, $element_markup, $element_class, $element_context = []) {
    $config = $this->configFactory->get('medicare_options.site_config_form');
    $plansType = NULL;
    $medicareData = $this->planData;
    $networkDisplays = new \stdClass();
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'network_type')
      ->sort('weight', 'ASC')
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
    try {
      $session = $this->request->getSession();
      $planIDs = $session->get("comparisonIds");

      if (is_null($planIDs) || empty($planIDs)) {
        return [
          '#type' => 'inline_template',
          '#template' => '<h3> Nothing To Compare</h3>',
          '#cache' => [
            'max-age' => 0,
          ],
        ];
      }
      $configMonth = $config->get('month');
      $month = (empty($configMonth) && $configMonth == NULL) ? idate('m') : (int) $configMonth;
      $element_settings['month'] = $month;
      $element_settings['ids'] = implode(',', $planIDs);
      $element_settings['number'] = count($planIDs);
      $planInfoData = $medicareData->getPlanDetails($planIDs);

      // Get ZIP Code needed for enroll button and Premium.
      $county = !is_null($session->get("county")) ? $session->get("county") : '';
      $zipCode = !is_null($session->get("zipCode")) ? $session->get("zipCode") : '';

      // Get URL needed for link to details pages.
      $uri = $this->currentPath->getPath();
      $uri = $this->aliasManager->getAliasByPath($uri);
      $element_settings['listingurl'] = str_replace("/compare", "", $uri);
      $element_settings['plans'] = [];
      $planIndex = 0;

      $planInfoDataSorted = $planInfoData;
      usort($planInfoDataSorted, function ($a, $b) {
        $a = count($a['planInfo']['highlights']);
        $b = count($b['planInfo']['highlights']);
        if ($a == $b) {
          return 0;
        }
        return ($a < $b) ? 1 : -1;
      });

      $planslHighlights = [];
      $highlightsToolTip = [];
      foreach ($planInfoDataSorted as $plan) {
        $planslHighlights[] = $plan['planInfo']['highlights'];
        foreach ($plan['planInfo']['helpText'] as $key => $value) {
          if (!isset($highlightsToolTip[$key]) || empty($highlightsToolTip[$key])) {
            $highlightsToolTip[$key] = $value;
          }
        }
      }
      $element_settings['highlightsToolTip'] = $highlightsToolTip;

      $allHighlights = [];
      $allHighlights['Premium Per Month'] = 'N/A';
      foreach ($planslHighlights as $highlights) {
        foreach ($highlights as $highlight) {
          $allHighlights[explode(': ', $highlight)[0]] = 'N/A';
        }
      }
      $allHighlights['Benefit Highlights'] = 'N/A';

      $element_settings['allHighlights'] = [];
      foreach ($allHighlights as $key => $value) {
        $element_settings['allHighlights'][] = $key;
      }

      foreach ($planInfoData as $plan) {

        $planID = $planIDs[$planIndex++];
        $element_settings['type'] = $plan['planInfo']['type'];
        $planAlias = $this->aliasManager->getAliasByPath('/node/' . $planID);
        $planInfo = $plan['planInfo'];
        $plansType = $planInfo['type'];
        $enrollButton = '<a href="/medicare/enroll-now?planName=' . preg_replace('/[^A-Za-z0-9 ]/', '', $planInfo['name']) . '&planType=' . $planInfo['type'] . '&planYear=' . $planInfo['year'] . '&zipCode=' . $zipCode . '"
                    class="use-ajax coh-style-bcbsma-secondary-dark" title="Enroll now to ' . $planInfo['name'] . '" aria-label="Enroll Now to ' . $planInfo['name'] . '" data-dialog-type="modal">Enroll</a>';
        if (strpos($plan['planInfo']['type'], 'Prescription') !== FALSE) {
          $prescriptionUrl = $config->get('prescription_url');
          $enrollButton =
            '<a href="' . $prescriptionUrl . '" class="coh-style-bcbsma-secondary-dark" title="Enroll now to ' . $planInfo['name'] . '" aria-label="Enroll Now to ' . $planInfo['name'] . '" external-link-message="enroll-prescription">Enroll</a>';
        }
        if ($planInfo['year'] == date("Y") && idate('m') === 12 && strpos($planInfo['type'], 'Prescription') === FALSE) {
          $enrollButton =
            '<a href="#" class="coh-style-bcbsma-secondary-dark" title="Enroll now to ' . $planInfo['name'] . '" aria-label="Enroll Now to ' . $planInfo['name'] . '" external-link-message="enrollment-closed">Enroll</a>';
        }
        $planObject = new \stdClass();
        $planObject->{'id'} = $planID;
        $planObject->{'name'} = $planInfo['name'];
        $planObject->{'year'} = $planInfo['year'];
        $planObject->{'detailsURL'} = str_replace("/compare", "", $uri) . $planAlias;
        $planObject->{'enrollButton'} = $enrollButton;
        $planObject->{'highlights'} = [];

        if (strpos($planInfo['type'], 'Prescription') !== FALSE || strpos($planInfo['type'], 'Dental') !== FALSE) {
          $premium = $planInfo['premium'];
        }
        else {
          if (!is_null($county)) {
            $premium = $medicareData->getPremium($county, $planID);
          }
          else {
            $premium = "";
          }
        }
        if ($premium !== 'X.XX' && $premium !== '0') {
          $premium = number_format((float) $premium, 2, '.', '');
        }

        if ($premium !== "") {
          $planObject->{'highlights'}['Premium Per Month'] = '$' . $premium;
        }

        foreach ($plan['planInfo']['highlights'] as $h) {
          $key = explode(': ', $h)[0];
          $value = explode(': ', $h)[1] == '$0.00' ? '$0' : explode(': ', $h)[1];
          $planObject->{'highlights'}[$key] = $value;
        }

        $planObject->{'highlights'}['Benefit Highlights'] = $plan['planInfo']['topBenefits'];
        $element_settings['plans'][] = $planObject;
      }

      $benefitsCategories = [];
      foreach ($planInfoData as $plan) {
        foreach ($plan['planBenefits'] as $benefits) {
          $benefitsCategories[] = $benefits['benefitCategory'];
        }
      }
      $benefitsCategories = array_values(array_unique($benefitsCategories));

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
          $benefitNameObject->{'label'} = $term->getDescription()
            ? $term->getDescription()
            : $term->label();
          $benefitNameObject->{'tags'} = $term->get('field_tags')->value;
          $benefitNameObject->{'tooltip'} = $term->get('field_tooltip')->value;
          $benefitsLables[$term->label()] = $benefitNameObject;
        }
      }

      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
        ->accessCheck(FALSE)
        ->condition('vid', 'medicare_benefit_category')
        ->sort('weight', 'ASC')
        ->execute();

      if (!empty($terms)) {
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($terms);
        $element_settings['categories'] = [];
        foreach ($terms as $term) {
          if (in_array($term->label(), $benefitsCategories)) {
            $categoryObject = new \stdClass();
            $categoryObject->{'display'} = $term->get('field_display')->value ?? $term->label();
            $blockMachineName = $term->get('field_additional_block')->value ?? '';
            if (!empty($blockMachineName)) {
              $block = $this->entityTypeManager->getStorage('block')->load($blockMachineName);
              if (!is_null($block)) {
                $categoryObject->{'additionalBlock'} = $this->entityTypeManager->getViewBuilder('block')->view($block);
              }
            }
            $categoryObject->{'benefits'} = $this->getBenefitsByCategory($planInfoData, $benefitsLables, $networkDisplays, $term->label());
            $element_settings['categories'][] = $categoryObject;
          }
        }
      }

      $hiddenpageinfoBlock = $this->entityTypeManager->getStorage('block')->load('hiddenpageinfo');
      if (!is_null($hiddenpageinfoBlock)) {
        $element_settings['hiddenpageinfo'] = $this->entityTypeManager->getViewBuilder('block')->view($hiddenpageinfoBlock);
      }

      $denatalAdditionalBlock = $this->entityTypeManager->getStorage('block')->load('denataladditionalblock');
      if (!is_null($denatalAdditionalBlock) && strpos($plansType, 'Dental') !== FALSE) {
        $element_settings['denataladditionalblock'] = $this->entityTypeManager->getViewBuilder('block')->view($denatalAdditionalBlock);
      }

    }
    catch (\Exception $exception) {
      $this->logger->get('comparison')->info($exception->getMessage());
    }

    return [
      '#theme' => 'comparison',
      '#template' => 'comparison',
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

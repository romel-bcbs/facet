<?php

namespace Drupal\bcbsma_plans\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Class for MedicarePlanData Service.
 *
 * @package Drupal\bcbsma_plans\Service
 */
class MedicarePlanData implements ContainerInjectionInterface {

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  // phpcs:ignore
  protected $logger;

  /**
   * A entity query.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  // phpcs:ignore
  protected $entityTypeManager;

  /**
   * A entity field.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  // phpcs:ignore
  protected $entityFieldManager;

  /**
   * A Cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  // phpcs:ignore
  protected $cache;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  // phpcs:ignore
  protected $connection;

  const VALUE = "value";
  const TARGET_ID = 'target_id';
  const PLAN_HIGHLIGHTS = 'planHighlights';
  const FORMAT = 'format';

  /**
   * Constructs a Drupal\bcbsma_plans\Service object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger Factory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A database connection.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   A Entity FIleds.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache.
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection instance.
   */
  public function __construct(LoggerChannelFactoryInterface $logger,
  EntityTypeManager $entityTypeManager,
  EntityFieldManagerInterface $entityFieldManager,
  CacheBackendInterface $cache,
  Connection $connection) {
    $this->logger = $logger;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->cache = $cache;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('cache.bcbsma_plans'),
      $container->get('database')
    );
  }

  /**
   * Provides PlanDetails getPlanDetails().
   *
   * @param mixed[] $planIds
   *   Array of node Ids.
   *
   * @return mixed[]
   *   Plan information
   */
  public function getPlanDetails(array $planIds): array {
    // Internal use Plan IDs nothing but Plan Content Node ID.
    $response = [];
    $benefitIds = [];
    $benefitEntityIds = [];
    $planEntities = $this->entityTypeManager->getStorage('node')->loadMultiple($planIds);
    // Looping through all Plan Entities.
    foreach ($planEntities as $plan) {
      // To get plan Title.
      $planName = $plan->getTitle();
      // Get Plan benefits attahced to this Entitiy.
      $benefitIds = $plan->field_plan_benefits->getValue();
      // Loop through all Benefit IDs refrences to Plan.
      foreach ($benefitIds as $benefitId) {
        $benefitEntityIds[$planName][] = $benefitId[self::TARGET_ID];
      }
      // Get plan info from getPlanInfo() by passing planIds.
      $planInfo = $this->getPlanInfo($planIds);
      $response[$planName]['planInfo'] = $planInfo[$planName];
      // Get benefit info from getPlanBenefits() by passing benefit Ids.
      $response[$planName]['planBenefits'] = isset($benefitEntityIds[$planName]) ? $this->getPlanBenefits($benefitEntityIds[$planName]) : [];
    }

    // Returns entire plan info and Plan details.
    return $response;
  }

  /**
   * Return only plan information with no benefits details.
   *
   * @param mixed[] $planIds
   *   Array of node Ids.
   *
   * @return mixed[]
   *   Plan information
   */
  public function getPlanInfo(array $planIds): array {
    $response = [];
    // Loading all Plan ids to get node Entity of plan.
    $planEntities = $this->entityTypeManager->getStorage('node')->loadMultiple($planIds);
    // Looping through all plan entitys.
    foreach ($planEntities as $plan) {
      // Checking if $plan entity is no null.
      if (!is_null($plan)) {
        // Get Plan Title.
        $pName = $plan->getTitle();
        // Load Plan ID we have to rewrite this.
        // In case if we want to load enities by plan name and plan ID.
        // Since internal use we used node iD as a parameter.
        // PlanId nothing but Node id.
        $response[$pName]['order'] = $plan->field_node_weight->getValue()[0][self::VALUE] ?? '';
        $response[$pName]['id'] = $plan->id() ?? '';
        $response[$pName]['name'] = $plan->field_display_name->getValue()[0][self::VALUE] ?? $plan->getTitle();
        $response[$pName]['status'] = $plan->isPublished();
        // Retriving Plan Year and calling get method to for Name.
        $yearId = $plan->field_plan_year->getValue()[0][self::TARGET_ID];
        $response[$pName]['year'] = $this->getTaxonomyName([$yearId])[0];
        // Retriving Plan type and calling get method to for Name.
        $planTypeId = $plan->field_plan_type->getValue()[0][self::TARGET_ID];
        $response[$pName]['type'] = $this->getTaxonomyName([$planTypeId])[0];
        $response[$pName]['planTypeId'] = $this->getPlanTypeId([$planTypeId])[0];
        // Retriving Plan category and calling get method to for Name.
        $planCategoryId = $plan->field_plan_category->getValue()[0][self::TARGET_ID] ?? '';
        $response[$pName]['category'] = !empty($planCategoryId) ? $this->getTaxonomyName([$planCategoryId])[0] : '';
        if ($response[$pName]['category'] === "PPO") {
          $response[$pName]['planTypeId'] = "2";
        }
        // Retring other info from plan table and validating Null cases.
        $response[$pName]['rating'] = $plan->field_plan_rating->getValue()[0][self::VALUE] ?? '';
        $response[$pName]['topBenefits'] = $plan->field_plan_top_benefits->getValue()[0][self::VALUE] ?? '';
        $response[$pName]['disclaimer'] = $plan->field_plan_disclaimer->getValue()[0][self::VALUE] ?? '';
        $response[$pName]['hoverText'] = $plan->field_plan_hover_text->getValue()[0][self::VALUE] ?? '';
        // Retriving Network info.
        $medicalNetworkId = $plan->field_medical_network->getValue()[0][self::TARGET_ID] ?? '';
        $response[$pName]['medicalNetwork'] = !empty($medicalNetworkId) ? $this->getTaxonomyName([$medicalNetworkId])[0] : '';
        // Retreving Pharmacy Info.
        $pharmacyNetworkId = $plan->field_pharmacy_network->getValue()[0][self::TARGET_ID] ?? '';
        $response[$pName]['pharmacyNetwork'] = !empty($pharmacyNetworkId) ? $this->getTaxonomyName([$medicalNetworkId])[0] : '';
        // Retreving formulary Id.
        $response[$pName]['formularyId'] = $plan->field_formulary_id->getValue()[0][self::VALUE] ?? '';
        $response[$pName]['premium'] = $plan->field_plan_premium->getValue()[0][self::VALUE] ?? '';
        $premiumhelpText = $plan->field_plan_premium->getFieldDefinition()->getDescription() ?? '';
        $premiumLabel = $plan->field_plan_premium->getFieldDefinition()->getLabel() ?? '';
        $response[$pName]['helpText'][$premiumLabel] = $premiumhelpText;
        // Creating plan highlights.
        if (strpos($response[$pName]['type'], 'Advantage') !== FALSE) {
          // Retreving Coverage Information.
          // These values available only for Advantage plans.
          // Maximum out of pocket.
          $outOfPocketLabel = $plan->field_maximum_out_of_pocket_cost->getFieldDefinition()->getLabel() ?? '';
          $outOfPocket = $plan->field_maximum_out_of_pocket_cost->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['outOfPocket'] = !empty($outOfPocket) ? $outOfPocket : '';
          $outOfPockethelpText = $plan->field_maximum_out_of_pocket_cost->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$outOfPocketLabel] = $outOfPockethelpText;

          // PCP Copay.
          $pcpCopayLabel = $plan->field_pcp_in_network_copay->getFieldDefinition()->getLabel() ?? '';
          $pcpCopay = $plan->field_pcp_in_network_copay->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['pcpCopay'] = !empty($pcpCopay) ? number_format($pcpCopay) : '';
          $pcpCopayhelpText = $plan->field_pcp_in_network_copay->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$pcpCopayLabel] = $pcpCopayhelpText;

          // Specialist Copay.
          $specialistCopayLabel = $plan->field_spec_in_network_copay->getFieldDefinition()->getLabel() ?? '';
          $specialistCopay = $plan->field_spec_in_network_copay->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['specialistCopay'] = !empty($specialistCopay) ? number_format($specialistCopay) : '';
          $specialisthelpText = $plan->field_spec_in_network_copay->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$specialistCopayLabel] = $specialisthelpText;

          // Inpatient In-Network.
          $inpatNetworkpayLabel = $plan->field_inpatient_in_network_copay->getFieldDefinition()->getLabel() ?? '';
          $inpatNetworkpay = $plan->field_inpatient_in_network_copay->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['inpatientInNetworkCopay'] = !empty($inpatNetworkpay) ? $inpatNetworkpay : '';
          $inpatNetworkhelpText = $plan->field_inpatient_in_network_copay->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$inpatNetworkpayLabel] = $inpatNetworkhelpText;

          // Tier 1 Preferred Pharmacy Copay.
          $tier1PharmacyLabel = $plan->field_tier_1_pref_pharmacy_copay->getFieldDefinition()->getLabel() ?? '';
          $tier1Pharmacy = $plan->field_tier_1_pref_pharmacy_copay->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['tier1PrePharCopay'] = !empty($tier1Pharmacy) ? number_format($tier1Pharmacy) : '';
          $tier1PharmacyhelpText = $plan->field_tier_1_pref_pharmacy_copay->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$tier1PharmacyLabel] = $tier1PharmacyhelpText;

          // Creating Highlights array.
          $response[$pName]['highlights'] = [
            $outOfPocketLabel . ': $' . number_format($outOfPocket),
            $pcpCopayLabel . ': $' . number_format($pcpCopay),
            $specialistCopayLabel . ': $' . number_format($specialistCopay),
            $inpatNetworkpayLabel . ': ' . $inpatNetworkpay,
            $tier1PharmacyLabel . ': $' . number_format($tier1Pharmacy),
          ];
        }
        elseif (strpos($response[$pName]['type'], 'Supplement') !== FALSE) {
          // Retreving Coverage Information.
          // These values available only for Supplement plans.
          // Part A Deductible.
          $partaDeducLabel = $plan->field_part_a_deductible->getFieldDefinition()->getLabel() ?? '';
          $partaDeduc = $plan->field_part_a_deductible->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['partAdeductible'] = !empty($partaDeduc) ? number_format($partaDeduc) : '';
          $partaDeduchelpText = $plan->field_part_a_deductible->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$partaDeducLabel] = $partaDeduchelpText;

          // Part B Deductible.
          $partbDeducLabel = $plan->field_part_b_deductible->getFieldDefinition()->getLabel() ?? '';
          $partbDeduc = $plan->field_part_b_deductible->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['partBdeductible'] = !empty($partbDeduc) ? number_format($partbDeduc) : '';
          $partbDeduchelpText = $plan->field_part_b_deductible->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$partbDeducLabel] = $partbDeduchelpText;

          // Provider Network.
          $providerNetworkLabel = $plan->field_provider_network->getFieldDefinition()->getLabel() ?? '';
          $providerNetwork = $plan->field_provider_network->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['providerNetwork'] = !empty($providerNetwork) ? $providerNetwork : '';
          $providerhelpText = $plan->field_provider_network->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$providerNetworkLabel] = $providerhelpText;

          // Specialist Referral.
          $specialistReferralLabel = $plan->field_specialist_referral->getFieldDefinition()->getLabel() ?? '';
          $specialistReferral = $plan->field_specialist_referral->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['inpatientInNetworkCopay'] = !empty($specialistReferral) ? $specialistReferral : '';
          $specialisthelpText = $plan->field_specialist_referral->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$specialistReferralLabel] = $specialisthelpText;

          // Foreign Travel Coverage.
          $foreignTravelCoverageLabel = $plan->field_foreign_travel_coverage->getFieldDefinition()->getLabel() ?? '';
          $foreignTravelCoverage = $plan->field_foreign_travel_coverage->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['foreignTravelCoverage'] = !empty($foreignTravelCoverage) ? $foreignTravelCoverage : '';
          $foreignhelpText = $plan->field_foreign_travel_coverage->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$foreignTravelCoverageLabel] = $foreignhelpText;

          // Creating Highlights array.
          $response[$pName]['highlights'] = [
            $partaDeducLabel . ': $' . number_format($partaDeduc),
            $partbDeducLabel . ': $' . number_format($partbDeduc),
            $providerNetworkLabel . ': ' . $providerNetwork,
            $specialistReferralLabel . ': ' . $specialistReferral,
            $foreignTravelCoverageLabel . ': ' . $foreignTravelCoverage,
          ];
        }
        elseif (strpos($response[$pName]['type'], 'Dental') !== FALSE) {

          // Retreving Coverage Information.
          // These values available only for Dental plans.
          // Deductible.
          $deductibleLabel = $plan->field_deductible->getFieldDefinition()->getLabel() ?? '';
          $deductible = $plan->field_deductible->getValue()[0][self::VALUE] ?? NULL;
          $response[$pName]['highlights'] = [];
          if (!is_null($deductible)) {
            $response[$pName][self::PLAN_HIGHLIGHTS]['deductible'] = !empty($deductible) ? $deductible : '';
            $deductiblehelpText = $plan->field_deductible->getFieldDefinition()->getDescription() ?? '';
            $response[$pName]['helpText'][$deductibleLabel] = $deductiblehelpText;
            array_push($response[$pName]['highlights'], $deductibleLabel . ': $' . $deductible);
          }
          // Calendar Year Maximum.
          $calendarYearMaximumLabel = $plan->field_calendar_year_maximum->getFieldDefinition()->getLabel() ?? '';
          $calendarYearMaximum = $plan->field_calendar_year_maximum->getValue()[0][self::VALUE] ?? '';
          $response[$pName][self::PLAN_HIGHLIGHTS]['calendarYearMaximum'] = !empty($calendarYearMaximum) ? number_format($calendarYearMaximum) : '';
          $calendarYearhelpText = $plan->field_calendar_year_maximum->getFieldDefinition()->getDescription() ?? '';
          $response[$pName]['helpText'][$calendarYearMaximumLabel] = $calendarYearhelpText;
          // Creating plan highlights Array.
          array_push($response[$pName]['highlights'], $calendarYearMaximumLabel . ': $' . number_format($calendarYearMaximum));
        }
        elseif (strpos($response[$pName]['type'], 'Prescription') !== FALSE) {

          // Retreving Coverage Information.
          // These values available only for Dental plans.
          // Deductible.
          $deductibleLabel = $plan->field_deductible->getFieldDefinition()->getLabel() ?? '';
          $deductible = $plan->field_deductible->getValue()[0][self::VALUE] ?? NULL;
          if (!is_null($deductible)) {
            $response[$pName][self::PLAN_HIGHLIGHTS]['deductible'] = !empty($deductible) ? $deductible : '';
            $deductiblehelpText = $plan->field_deductible->getFieldDefinition()->getDescription() ?? '';
            // Creating plan highlights Array.
            $response[$pName]['highlights'][] = $deductibleLabel . ': $' . $deductible;
            $response[$pName]['helpText'][$deductibleLabel] = $deductiblehelpText;
          }
        }
        if (isset($plan->field_plan_highlight_key_values)) {
          $planHighNosort = $plan->field_plan_highlight_key_values->getValue() ?? '';
          if (!empty($planHighNosort)) {
            foreach ($planHighNosort as $value) {
              $response[$pName]['helpText'][$value['key']] = $value['description'];
              $response[$pName]['highlights'][] = $value['key'] . ': ' . $value['value'];
            }
          }
        }
      }
    }
    // Returning plan info.
    return $response;
  }

  /**
   * Get plan Benefits.
   *
   * @param mixed[] $benefitIds
   *   Array of node Ids.
   *
   * @return mixed[]
   *   Plan information
   */
  public function getPlanBenefits(array $benefitIds): array {
    // Returns only benefit information for plan.
    // Declaring varirbles required.
    $response = [];
    $benefitTextEntityIds = [];
    $network = [];
    // Loading multiple Entity by Benefit Ids.
    $benefitEntities = $this->entityTypeManager->getStorage('node')->loadMultiple($benefitIds);
    // Looping thorugh each benefit Entity.
    foreach ($benefitEntities as $benefit) {
      // Retriving Benefit title with Refrence id and validating null case.
      $benefitName = $benefit->field_benefit_name->getValue()[0][self::TARGET_ID] ?? '';
      $benefitTitle = !empty($benefitName) ? $this->getTaxonomyName([$benefitName])[0] : '';
      // Retriving Benefit Category with Refrence id and validating null case.
      $benefitCategory = $benefit->field_benefit_category->getValue()[0][self::TARGET_ID] ?? '';
      $benfitArray['benefitCategory'] = !empty($benefitCategory) ? $this->getTaxonomyName([$benefitCategory])[0] : '';
      // Setting Benefit name to benefit Array.
      $benfitArray['benefitName'] = $benefitTitle;
      $benfitArray['aggregateType'] = $benefit->field_benefit_aggregation_type->getValue()[0][self::VALUE] ?? '';
      // Retreving Benefit text refrence IDs.
      $benefittextIds = $benefit->field_benefit_text->getValue();
      // Looping through each benefit text refrence ID.
      foreach ($benefittextIds as $benefitTextId) {
        // Setting benefit Entity Ids arrary.
        $benefitTextEntityIds[$benefitTitle][] = $benefitTextId[self::TARGET_ID];
      }
      // Loading Benefittext Entity using Text ID.
      $benefitTextEntities = $this->entityTypeManager->getStorage('node')->loadMultiple($benefitTextEntityIds[$benefitTitle]);
      // Looping through all Benefit Entity text Object.
      $benfitArray['benefitText'] = [];
      foreach ($benefitTextEntities as $benefitTextEntitie) {
        // Creating text array with individual Fields.
        $prefix = $benefitTextEntitie->field_benefit_prefix_text->getValue()[0][self::VALUE] ?? '';
        $prefixFormat = $benefitTextEntitie->field_benefit_prefix_text->getValue()[0][self::FORMAT] ?? '';

        if ($prefixFormat == 'full_html') {
          $text['prefix'] = $prefix;
        }
        else {
          $text['prefix'] = strip_tags($prefix);
        }

        $text['unit'] = $benefitTextEntitie->field_benefit_unit->getValue()[0][self::VALUE] ?? '';
        $text['unitType'] = $benefitTextEntitie->field_benefit_unit_type->getValue()[0][self::VALUE] ?? '';
        // Retreving Network IDs.
        $networkIds = $benefitTextEntitie->field_benefit_network_type->getValue();
        // Looping through Newtwork ids.
        $network = [];
        foreach ($networkIds as $networkId) {
          // Setting network array with specific benefit title.
          $network[$benefitTitle][] = $networkId[self::TARGET_ID];
        }
        // Adding Network Names by caling getNetworkType() Method.
        $text['networkType'] = $this->getNetworkType($network[$benefitTitle]);
        $suffix = $benefitTextEntitie->field_benefit_suffix_text->getValue()[0][self::VALUE] ?? '';
        $suffixFormat = $benefitTextEntitie->field_benefit_suffix_text->getValue()[0][self::FORMAT] ?? '';
        if ($suffixFormat == 'full_html') {
          $text['suffix'] = $suffix;
        }
        else {
          $text['suffix'] = strip_tags($suffix);
        }

        // Setting values to Benefit array.
        $benfitArray['benefitText'][] = $text;
      }
      $response[$benefitTitle] = $benfitArray;
    }
    // Returning Benefit array.
    return $response;
  }

  /**
   * To get all Content Type Fields.
   *
   * @param string $contentType
   *   Content Type name.
   *
   * @return mixed[]
   *   Fields of content type.
   */
  public function getFields(string $contentType): array {
    // Used to retrive Fileds avalable in content type.
    // By passing content type machine name.
    $fields = [];
    // Looping through each filed Defination.
    foreach ($this->entityFieldManager->getFieldDefinitions('node', $contentType) as $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        // Settgin name, Type and settitng to fields array.
        $fields['name'][] = $field_definition->getName();
        $fields['type'][] = $field_definition->getType();
        $fields['setting'][] = $field_definition->getSettings();
      }
    }
    // Returing fields Array.
    return $fields;
  }

  /**
   * Get Taxonomy Names.
   *
   * @param mixed[] $taxonomy_ids
   *   Array of Taxonomu Ids.
   *
   * @return mixed[]
   *   returns Array names
   */
  public function getTaxonomyName(array $taxonomy_ids): array {
    $term_name = [];
    // Loading multiple Taxonomy iDs to retrive Entities.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($taxonomy_ids);
    // Looping through each term object to retrive Name of the term.
    foreach ($terms as $term) {
      $term_name[] = $term->getName();
    }
    // Returing term name array.
    return $term_name;
  }

  /**
   * Get Plan Type Id.
   *
   * @param mixed[] $taxonomy_ids
   *   Array of Taxonomu Ids.
   *
   * @return mixed[]
   *   returns Array names
   */
  public function getPlanTypeId(array $taxonomy_ids): array {
    $planTypeId = [];
    // Loading multiple Taxonomy iDs to retrive Entities.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($taxonomy_ids);
    // Looping through each term object to retrive Name of the term.
    foreach ($terms as $term) {
      $planTypeId[] = $term->field_plan_type_id->getValue()[0][self::VALUE] ?? '';
    }
    // Returing term name array.
    return $planTypeId;
  }

  /**
   * Undocumented function.
   *
   * @param mixed[] $networkIds
   *   NetworkIds.
   *
   * @return mixed[]
   *   List of Networks.
   */
  public function getNetworkType(array $networkIds): array {
    // Returning Taxonomy Names for network Ids.
    return $this->getTaxonomyName($networkIds);
  }

  /**
   * Get Plan Premium.
   *
   * @param string $countyName
   *   CountyName.
   * @param string $planId
   *   Plan Node ID.
   *
   * @return string
   *   returns Array highlights
   */
  public function getPremium(string $countyName, string $planId): string {
    // Declaring Variables Required.
    $premium = '';
    // Entity query to get node ID of county Node.
    $countyQuery = $this->entityTypeManager->getStorage('node');
    $countyResults = $countyQuery->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'counties')
      ->condition('title', $countyName)
      ->condition('status', 1)
      ->execute();
    // Looping through Results to get county Node Id as String.
    foreach ($countyResults as $county) {
      $countyId = $county;
    }
    // Validating county Id Exists if not early Return.
    if (empty($countyId)) {
      return $premium;
    }

    // Quaring DB to get price for county and plan.
    // Query will return exact price for plan in the county.
    $query = $this->connection->select('paragraph__field_plan', 'p');
    $query->leftJoin('node__field_plan_prices', 'n', 'n.field_plan_prices_target_id = p.entity_id');
    $query->addField('p', 'entity_id');
    $query->condition('n.entity_id', $countyId);
    $query->condition('p.field_plan_target_id', $planId);
    $results = $query->execute()->fetchAll();

    // Looping all Results since this will be an array.
    foreach ($results as $paragraphId) {
      $priceEntity = $this->entityTypeManager->getStorage('paragraph')->load($paragraphId->entity_id);
      if (isset($priceEntity->field_price)) {
        $premium = $priceEntity->field_price->getValue()[0][self::VALUE] ?? '';
      }
    }
    // Returning Premium.
    return $premium;
  }

}

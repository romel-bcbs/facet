<?php

namespace Drupal\medicare_options\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Example configuration form.
 */
class ZipsDataForm extends ConfigFormBase {

  const SETTINGS = 'medicare_options.zips_data';

  /**
   * FileSystem variable.
   */
  protected FileSystem $fileSystem;

  /**
   * EntityTypeManager variable.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function __construct(
    FileSystem $fileSystem,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->fileSystem = $fileSystem;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ConfigFormBase {
    return new static(
      $container->get('file_system'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'medicare_options_zips_data';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [
      '#attributes' => ['enctype' => 'multipart/form-data'],
    ];

    $form['excel_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Select the Excel file'),
      '#description' => $this->t('Upload a file for the ZIP code related.'),
      '#upload_location' => 'public://excels/zips/',
      '#multiple' => FALSE,
      '#upload_validators' => [
        'file_validate_extensions' => ['xlsx'],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('excel_file') == NULL) {
      $form_state->setErrorByName('excel_file', $this->t('Select a file!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $file = $this->entityTypeManager->getStorage('file')
      ->load($form_state->getValue('excel_file')[0]);
    $uploadedFileName = $this->fileSystem
      ->realpath('public://excels/zips/' . basename($file->get('uri')->value));

    $reader = new Xlsx();
    $sheetData = $reader->load($uploadedFileName)->getActiveSheet();
    $highestColumn = $sheetData->getHighestColumn();
    $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

    $data = new \stdClass();

    for ($row = 3; $row <= $sheetData->getHighestRow(); $row++) {
      $countyName = trim($sheetData->getCell('A' . $row)->getValue());

      if (!isset($data->$countyName)) {
        $data->$countyName = new \stdClass();
        $data->$countyName->{'zips'} = [];
        $data->$countyName->{'prices'} = [];
        for ($c = 3; $c <= $highestColumnIndex; $c++) {
          $columnLetter = Coordinate::stringFromColumnIndex($c);
          if ($sheetData->getCell($columnLetter . '2')->getValue() && $sheetData->getCell($columnLetter . $row)->getValue() != 'N/A') {
            $price = new \stdClass();
            $price->{'planId'} = $sheetData->getCell($columnLetter . '2')->getValue();
            $price->{'price'} = $sheetData->getCell($columnLetter . $row)->getValue();
            $data->$countyName->{'prices'}[] = $price;
          }
        }
      }
      $data->$countyName->{'zips'}[] = '0' . $sheetData->getCell('B' . $row)->getValue();
    }

    foreach ($data as $key => $value) {
      $node_storage = $this->entityTypeManager->getStorage('node');
      $county = $node_storage->create([
        'type' => 'counties',
        'title' => $key,
      ]);
      $county->set('field_zips', $value->{'zips'});
      foreach ($value->{'prices'} as $price) {
        $plan = $this->entityTypeManager->getStorage('node')
          ->load(intval($price->{'planId'}));
        $planReference = [
          'target_id' => $plan->id(),
          'target_type' => 'node',
          'target_uuid' => $plan->uuid(),
        ];
        $planPrice = $this->entityTypeManager->getStorage('paragraph')->create([
          'type' => 'plan_price',
          'field_plan' => $planReference,
          'field_price' => $price->{'price'},
        ]);
        $planPrice->save();

        $planPrices = $county->get('field_plan_prices')->getValue();

        if (method_exists($planPrice, 'getRevisionId')) {
          $planPrices[] = [
            'target_id' => $planPrice->id(),
            'target_revision_id' => $planPrice->getRevisionId(),
          ];
        }
        $county->set('field_plan_prices', $planPrices);

      }

      $county->save();
    }

    $config->set('excel_file', $form_state->getValue('excel_file'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

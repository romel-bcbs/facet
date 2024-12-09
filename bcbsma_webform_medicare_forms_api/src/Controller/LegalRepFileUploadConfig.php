<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Controller;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Configure example settings for this site.
 */
class LegalRepFileUploadConfig extends ConfigFormBase {
  /**
   * Config settings.
   * */
  const SETTINGS = 'bcbsma_webform_medicare_forms_api.legalrep_upload_config';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bcbsma_webform_legal_rep_file_upload_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $form['legal_rep_file_upload_file_api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File API Settings'),
    ];
    $form['legal_rep_file_upload_file_api_settings']['api_details'] = [
      '#type' => 'markup',
      '#title' => $this->t('API Details'),
      '#markup' => Markup::create('API setting refer Keys'),
    ];
    $form['legal_rep_file_upload_file_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File Settings'),
    ];
    $form['legal_rep_file_upload_file_settings']['max_file_size_mb'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MAX File Size MB'),
      '#required' => TRUE,
      '#default_value' => $config->get('max_file_size_mb'),
    ];
    $form['legal_rep_file_upload_file_settings']['max_files_upload'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MAX Files Upload'),
      '#required' => TRUE,
      '#default_value' => $config->get('max_files_upload'),
    ];
    $form['legal_rep_file_upload_file_settings']['total_files_limit_exceeded_mb'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Total Files Limit Exceeded MB'),
      '#required' => TRUE,
      '#default_value' => $config->get('total_files_limit_exceeded_mb'),
    ];
    $form['legal_rep_file_upload_file_settings']['accepted_file_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accepted File Format'),
      '#required' => TRUE,
      '#default_value' => $config->get('accepted_file_format'),
    ];
    $form['legal_rep_file_upload_error_msg'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Error Message'),
    ];
    $form['legal_rep_file_upload_error_msg']['atleast_one_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AtLeast One File'),
      '#required' => TRUE,
      '#default_value' => $config->get('atleast_one_file'),
    ];
    $form['legal_rep_file_upload_error_msg']['required_documentation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Required Documentation'),
      '#required' => TRUE,
      '#default_value' => $config->get('required_documentation'),
    ];
    $form['legal_rep_file_upload_error_msg']['size_limit_exceeded'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Size Limit Exceeded'),
      '#required' => TRUE,
      '#default_value' => $config->get('size_limit_exceeded'),
    ];
    $form['legal_rep_file_upload_error_msg']['total_limit_exceeded'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Total Limit Exceeded'),
      '#required' => TRUE,
      '#default_value' => $config->get('total_limit_exceeded'),
    ];
    $form['legal_rep_file_upload_error_msg']['doc_limit_exceeded'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DOC Limit Exceeded'),
      '#required' => TRUE,
      '#default_value' => $config->get('doc_limit_exceeded'),
    ];
    $form['legal_rep_file_upload_error_msg']['unsupported_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unsupported Format'),
      '#required' => TRUE,
      '#default_value' => $config->get('unsupported_format'),
    ];
    $form['legal_rep_file_upload_error_msg']['has_malware'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Has Malware / Uploaded doc has error'),
      '#required' => TRUE,
      '#default_value' => $config->get('has_malware'),
    ];
    $form['legal_rep_file_upload_error_msg']['upload_progress'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File Upload In-Progress'),
      '#default_value' => $config->get('upload_progress'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('bcbsma_webform_medicare_forms_api.legalrep_upload_config')
      ->set('max_file_size_mb', $form_state->getValue('max_file_size_mb'))
      ->set('max_files_upload', $form_state->getValue('max_files_upload'))
      ->set('total_files_limit_exceeded_mb', $form_state->getValue('total_files_limit_exceeded_mb'))
      ->set('accepted_file_format', $form_state->getValue('accepted_file_format'))
      ->set('atleast_one_file', $form_state->getValue('atleast_one_file'))
      ->set('required_documentation', $form_state->getValue('required_documentation'))
      ->set('size_limit_exceeded', $form_state->getValue('size_limit_exceeded'))
      ->set('total_limit_exceeded', $form_state->getValue('total_limit_exceeded'))
      ->set('doc_limit_exceeded', $form_state->getValue('doc_limit_exceeded'))
      ->set('unsupported_format', $form_state->getValue('unsupported_format'))
      ->set('has_malware', $form_state->getValue('has_malware'))
      ->set('upload_progress', $form_state->getValue('upload_progress'))
      ->save();
  }

}

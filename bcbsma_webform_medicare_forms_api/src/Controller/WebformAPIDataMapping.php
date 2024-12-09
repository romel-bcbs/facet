<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Controller;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class WebformAPIDataMapping extends ConfigFormBase {
  /**
   * Config settings.
   * */
  const SETTINGS = 'bcbsma_webform_medicare_forms_api.field_mapping';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bcbsma_webform_medicare_forms_api_data_mapping';
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
    $form['medicare_field_data_mapping_AOR_MAPD'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - AOR_MAPD(authorized_representative_form)'),
      '#default_value' => $config->get('medicare_field_data_mapping_AOR_MAPD'),
    ];
    $form['medicare_field_data_mapping_AOR_MEDEX'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - AOR_MEDEX(member_designee_form_updated)'),
      '#default_value' => $config->get('medicare_field_data_mapping_AOR_MEDEX'),
    ];
    $form['medicare_field_data_mapping_legal_rep'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - Legal Rep(legal_representative_form)'),
      '#default_value' => $config->get('medicare_field_data_mapping_legal_rep'),
    ];
    $form['medicare_field_data_mapping_legal_rep_doc_sections'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping doc section - Legal Rep(legal_representative_form)'),
      '#default_value' => $config->get('medicare_field_data_mapping_legal_rep_doc_sections'),
    ];
    $form['medicare_field_data_mapping_campaign'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - Campaign & GuideBook Registration'),
      '#default_value' => $config->get('medicare_field_data_mapping_campaign'),
    ];
    $form['medicare_field_data_mapping_seminar'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - Seminar'),
      '#default_value' => $config->get('medicare_field_data_mapping_seminar'),
    ];
    $form['medicare_field_data_mapping_webinar'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - Webinar'),
      '#default_value' => $config->get('medicare_field_data_mapping_webinar'),
    ];
    $form['medicare_field_data_mapping_request_a_call'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - Request a Call'),
      '#default_value' => $config->get('medicare_field_data_mapping_request_a_call'),
    ];
    $form['medicare_field_data_mapping_billchange'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - BillChange'),
      '#default_value' => $config->get('medicare_field_data_mapping_billchange'),
    ];
    $form['medicare_field_data_mapping_planchange'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - PlanChange'),
      '#default_value' => $config->get('medicare_field_data_mapping_planchange'),
    ];
    $form['medicare_field_data_mapping_addresschange'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - AddressChange'),
      '#default_value' => $config->get('medicare_field_data_mapping_addresschange'),
    ];
    $form['medicare_field_data_error_confirm_msg'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sign designee API Error - error message'),
      '#default_value' => $config->get('medicare_field_data_error_confirm_msg'),
    ];
    $form['medicare_field_append_zeros_to_memberid'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Append zeros to Member ID'),
      '#default_value' => $config->get('medicare_field_append_zeros_to_memberid'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('bcbsma_webform_medicare_forms_api.field_mapping')
      ->set('medicare_field_data_mapping_AOR_MAPD', $form_state->getValue('medicare_field_data_mapping_AOR_MAPD'))
      ->set('medicare_field_data_mapping_AOR_MEDEX', $form_state->getValue('medicare_field_data_mapping_AOR_MEDEX'))
      ->set('medicare_field_data_mapping_legal_rep', $form_state->getValue('medicare_field_data_mapping_legal_rep'))
      ->set('medicare_field_data_mapping_legal_rep_doc_sections', $form_state->getValue('medicare_field_data_mapping_legal_rep_doc_sections'))
      ->set('medicare_field_data_error_confirm_msg', $form_state->getValue('medicare_field_data_error_confirm_msg'))
      ->set('medicare_field_append_zeros_to_memberid', $form_state->getValue('medicare_field_append_zeros_to_memberid'))
      ->set('medicare_field_data_mapping_campaign', $form_state->getValue('medicare_field_data_mapping_campaign'))
      ->set('medicare_field_data_mapping_seminar', $form_state->getValue('medicare_field_data_mapping_seminar'))
      ->set('medicare_field_data_mapping_webinar', $form_state->getValue('medicare_field_data_mapping_webinar'))
      ->set('medicare_field_data_mapping_planchange', $form_state->getValue('medicare_field_data_mapping_planchange'))
      ->set('medicare_field_data_mapping_addresschange', $form_state->getValue('medicare_field_data_mapping_addresschange'))
      ->set('medicare_field_data_mapping_request_a_call', $form_state->getValue('medicare_field_data_mapping_request_a_call'))
      ->set('medicare_field_data_mapping_billchange', $form_state->getValue('medicare_field_data_mapping_billchange'))
      ->save();
  }

}

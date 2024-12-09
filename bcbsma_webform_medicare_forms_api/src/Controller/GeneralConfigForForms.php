<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Controller;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class GeneralConfigForForms extends ConfigFormBase {
  /**
   * Config settings.
   * */
  const SETTINGS = 'bcbsma_webform_medicare_forms_api.general_config';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bcbsma_webform_medicare_forms_api_general_config';
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
    $form['date_timestamp_update_js_add'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Add medicare-admin-form-update.js file to below paths'),
      '#default_value' => $config->get('date_timestamp_update_js_add'),
    ];
    $form['hide_error_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Add hide_error_message.css file to below paths'),
      '#description' => $this->t('Status message block will be hidden in above paths'),
      '#default_value' => $config->get('hide_error_message'),
    ];
    $form['keys_to_tokenize'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of keys to be tokenized'),
      '#default_value' => $config->get('keys_to_tokenize'),
      '#description' => $this->t('Should be "Key_ID:Token Title", Token Title - Can be a random text. Key_ID - Is the ID of Key(admin/config/system/keys)'),
    ];
    $form['enable_address_field_in_campaign'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Address Field in Campaign page'),
      '#default_value' => $config->get('enable_address_field_in_campaign'),
      '#description' => $this->t('Disable address fields on - Medicare Choices Campaign fields.'),
    ];
    $form['seminar'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Seminar'),
    ];
    $form['seminar']['inperson_seminar_field_update'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty Location and Info fields - for Online seminars'),
      '#default_value' => $config->get('inperson_seminar_field_update'),
      '#description' => $this->t('Online Taxonomy ID(of type "Seminar Availability")'),
    ];
    $form['seminar']['seminar_almost_full_percentage'] = [
      '#type' => 'number',
      '#title' => $this->t('Percentage to show "Seminar Almost full"'),
      '#default_value' => $config->get('seminar_almost_full_percentage'),
      '#description' => $this->t('Shows seminar almost full based on above settings.")'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('bcbsma_webform_medicare_forms_api.general_config')
      ->set('date_timestamp_update_js_add', $form_state->getValue('date_timestamp_update_js_add'))
      ->set('keys_to_tokenize', $form_state->getValue('keys_to_tokenize'))
      ->set('enable_address_field_in_campaign', $form_state->getValue('enable_address_field_in_campaign'))
      ->set('inperson_seminar_field_update', $form_state->getValue('inperson_seminar_field_update'))
      ->set('hide_error_message', $form_state->getValue('hide_error_message'))
      ->set('seminar_almost_full_percentage', $form_state->getValue('seminar_almost_full_percentage'))
      ->save();
  }

}

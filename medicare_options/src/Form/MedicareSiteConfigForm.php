<?php

/**
 * @file
 * Medicare site configuration page
 *
 * @codingStandardsIgnoreFile
 */


namespace Drupal\medicare_options\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implement Medicare Site configuration page.
 */
class MedicareSiteConfigForm extends ConfigFormBase {

  const SETTINGS = 'medicare_options.site_config_form';

  /**
   * Implement getFormId.
   */
  public function getFormId(): string {
    return 'medicare_options_site_config_form';
  }

  /**
   * Implement getEditableConfigNames.
   */
  protected function getEditableConfigNames(): array {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * Implemnet buildForm.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::SETTINGS);

    $form['year_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#options' => ['dual' => $this->t('Dual Year'), '' => $this->t('Single')],
      '#description' => $this->t('Is the Site in Dual Year Mode'),
      '#default_value' => $config->get('year_type'),
    ];

    $form['month'] = [
      '#type' => 'number',
      '#title' => $this->t('Set Month to validate Dual year.'),
      '#description' => $this->t('By default this field should be empty. only when we are validating Dual year site before oct we can add number 10.'),
      '#default_value' => $config->get('month'),
    ];

    $form['redirect_include'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Include Redirect'),
      '#description' => $this->t('List all the Relatives path that needs to redirect. if zipcode is not available. New line for each path'),
      '#default_value' => $config->get('redirect_include'),
    ];

    $form['redirect_to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect to'),
      '#description' => $this->t('path to which we have to redirect.'),
      '#default_value' => $config->get('redirect_to'),
    ];

    $form['zipcode_redirect_to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plans overview page'),
      '#description' => $this->t('Redirect from enter zipcode page to overview page.'),
      '#default_value' => $config->get('zipcode_redirect_to'),
    ];

    $form['zipcode_user_agent'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List all User Agents to allow to plan pages.'),
      '#description' => $this->t('List user agent in newline to allow to see plan pages.'),
      '#default_value' => $config->get('zipcode_user_agent'),
    ];

    $form['filter_retain_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Filter Page Urls'),
      '#description' => $this->t('page Urls where we want to retain Filters'),
      '#default_value' => $config->get('filter_retain_urls'),
    ];

    $form['prescription_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prescription Enroll Now url'),
      '#description' => $this->t('Prescription Enroll Now url'),
      '#default_value' => $config->get('prescription_url'),
    ];

    $form['drug_search_service'] = array(
      '#type' => 'checkbox',
      '#title' => t('Drug Service'),
      '#default_value' => $config->get('drug_search_service')
    );

    $form['drug_search_page_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drug Search Page URL'),
      '#description' => $this->t('Drug Search Page URL'),
      '#default_value' => $config->get('drug_search_page_url'),
    ];

    $form['doctor_search_service'] = array(
      '#type' => 'checkbox',
      '#title' => t('Doctor Service'),
      '#default_value' => $config->get('doctor_search_service')
    );

    $form['doctor_search_page_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Doctor Search Page URL'),
      '#description' => $this->t('Doctor Search Page URL'),
      '#default_value' => $config->get('doctor_search_page_url'),
    ];
    $form['maps_service_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Autocomplet/distance Service Base Path'),
      '#description' => $this->t('Autocomplet/distance Service Base Path'),
      '#default_value' => $config->get('maps_service_url'),
    );
    $form['drug_search_service_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Drug Search Service Base Path'),
      '#description' => $this->t('Drug Search Service Base Path'),
      '#default_value' => $config->get('drug_search_service_url'),
    );
    $form['doctor_search_service_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Doctor Search Service Base Path'),
      '#description' => $this->t('Doctor Search Service Base Path'),
      '#default_value' => $config->get('doctor_search_service_url'),
    );

     $form['local_data_clear'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Doctor & Drug local data clear dates'),
      '#description' => $this->t('On the dates mentioned we will clear Doctor & drug info from user browsers.'),
      '#default_value' => $config->get('local_data_clear'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implement submitForm.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(static::SETTINGS);
    $config->set('year_type', $form_state->getValue('year_type'));
    $config->set('month', $form_state->getValue('month'));
    $config->set('redirect_include', $form_state->getValue('redirect_include'));
    $config->set('redirect_to', $form_state->getValue('redirect_to'));
    $config->set('zipcode_redirect_to', $form_state->getValue('zipcode_redirect_to'));
    $config->set('zipcode_user_agent', $form_state->getValue('zipcode_user_agent'));
    $config->set('filter_retain_urls', $form_state->getValue('filter_retain_urls'));
    $config->set('prescription_url', $form_state->getValue('prescription_url'));
    $config->set('drug_search_service', $form_state->getValue('drug_search_service'));
    $config->set('drug_search_page_url', $form_state->getValue('drug_search_page_url'));
    $config->set('doctor_search_service', $form_state->getValue('doctor_search_service'));
    $config->set('doctor_search_page_url', $form_state->getValue('doctor_search_page_url'));
    $config->set('maps_service_url', $form_state->getValue('maps_service_url'));
    $config->set('drug_search_service_url', $form_state->getValue('drug_search_service_url'));
    $config->set('doctor_search_service_url', $form_state->getValue('doctor_search_service_url'));
    $config->set('local_data_clear', $form_state->getValue('local_data_clear'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

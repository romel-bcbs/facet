<?php

namespace Drupal\bcbsma_chat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class CiscoSettingsForm extends ConfigFormBase {

  /**
   * Declaring Const.
   *
   * @var string Config settings
   */
  const SETTINGS = 'bcbsma_chat.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cisco_chat_admin_settings';
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
    $options = [
      'stage' => $this->t('Staging'),
      'prod' => $this->t('Production'),
    ];
    $form['bcbsma_chat_api_environment'] = [
      '#type' => 'select',
      '#title' => $this->t('CHAT API ENVIRONMENT'),
      '#options' => $options,
      '#default_value' => $config->get('bcbsma_chat_api_environment'),
    ];

    $form['bcbsma_chat_api_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CHAT API URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('bcbsma_chat_api_url'),
    ];
    $form['bcbsma_chat_base_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CHAT BASE URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('bcbsma_chat_base_url'),
    ];
    $form['bcbsma_chat_template_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CHAT TEMPLATE URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('bcbsma_chat_template_url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('bcbsma_chat_api_environment') == 'prod' && strpos($form_state->getValue('bcbsma_chat_api_url'), 'staging') != FALSE) {
      $form_state->setErrorByName('bcbsma_chat_api_url', $this->t('The CHAT API URL should be production URL'));
    }
    if ($form_state->getValue('bcbsma_chat_api_environment') == 'stage' && strpos($form_state->getValue('bcbsma_chat_api_url'), 'staging') != TRUE) {
      $form_state->setErrorByName('bcbsma_chat_api_url', $this->t('The CHAT API URL should be staging URL'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('bcbsma_chat_api_environment', $form_state->getValue('bcbsma_chat_api_environment'))
      ->set('bcbsma_chat_api_url', $form_state->getValue('bcbsma_chat_api_url'))
      ->set('bcbsma_chat_base_url', $form_state->getValue('bcbsma_chat_base_url'))
      ->set('bcbsma_chat_template_url', $form_state->getValue('bcbsma_chat_template_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

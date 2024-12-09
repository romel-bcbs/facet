<?php

namespace Drupal\bcbsma_chat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class AmeliaSettingForm extends ConfigFormBase {

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
    return 'amelia_chat_admin_settings';
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

    $form['iframe_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amelia Domain URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('iframe_domain'),
      '#description' => $this->t('Here we add only iframe Domain.'),
    ];

    $form['iframe_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Amelia Iframe URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('iframe_url'),
      '#description' => $this->t('Here we add only iframe Url For Api keys please check Keys entity.'),
    ];

    $form['amelia_queueCode'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Amelia QueueCode'),
      '#required' => TRUE,
      '#default_value' => $config->get('amelia_queueCode'),
      '#description' => $this->t('Queue Coode for Amelia which is different for site specific.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('iframe_domain', $form_state->getValue('iframe_domain'))
      ->set('iframe_url', $form_state->getValue('iframe_url'))
      ->set('amelia_queueCode', $form_state->getValue('amelia_queueCode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

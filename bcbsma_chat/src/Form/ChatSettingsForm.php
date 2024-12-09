<?php

namespace Drupal\bcbsma_chat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class ChatSettingsForm extends ConfigFormBase {

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
    return 'bcbsma_chat_admin_settings';
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

    $form['chat_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#options' => [
        'cisco' => $this->t('Cisco Chat'),
        'amelia' => $this->t('Amelia Chat'),
      ],
      '#description' => $this->t('Select type of chat want to enable'),
      '#default_value' => $config->get('chat_type'),
      '#required' => TRUE,
    ];

    $form['bcbsma_chat_start_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CHAT START TIME(H:i)'),
      '#required' => TRUE,
      '#default_value' => $config->get('bcbsma_chat_start_time'),
    ];

    $form['bcbsma_chat_end_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CHAT END TIME(H:i)'),
      '#required' => TRUE,
      '#default_value' => $config->get('bcbsma_chat_end_time'),
    ];
    $form['bcbsma_chat_sunday'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BcbsMA Sunday (// 0 means sunday, 6 means saturday)'),
      '#required' => TRUE,
      '#default_value' => $config->get('bcbsma_chat_sunday'),
    ];
    $form['bcbsma_chat_saturday'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BcbsMA Saturday'),
      '#required' => TRUE,
      '#default_value' => $config->get('bcbsma_chat_saturday'),
    ];
    $form['bcbsma_chat_page_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CHAT PAGE URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('bcbsma_chat_page_url'),
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
      ->set('bcbsma_chat_start_time', $form_state->getValue('bcbsma_chat_start_time'))
      ->set('bcbsma_chat_end_time', $form_state->getValue('bcbsma_chat_end_time'))
      ->set('bcbsma_chat_page_url', $form_state->getValue('bcbsma_chat_page_url'))
      ->set('bcbsma_chat_sunday', $form_state->getValue('bcbsma_chat_sunday'))
      ->set('bcbsma_chat_saturday', $form_state->getValue('bcbsma_chat_saturday'))
      ->set('chat_type', $form_state->getValue('chat_type'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

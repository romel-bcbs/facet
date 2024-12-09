<?php

namespace Drupal\medicare_options\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class AdobeTagsForm extends ConfigFormBase {

  /**
   * Declaring Const.
   *
   * @var string Config settings
   */
  const SETTINGS = 'medicare_options.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adobe_tags_admin_settings';
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

    $form['adobe_taging_prod_script_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Adobe Script prodution URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('adobe_taging_prod_script_url'),
    ];

    $form['adobe_taging_stage_script_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Adobe Script stage URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('adobe_taging_stage_script_url'),
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
      ->set('adobe_taging_prod_script_url', $form_state->getValue('adobe_taging_prod_script_url'))
      ->set('adobe_taging_stage_script_url', $form_state->getValue('adobe_taging_stage_script_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

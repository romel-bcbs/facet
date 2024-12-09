<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Controller;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class AdobeTag extends ConfigFormBase {
  /**
   * Config settings.
   * */
  const SETTINGS = 'bcbsma_webform_medicare_forms_api.adobe_tag';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bcbsma_webform_medicare_forms_api_adobe_tag';
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
    $form['adobe_taging'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Adobe Taging'),
    ];
    $form['adobe_taging']['adobe_taging_prod_script_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Adobe Script Prodution URL'),
      '#required' => TRUE,
      '#rows' => 3,
      '#default_value' => $config->get('adobe_taging_prod_script_url'),
    ];
    $form['adobe_taging']['adobe_taging_stage_script_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Adobe Script Stage URL'),
      '#required' => TRUE,
      '#rows' => 3,
      '#default_value' => $config->get('adobe_taging_stage_script_url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('bcbsma_webform_medicare_forms_api.adobe_tag')
      ->set('adobe_taging_prod_script_url', $form_state->getValue('adobe_taging_prod_script_url'))
      ->set('adobe_taging_stage_script_url', $form_state->getValue('adobe_taging_stage_script_url'))
      ->save();
  }

}

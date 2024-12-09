<?php

namespace Drupal\bcbsma_tour\Controller;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class OnboardingUsers extends ConfigFormBase {
  /**
   * Config settings.
   * */
  const SETTINGS = 'bcbsma_tour.general_config';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bcbsma_tour_general_config';
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
    $form['advantage_plan_popup_message_desktop'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Medicare Plan Popup Message - Desktop'),
      '#default_value' => $config->get('advantage_plan_popup_message_desktop'),
    ];
    $form['advantage_plan_popup_message_mobile'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Medicare Plan Popup Message - Mobile'),
      '#default_value' => $config->get('advantage_plan_popup_message_mobile'),
    ];
    $form['library_page_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Library Page Mapping'),
      '#default_value' => $config->get('library_page_mapping'),
      '#description' => $this->t('Format: Path or URL alias|||Library'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config(static::SETTINGS)
      ->set('advantage_plan_popup_message_desktop', $form_state->getValue('advantage_plan_popup_message_desktop'))
      ->set('advantage_plan_popup_message_mobile', $form_state->getValue('advantage_plan_popup_message_mobile'))
      ->set('library_page_mapping', $form_state->getValue('library_page_mapping'))
      ->save();
  }

}

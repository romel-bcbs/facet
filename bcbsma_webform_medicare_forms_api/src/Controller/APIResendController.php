<?php

namespace Drupal\bcbsma_webform_medicare_forms_api\Controller;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure example settings for this site.
 */
class APIResendController extends ConfigFormBase {
  /**
   * Config settings.
   * */
  const SETTINGS = 'bcbsma_webform_medicare_forms_api.data_resend_settings';

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bcbsma_webform_medicare_forms_api_data_resend_settings';
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
    $form['medicare_field_data_resend_settings_view'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Webform field to API data mapping - AOR_MAPD(authorized_representative_form)'),
      '#default_value' => $config->get('medicare_field_data_resend_settings_view'),
    ];
    $from_type_value['AOR_MAPD'] = "AOR_MAPD";
    $from_type_value['AOR_MEDEX'] = "AOR_MEDEX";
    $form['medicare_field_data_resend_from_type'] = [
      '#title' => $this->t('Form Type'),
      '#type' => 'select',
      '#default_value' => $config->get('medicare_field_data_resend_from_type'),
      '#options' => $from_type_value,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $logger = $this->logger('medicare_field_data_resend_settings_view');
    if ($form_state->getValue('medicare_field_data_resend_settings_view')) {
      $view = Views::getView(trim($form_state->getValue('medicare_field_data_resend_settings_view')));
      $logger->notice('coming - view name given');
    }
    else {
      $view = Views::getView('medicare_field_data_resend_settings_view');
    }
    $view->setDisplay('page_1');
    $view->execute();
    foreach ($view->result as $val) {
      if (isset($val->sid)) {
        $logger->notice('Sid : @sid', ['@sid' => $val->sid]);
        $webform_submission = WebformSubmission::load($val->sid);
        $webform_submission->save();
      }
    }
    parent::submitForm($form, $form_state);
    $this->config('bcbsma_webform_medicare_forms_api.data_resend_settings')
      ->set('medicare_field_data_resend_settings_view', $form_state->getValue('medicare_field_data_resend_settings_view'))
      ->set('medicare_field_data_resend_from_type', $form_state->getValue('medicare_field_data_resend_from_type'))
      ->save();
  }

}

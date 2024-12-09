<?php

namespace Drupal\bcbsma_medicare_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implement Medicare Site configuration page.
 */
class SearchConfigForm extends ConfigFormBase {

  const SETTINGS = 'search.site_config_form';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'search_site_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::SETTINGS);

    $form['search_results_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Results'),
      '#description' => $this->t('path to which we have Search results custom Element placed. ex: internal:/site/search'),
      '#default_value' => $config->get('search_results_page'),
    ];

    $form['site_a_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API END PONT FOR SITE A'),
      '#description' => $this->t('Endpoint for Site A Search API.'),
      '#default_value' => $config->get('site_a_endpoint'),
    ];

    $form['site_b_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API END PONT FOR SITE B'),
      '#description' => $this->t('Endpoint for Site B Search API.'),
      '#default_value' => $config->get('site_b_endpoint'),
    ];

    $form['results_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rsults Limit'),
      '#description' => $this->t('Number of results want to limit'),
      '#default_value' => $config->get('results_limit'),
    ];

    $form['authorization'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authorization Key'),
      '#description' => $this->t('Anuthorization key is only when shield is enables else should be empty.'),
      '#default_value' => $config->get('authorization'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(static::SETTINGS);
    $config->set('search_results_page', $form_state->getValue('search_results_page'));
    $config->set('site_a_endpoint', $form_state->getValue('site_a_endpoint'));
    $config->set('site_b_endpoint', $form_state->getValue('site_b_endpoint'));
    $config->set('results_limit', $form_state->getValue('results_limit'));
    $config->set('authorization', $form_state->getValue('authorization'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

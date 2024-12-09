<?php

namespace Drupal\bcbsma_seminar\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Past/Present/Future event filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date_range_picker_filter")
 */
class DateRangePickerFilter extends FilterPluginBase {

  /**
   * The form that is shown (including the exposed form).
   *
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#tree' => TRUE,
    ];
    $form['value']['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start date'),
      '#default_value' => !empty($this->value['start_date']) ? $this->value['start_date'] : '',
    ];
    $form['value']['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End date'),
      '#default_value' => !empty($this->value['end_date']) ? $this->value['end_date'] : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    parent::valueSubmit($form, $form_state);
    $start_date = $form_state->getValue('start_date');
    $form_state->setValue('start_date', $start_date);
    $end_date = $form_state->getValue('end_date');
    $form_state->setValue('end_date', $end_date);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['start_date'] = ['default' => NULL];
    $options['end_date'] = ['default' => NULL];
    return $options;
  }

  /**
   * Applying query filter.
   *
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $logger = \Drupal::logger('Date Range Filter - Query');
    if ($this->value['start_date'] && $this->value['end_date']) {
      $val['0'] = strtotime($this->value['start_date'] . " 00:00:00");
      $val['1'] = strtotime($this->value['end_date'] . " 23:59:59");
      $logger->notice("Start Date: " . $val['0'] . " End Date: " . $val['1']);
      if ($this->query instanceof SearchApiQuery) {
        $this->query->addCondition('field_seminar_date', $val, 'BETWEEN');
      }
    }
  }

  /**
   * Admin summary makes it nice for editors.
   *
   * {@inheritdoc}
   */
  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      if ($this->value['start_date'] || $this->value['end_date']) {
        return $this->t('Exposed, default value: :start_date and :end_date', [
          ':start_date' => $this->value['start_date'],
          ':end_date' => $this->value['end_date'],
        ]);
      }
      return $this->t('Exposed, default value: NA');
    }
    else {
      if (is_array($this->value)) {
        return $this->t('Start date: :start_date and, End date: :end_date', [
          ':start_date' => $this->value['start_date'],
          ':end_date' => $this->value['end_date'],
        ]);
      }
      return $this->t('Date Range');
    }
  }

}

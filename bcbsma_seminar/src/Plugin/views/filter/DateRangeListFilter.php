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
 * @ViewsFilter("date_range_list_filter")
 */
class DateRangeListFilter extends FilterPluginBase {

  /**
   * The form that is shown (including the exposed form).
   *
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#tree' => TRUE,
      'date_range_list' => [
        '#type' => 'radios',
        '#title' => $this->t('Date Range List'),
        '#options' => [
          'all' => $this->t('All'),
          'next_30_days' => $this->t('Next 30 Days'),
          'next_60_days' => $this->t('Next 60 Days'),
          'next_90_days' => $this->t('Next 90 Days'),
          'custom_date_range' => $this->t('Custom Date Range'),
        ],
        '#default_value' => !empty($this->value['date_range_list']) ? $this->value['date_range_list'] : 'all',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    parent::valueSubmit($form, $form_state);
    $key = ['options', 'value', 'date_range_list'];
    $real_value = $form_state->getValue($key);
    $logger = \Drupal::logger('date_range_list1');
    $logger->notice(print_r($real_value, TRUE));
    $form_state->setValue('date_range_list', $real_value);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['date_range_list'] = ['default' => NULL];
    return $options;
  }

  /**
   * Applying query filter.
   *
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $val['0'] = strtotime("now");
    $val['1'] = strtotime("+1 week");
    switch ($this->value['date_range_list']) {
      case 'next_30_days':
        $val['1'] = strtotime("+1 month");
        break;

      case 'next_60_days':
        $val['1'] = strtotime("+2 month");
        break;

      case 'next_90_days':
        $val['1'] = strtotime("+3 month");
        break;
    }
    $range = [
      "next_90_days",
      "next_60_days",
      "next_30_days",
    ];
    if ($this->query instanceof SearchApiQuery && in_array($this->value['date_range_list'], $range)) {
      $this->query->addCondition('field_seminar_date', $val, 'BETWEEN');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {

    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      if (isset($this->value['start_date']) && isset($this->value['end_date'])) {
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
      return $this->t('Date List Range');
    }
  }

}

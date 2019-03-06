<?php

namespace Drupal\civicrm_views\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Date;

/**
 * Wraps the 'date' operator to match CiviCRMs.
 *
 * @ingroup views_filter_handlers
 * @ViewsFilter("civicrm_date_operator")
 */
class CivicrmDateOperator extends Date {

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    $a = intval(strtotime($this->value['min'], 0));
    $b = intval(strtotime($this->value['max'], 0));

    if ($this->value['type'] == 'offset') {
      // Keep sign.
      $a = date("Y-m-d H:i:s", time() + sprintf('%+d', $a));
      // Keep sign.
      $b = date("Y-m-d H:i:s", time() + sprintf('%+d', $b));
    }
    $operator = strtoupper($this->operator);
    $this->query->addWhereExpression($this->options['group'], "$field $operator '$a' AND '$b'");
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    $value = $this->value['value'];
    if (!empty($this->value['type']) && $this->value['type'] == 'offset') {
      // Convert date to ISO standard date format "Y-m-d H:i:s".
      $value = intval(strtotime($value, 0));
      $value = date("Y-m-d H:i:s", time() + sprintf('%+d', $value));
    }
    $this->query->addWhereExpression($this->options['group'], "$field $this->operator '$value'");
  }

}

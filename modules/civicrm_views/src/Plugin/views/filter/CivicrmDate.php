<?php

namespace Drupal\civicrm_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Date;

/**
 * Filter to handle dates stored as a formatted date string but compared as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("civicrm_date")
 */
class CivicrmDate extends Date {
    public function query() {
        $this->ensureMyTable();
        $field = "$this->tableAlias.$this->realField";
        $field = "UNIX_TIMESTAMP($field)";

        $info = $this->operators();
        if (!empty($info[$this->operator]['method'])) {
            $this->{$info[$this->operator]['method']}($field);
        }
    }
}
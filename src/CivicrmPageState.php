<?php

namespace Drupal\civicrm;

/**
 * The page state service.
 */
class CivicrmPageState {

  /**
   * The page title.
   *
   * @var string
   */
  protected $title = '';

  /**
   * An array of css files.
   *
   * @var string[]
   */
  protected $css = [];

  /**
   * An array of js files.
   *
   * @var string[]
   */
  protected $js = [];

  /**
   * An array of breadcrumb items.
   *
   * @var string[]
   */
  protected $breadcrumbs = [];

  /**
   * Contains a flag if access to the page is allowed.
   *
   * @var bool
   */
  protected $accessDenied = FALSE;

  /**
   * An array of additional items for the html HEAD section.
   *
   * @var string[]
   */
  protected $htmlHeaders = [];

  /**
   * Sets the page title.
   *
   * @param string $title
   *   The page title.
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * Returns the page title.
   *
   * @return string
   *   The page title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Adds extra css files.
   *
   * @todo Are we sure this needs to be an array, this is different from ::addJs
   * for no reason?
   *
   * @param string[] $css
   *   A list of css files to add.
   */
  public function addCSS(array $css) {
    $this->css[] = $css;
  }

  /**
   * Returns all css files.
   *
   * @return string[]
   *   A list of css files.
   */
  public function getCSS() {
    return $this->css;
  }

  /**
   * Adds extra js files.
   *
   * @param string[] $script
   *   A list of js files to add.
   */
  public function addJS($script) {
    $this->js[] = $script;
  }

  /**
   * Returns all js files.
   *
   * @return string[]
   *   A list of js files.
   */
  public function getJS() {
    return $this->js;
  }

  /**
   * Add a breadcrumb item.
   *
   * @param string $name
   *   The title of the breadcrumb item.
   * @param string $url
   *   The url the item should link to.
   */
  public function addBreadcrumb($name, $url) {
    $this->breadcrumbs[$name] = $url;
  }

  /**
   * Reset the breadcrumbs back to an empty list.
   */
  public function resetBreadcrumbs() {
    $this->breadcrumbs = [];
  }

  /**
   * Returns all breadcrumb items.
   *
   * @return string[]
   *   A list of breadcrumb urls, keyed by name.
   */
  public function getBreadcrumbs() {
    return $this->breadcrumbs;
  }

  /**
   * Add html header items.
   *
   * @param string $html
   *   Add another item to the html head section.
   */
  public function addHtmlHeader($html) {
    $this->htmlHeaders[] = $html;
  }

  /**
   * Returns a list of header items.
   *
   * @return string[]
   *   A list of items to add to the html head section.
   */
  public function getHtmlHeaders() {
    return implode(' ', $this->htmlHeaders);
  }

  /**
   * Set the page to deny access.
   */
  public function setAccessDenied() {
    $this->accessDenied = TRUE;
  }

  /**
   * Returns the access state for this page.
   *
   * @return bool
   *   Returns TRUE when access is denied.
   */
  public function isAccessDenied() {
    return $this->accessDenied;
  }

}

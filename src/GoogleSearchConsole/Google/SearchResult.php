<?php
/**
 * Google Custom web search script and PDF writer.
 *
 * @file Search result class, a wrapper around
 *       Google_Service_Customsearch_Result with our own auto-complete methods
 *       and a thumbnail retriever.
 */

namespace GoogleSearchConsole\Google;

/**
 * Class SearchResult
 *
 * @package GoogleSearchConsole
 */
class SearchResult {
  /**
   * @var \Google_Service_Customsearch_Result
   */
  protected $search_result_item;

  /**
   * convert a string to 'windows-1252' charset (compatable with PDF)
   * @param $string
   *
   * @return string
   */
  protected function iconv($string) {
    return iconv('UTF-8', 'windows-1252', stripslashes($string));
  }

  public function __construct(\Google_Service_Customsearch_Result $search_result_item) {
    $this->search_result_item = $search_result_item;
  }

  /**
   * @param \Google_Service_Customsearch_Result $search_result_item
   *
   * @return SearchResult
   */
  public static function factory(\Google_Service_Customsearch_Result $search_result_item) {
    $class = __CLASS__;
    return new $class($search_result_item);
  }

  /**
   * Get the page map of the item.
   *
   * @return mixed
   */
  public function getPagemap() {
    return $this->search_result_item->getPagemap();
  }

  /**
   * Get a URL of thumbnail image of the results if available.
   * @return string
   */
  public function getThumpnailUrl() {
    $page_map = $this->search_result_item->getPagemap();
    return isset($page_map['cse_thumbnail'][0]['src']) ? $page_map['cse_thumbnail'][0]['src'] : '';
  }

  /**
   * Get the width in px of thumbnail image of the results if available.
   * @return string
   */
  public function getThumpnailWidth() {
    $page_map = $this->search_result_item->getPagemap();
    return isset($page_map['cse_thumbnail'][0]['width']) ? $page_map['cse_thumbnail'][0]['width'] : '';
  }

  /**
   * Get the height in px of thumbnail image of the results if available.
   * @return string
   */
  public function getThumpnailHeight() {
    $page_map = $this->search_result_item->getPagemap();
    return isset($page_map['cse_thumbnail'][0]['height']) ? $page_map['cse_thumbnail'][0]['height'] : '';
  }

  /**
   * Get title of Search Result Item.
   *
   * @return mixed
   */
  public function getTitle($to_utf8 = FALSE) {
    return $to_utf8 ? $this->iconv($this->search_result_item->getTitle()) : $this->search_result_item->getTitle();
  }

  /**
   * Get link of Search Result Item.
   *
   * @return mixed
   */
  public function getLink($to_utf8 = FALSE) {
    return $to_utf8 ? $this->iconv($this->search_result_item->getLink()) : $this->search_result_item->getLink();
  }

  /**
   * Get snippet of Search Result Item.
   *
   * @return mixed
   */
  public function getSnippet($to_utf8 = FALSE) {
    return $to_utf8 ? $this->iconv($this->search_result_item->getSnippet()) : $this->search_result_item->getSnippet();
  }

}

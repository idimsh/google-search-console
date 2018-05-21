<?php
/**
 * Google Custom web search script and PDF writer.
 *
 * @file Search class, a wrapper around Google_Service_Customsearch
 */

namespace GoogleSearchConsole\Google;

use \GoogleSearchConsole\Cli\Ops as CliOps;

class Search {
  protected $api_client = NULL;

  /**
   * @return Search
   */
  public static function factory() {
    $class = __CLASS__;
    return new $class;
  }

  /**
   * Get \Google_Client instance initialized with API Key defined in config.
   *
   * @return \Google_Client|null
   * @throws \Exception
   */
  public function getApiClient() {
    if (!$this->api_client) {
      if (!CliOps::getApiKey()) {
        throw new \Exception("API Key is not defined, update configuration file");
      }
      $this->api_client = new \Google_Client();
      $this->api_client->setDeveloperKey(CliOps::getApiKey());
      if (CliOps::isNoVerify()) {
        // add special HTTP client which does not require verify of SSL certificates if requested.
        $this->api_client->setHttpClient(new \GuzzleHttp\Client(['curl' => [CURLOPT_SSL_VERIFYPEER => FALSE]]));
      }
    }
    return $this->api_client;
  }

  /**
   * @param $search_term
   * @param $number_of_results
   *
   * @return array|\Exception|Results
   */
  public function perform($search_term, $number_of_results) {
    /** Google Custom Search API does not support number of results per search
     * greater than 10, to get more results, we use the 'start' parameter to
     * shift the offset of the results and get 10 at each call.
     */
    $number_of_searches   = $number_of_results > 10 ? ceil($number_of_results / 10) : 1;
    $final_items          = new Results();
    $all_items            = [];
    $current_search_count = 0;

    try {
      if (!CliOps::getSearchEngineId()) {
        throw new \Exception("Search Engine ID is not defined, update configuration file");
      }
      do {
        $current_search_count++;

        $search   = new \Google_Service_Customsearch($this->getApiClient());
        $response = $search->cse->listCse($search_term, [
          'cx'    => CliOps::getSearchEngineId(),
          'start' => 1 + $current_search_count * 10,
//          'gl'    => 'de',
//          'hl'    => 'de',
        ]);
        $items    = $response->getItems();
        if (!count($items)) {
          // no more results, break the while loop.
          break;
        }
        foreach ($items as $item) {
          $all_items[] = SearchResult::factory($item);
          if (count($all_items) >= $number_of_results) {
            // we need only the number of results defined.
            break;
          }
        }
      } while ($current_search_count * 10 < $number_of_results && $number_of_searches-- > 0);
    } catch (\Exception $e) {
      return $e;
    }
    $final_items->setItems($all_items);
    return $final_items;
  }
}

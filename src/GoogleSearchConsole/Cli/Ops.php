<?php
/**
 * Google Custom web search script and PDF writer.
 *
 * @file CLI Operations class definition file
 */

namespace GoogleSearchConsole\Cli;

use GetOpt\GetOpt;
use GetOpt\Option;
use GetOpt\Operand;

class Ops {

  const DEFAULT_API_KEY = 'YOUR_API_KEY';
  const DEFAULT_SE_ID = 'YOUR_SEARCH_ENGINE_ID';

  /**
   * @var \GetOpt\GetOpt
   */
  protected static $getopt = NULL;


  /**
   * Print to STDERR Standard-Error and append and new line at the end.
   *
   * Accepts multiple parameters which will be concatenated with space.
   *
   * @param array ...$input
   */
  public static function print_e(...$input) {
    fwrite(STDERR, implode(' ', $input) . "\n");
  }

  /**
   * Get the completely defined command line options parsing object.
   *
   * @return \GetOpt\GetOpt
   */
  protected static function getGetops() {
    if (NULL === static::$getopt) {
      static::$getopt = new Getopt([
        Option::create('h', 'help', GetOpt::NO_ARGUMENT)
          ->setDescription('Show this help text'),

        Option::create(NULL, 'no-verify', GetOpt::NO_ARGUMENT)
          ->setDescription('Skip SSL certificate verification against Google services, this is required on some WAMP stacks, if the search has failed.'),

        Option::create(NULL, 'thumb', GetOpt::NO_ARGUMENT)
          ->setDescription('Download thumbnails of search results (from metadata) and print them in PDF.'),

        Option::create('o', 'out', GetOpt::REQUIRED_ARGUMENT)
          ->setDescription('Specify the output PDF file name, the default name is taken from <Search term> and <Result limit>.'),
      ]);

      static::$getopt->addOperands([
        Operand::create('Search term')->setName('search_term'),
        Operand::create('Result limit')->setName('limit'),
      ]);

    }
    return static::$getopt;
  }

  /**
   * Parse command line options and parameters and return the parsed array.
   *
   * @return array
   */
  public static function cmdOptionsGet() {
    static $processed;
    if (empty($processed)) {
      try {
        self::getGetops()->process();
      } catch (\Exception $e) {
        static::print_e($e->getMessage());
        exit(1);
      }
      $processed = self::getGetops()->getOptions();
    }
    return $processed;
  }

  /**
   * Parse the command line arguments and process the help, and the validation.
   *
   * This must be called first to ensure everything is OK to proceed with the
   * script.
   */
  public static function cmdOptionsParse() {
    $options = self::cmdOptionsGet();
    if (!empty($options['help'])) {
      echo self::getGetops()->getHelpText();
      exit(0);
    }
    $search_term = self::getGetops()->getOperand('search_term');
    $limit       = self::getGetops()->getOperand('limit');

    if (empty($search_term)) {
      self::print_e("<Search term> is required");
      exit(1);
    }
    if (empty($limit)) {
      self::print_e("<Result limit> is required");
      exit(1);
    }
    if (!is_numeric($limit) || $limit < 1) {
      self::print_e("<Result limit> must be a positive integer, [$limit] is not a valid value.");
      exit(1);
    }
  }

  /**
   * Is parameter no-verify passed.
   *
   * @return bool
   */
  public static function isNoVerify() {
    return !empty(self::cmdOptionsGet()['no-verify']);
  }

  /**
   * Is thumbnail requested
   * @return bool
   */
  public static function isThumbnail() {
    return !empty(self::cmdOptionsGet()['thumb']);
  }

  /**
   * Generate PDF filename from the search term and the limit.
   *
   * @return string
   */
  public static function filenameFromOperand() {
    $ret                  = 'Search[';
    $search_term_santized = preg_replace('@[^A-z0-9_.-]@', '-', self::getSearchTerm());
    $search_term_santized = preg_replace('@--+@', '-', $search_term_santized);
    $ret                  .= $search_term_santized . ']-' . self::getLimit() . '.pdf';
    return $ret;
  }

  /**
   * Get the result PDF filename, either requested as command option or
   * generate it from Search term.
   *
   * @return mixed|string
   */
  public static function getFilename() {
    $from_opt = !empty(self::cmdOptionsGet()['out']) ? self::cmdOptionsGet()['out'] : '';
    return $from_opt ? $from_opt : self::filenameFromOperand();
  }

  /**
   * Get the passed requested search term.
   *
   * @return mixed
   */
  public static function getSearchTerm() {
    return self::getGetops()->getOperand('search_term');
  }

  /**
   * Get the requested result limit.
   *
   * @return mixed
   */
  public static function getLimit() {
    return self::getGetops()->getOperand('limit');
  }

  /**
   * Rerutn an object represent the Keys file JSON pasing, it should include
   * the properties:
   * - api_key
   * - search_engine_id
   *
   * @return \stdClass|NULL
   */
  public static function parseKeysFile() {
    if (is_readable(KEYS_FILES)) {
      $ret = @json_decode(@file_get_contents(KEYS_FILES));
      return is_object($ret) && !empty($ret->api_key) && !empty($ret->search_engine_id) ? $ret : NULL;
    }
    return NULL;
  }

  /**
   * Get the API Key from config file.
   *
   * @return string
   */
  public static function getApiKey() {
    $keys = self::parseKeysFile();
    return !empty($keys->api_key) && $keys->api_key !== self::DEFAULT_API_KEY ? $keys->api_key : '';
  }

  /**
   * Get the Search Engine ID from config file.
   *
   * @return string
   */
  public static function getSearchEngineId() {
    $keys = self::parseKeysFile();
    return !empty($keys->search_engine_id) && $keys->search_engine_id !== self::DEFAULT_SE_ID ? $keys->search_engine_id : '';
  }


  /**
   * Get an answer from user to a question printed before.
   * Example:
   *   echo "\nConfirm Rename? [y/N]? \n";
   *   $line = getAnswer();
   *   if ($line !== 'y') {
   *     echo "exitting ... \n";
   *     exit(0);
   *   }
   *
   *
   * @return string
   */
  public static function getAnswer() {
    if (PHP_OS == 'WINNT') {
      $line = stream_get_line(STDIN, 1024, PHP_EOL);
    }
    else {
      $line = readline('');
    }

    return $line;
  }

}

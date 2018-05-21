#!/usr/bin/env php
<?php
/**
 * Google Custom web search script and PDF writer.
 * A show case project to demonstrate console application options parsing and
 * PDF files generation and saving.
 *
 * To get command line options and help use:
 *
 *   php google-search.php --help
 */

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  die("[vendor/autoload.php] file not found, init the project with 'composer install' command first\n");
}

if (!function_exists('readline')) {
  die("[readline] library (extension) not enabled in this PHP, install the extension first\n");
}

require_once __DIR__ . '/vendor/autoload.php';
const KEYS_FILES = __DIR__ . DIRECTORY_SEPARATOR . 'keys.json';

use \GoogleSearchConsole\Cli\Ops as CliOps;

// this will ensure all parameters are correct, else it will exit.
CliOps::cmdOptionsParse();

$results = \GoogleSearchConsole\Google\Search::factory()
  ->perform(CliOps::getSearchTerm(), CliOps::getLimit());

if ($results instanceof \Exception) {
  CliOps::print_e("Failed to perform search with error:\n[" . $results->getMessage() . "]");
  if (stripos($results->getMessage(), 'SSL') !== FALSE) {
    CliOps::print_e("\ntry with --no-verify command line option if you have errors with SSL\n");
  }

  exit(1);
}

$output = CliOps::getFilename();
if (file_exists($output)) {
  echo "File [{$output}] already exists, overwrite? (y/N) \n";
  $answer = readline('');
  if ($answer !== 'y') {
    CliOps::print_e("\nexiting...\n");
    exit(1);
  }
}

try {
  $pdf = new \GoogleSearchConsole\Pdf\Generator();
  $pdf->AliasNbPages();
  $pdf->AddPage();
  $pdf->writeResults($results);
  if (!file_exists(dirname($output))) {
    @mkdir(dirname($output), 0755, TRUE);
  }
  $pdf->Output('F', $output);
  echo "saved in [$output]\n";
} catch (\Exception $e) {
  CliOps::print_e("Failed to generate PDF file, got error:\n", $e->getMessage());
  exit(1);
}

exit (0);

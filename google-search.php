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


require_once __DIR__ . '/vendor/autoload.php';
const KEYS_FILES = __DIR__ . DIRECTORY_SEPARATOR . 'keys.json';

use \GoogleSearchConsole\Cli\Ops as CliOps;

CliOps::cmdOptionsParse();

$results = \GoogleSearchConsole\Google\Search::factory()
  ->perform(CliOps::getSearchTerm(), CliOps::getLimit());

if ($results instanceof \Exception) {
  CliOps::print_e("Failed to perform search with error:\n[" . $results->getMessage() . "]");
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

$pdf = new \GoogleSearchConsole\Pdf\Generator();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->writeResults($results);
/*$i = 1;
while(($file_name = "my-pdf-{$i}.pdf") && file_exists($file_name) && $i++);*/
$pdf->Output('F', $output);

exit (0);

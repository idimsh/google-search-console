<?php
/**
 * Google Custom web search script and PDF writer.
 *
 * @file PDF Generator Class
 */

namespace GoogleSearchConsole\Pdf;

use \GoogleSearchConsole\Cli\Ops as CliOps;

class Generator extends \FPDF {

  /**
   * A static array of temp files to be deleted on shutdown
   * @var array
   */
  public static $temp_files_array = [];

  /**
   * Registered as shutdown function to delete the temporary files.
   */
  public static function removeTempFiles() {
    foreach (self::$temp_files_array as $key => $file) {
      @unlink($file);
      unset(self::$temp_files_array[$key]);
    }
  }

  /**
   * Generator constructor.
   * Used to register the shutdown function.
   *
   * @see FPDF::__construct()
   *
   * @param string $orientation
   * @param string $unit
   * @param string $size
   */
  public function __construct(string $orientation = 'P', string $unit = 'mm', string $size = 'A4') {
    parent::__construct($orientation, $unit, $size);
    register_shutdown_function(array(__CLASS__, 'removeTempFiles'));
  }

  /**
   * Page Header
   */
  public function Header() {
    if ($this->PageNo() == 1) {
      // Only print the header on first page

      // Print the title
      $this->SetFont('Arial', 'B', 12);
      $this->Write(5, 'Google Custom Search');
      // Line break
      $this->Ln(5);

      // Print the input parameters
      $this->SetFont('Arial', 'B', 10);
      $this->Write(5, 'Search Term: ');
      $this->SetFont('Arial', '', 9);
      $this->Write(5, CliOps::getSearchTerm());
      $this->Ln(5);

      $this->SetFont('Arial', 'B', 10);
      $this->Write(5, 'Results Count Limit: ');
      $this->SetFont('Arial', '', 9);
      $this->Write(5, CliOps::getLimit());

      $this->Ln(10);
    }
  }

  /**
   * Page footer
   */
  public function Footer() {
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    // Arial italic 8
    $this->SetFont('Arial', 'I', 8);
    // Page number
    $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
  }

  protected function writeThumbnailForItem(\GoogleSearchConsole\Google\SearchResult $item, $at_y = NULL) {
    static $context = NULL;
    if (!isset($context)) {
      // Create a stream context for file_get_contents() to allow downloading
      // images without SSL verify (fails on WAMP stack)
      $options = [
        'ssl' => [
          'verify_peer' => FALSE,
        ],
      ];
      $context = stream_context_create($options);
    }

    $image_url     = $item->getThumpnailUrl();
    $image_printed = FALSE;
    if ($image_url) {
      if (NULL !== $at_y) {
        $this->SetY($at_y);
      }

      // if a thumbnail is returned by search
      // 1st: assume it is a PNG image (URLs not always indicate the image type)
      $tmp_file                 = tempnam(sys_get_temp_dir(), __FUNCTION__);
      self::$temp_files_array[] = $tmp_file;
      // FPDF works by file extension
      $tmp1                     = $tmp_file . '.png';
      self::$temp_files_array[] = $tmp1;
      @file_put_contents($tmp1, @file_get_contents($image_url, FALSE, $context));
      if (@filesize($tmp1)) {
        try {
          $this->Image($tmp1, 150, NULL, 16, 0, '', $item->getLink());
          $image_printed = TRUE;
        } catch (\Exception $e) {
          // if assuming PNG type is false, then assume JPG and try again
          $tmp2                     = $tmp_file . '.jpg';
          self::$temp_files_array[] = $tmp2;
          @file_put_contents($tmp2, @file_get_contents($tmp1));
          if (@filesize($tmp2)) {
            try {
              $this->Image($tmp2, 150, NULL, 16, 0, '', $item->getLink());
              $image_printed = TRUE;
            } catch (\Exception $e) {
            }
          }
        }
      }
    }

    return $image_printed;
  }

  public function writeResults(\GoogleSearchConsole\Google\Results $results) {
    $result_number = 0;

    if (count($results->getItems()) == 0) {
      // Print no results and do not continue
      $this->SetFont('Arial', '', 12);
      $this->SetTextColor(0, 0, 0);
      $this->Write(5, 'Your search yields no results!');
      $this->Ln(5);
      return;
    }

    foreach ($results->getItems() as /** @var \GoogleSearchConsole\Google\SearchResult $item */
             $item) {
      $result_number++;

      // save the position Y at this point (to be used to print the image)
      $line_y = $this->GetY();

      // Print Title with color and link
      $this->SetFont('Arial', '', 9);
      $this->Write(5, $result_number . '.  ');
      $this->SetFont('Arial', 'U', 10);
      $this->SetTextColor(0, 0, 255);
      $this->Write(5, $item->getTitle(TRUE), $item->getLink());
      $this->Ln(5);

      // Print the link only
      $this->SetFont('Arial', '', 7);
      $this->SetTextColor(0, 0, 0);
      $this->Write(5, '        ' . $item->getLink());
      $this->Ln(5);

      // Print the snippet (with added space at line start for alignment
      $this->SetFont('Arial', '', 8);
      $this->Write(5, preg_replace('@^@m', '    ', $item->getSnippet()));
      $this->Ln(5);
      $this->Ln(5);

      // save the position Y at this point (to get back to it after printing the image)
      $last_y = $this->GetY();

      if (CliOps::isThumbnail() && $this->writeThumbnailForItem($item, $line_y)) {
        // if image is printed, get back to Y position
        $this->SetY($last_y);
      }
    }
  }

}

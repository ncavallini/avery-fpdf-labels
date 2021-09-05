<?php
/*
  Produced 2019-2021
  By https://amattu.com/github
  Copy Alec M.
  License GNU Affero General Public License v3.0
*/

// Class namespace
namespace amattu;

// Exception Classes
class BadValueException extends Exception {}
class InvalidStateException extends Exception {}

/*
  Avery label interface
 */
interface LabelInterface {
  /**
   * Add a single label to the PDF
   *
   * @param string $label a complete label with lines donoted by \n
   * @param integer $row optional desired insert row
   * @param integer $col optional diesired intert column
   * @return bool success
   * @throws TypeError
   * @throws BadValueException
   * @author Alec M. <https://amattu.com>
   * @date 2021-09-05T13:49:28-040
   */
  public function add(string $label, int $row = 0, int $col = 0) : bool;

  /**
   * Build the completed PDF with labels
   *
   * NOTE:
   *   (1) To save resources, no PDF is built until
   *   this function is called.
   *
   * @return void
   * @throws InvalidStateException
   * @author Alec M. <https://amattu.com>
   * @date 2021-09-05T13:50:58-040
   */
  public function build() : void;
}

/*
 A Avery 5160 label PDF
 */
class Avery_5160 extends FPDF implements LabelInterface {
  /**
   * Represents current PDF state
   *
   * @var int
   */
  protected $open_state = 1;

  /**
   * Holds the labels
   *
   * @var array
   */
  protected $labels = Array();

  /**
   * PDF top margin
   *
   * @var int
   */
  protected $top = 13;

  /**
   * PDF left margin
   *
   * @var int
   */
  protected $left = 5;

  /**
   * Represents the PDF column width
   *
   * @var int
   */
  public const COLUMN_WIDTH = 67;

  /**
   * Represents the PDF maximum number of labels
   *
   * @var int
   */
  public const MAX_LABEL_LINES = 4;

  /**
   * PDF maximum number of columns
   *
   * @var int
   */
  public const COLUMNS = 3;

  /**
   * PDF maximum number of rows
   *
   * @var int
   */
  public const ROWS = 10;

  /**
   * {@inheritdoc}
   */
  public function add(string $string, int $row = -1, int $col = -1) : bool
  {
    // Checks
    if (empty($string) || substr_count($string, "\n") > $this->config_max_linebreak) {
      throw new BadValueException("Label string provided is empty or contains too many lines");
    }
    if ($row < -1 || $col < -1 || ($row + 1) > $this->config_row_count || ($col + 1) > $this->config_col_count) {
      throw new BadValueException("Row or column value specified is invalid");
    }

    // Append
    $this->labels[] = Array(
      "S" => trim($string),
      "R" => $row,
      "C" => $col
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // Checks
    if ($this->open_state != 1) {
      throw new InvalidStateException("Attempt to build onto an existing PDF");
    }

    // Variables
    $bottom = $this->GetPageHeight() - $this->top;
    $right = $this->GetPageWidth() - $this->left;
    $config_row_height = (($bottom - $this->top) / $this->config_row_count);
    $config_items_per_page = $this->config_row_count * $this->config_col_count;
    $current_row = 0;
    $current_col = 0;
    $current_page = 0;
    $current_item_count = 0;

    // Loop
    foreach ($this->labels as $item) {
      // Checks
      if ($current_item_count++ > $config_items_per_page) {
        $this->AddPage("P", "Letter");
        $current_item_count = 1;
        $current_page++;
        $current_col = 0;
        $current_row = 0;
      }
      if ($current_row >= $this->config_row_count) {
        $current_col++;
        $current_row = 0;
      }
      if ($current_col >= $this->config_col_count) {
        $this->AddPage("P", "Letter");
        $current_item_count = 1;
        $current_page++;
        $current_col = 0;
        $current_row = 0;
      }
      if ($item["R"] > $this->config_row_count || $item["R"] < 0) {
        $item["R"] = $current_row++;
      }
      if ($item["C"] > $this->config_col_count || $item["C"] < 0) {
        $item["C"] = $current_col;
      }

      // Build Item
      $this->setY(($item["R"] > 0 ? $this->top + ($config_row_height * $item["R"]) + 2 : $this->top + 2));
      $this->setX(($item["C"] > 0 ? $this->left + ($this->config_col_width * $item["C"]) + (3 * $item["C"]) : $this->left));
      $this->MultiCell($this->config_col_width, ($config_row_height / 3.5), $item["S"], false, "C");
    }

    // Close PDF
    $this->open_state = 0;
  }
}
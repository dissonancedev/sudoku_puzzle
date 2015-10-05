<?php
/**
 * @file
 * Sudoku puzzle class.
 *
 * This class is a unit that represents a simple 9x9 SuDoKu puzzle.
 * It has the ability to generate unique-solvable puzzles with adjustable
 * difficulty as well as solve given/generated puzzles of all difficulties.
 *
 * Much of the logic and algorithms were inspired/taken from this paper:
 * http://zhangroup.aporc.org/images/files/Paper_3485.pdf
 *
 * @author Nikos Parastatidis <nparasta@gmail.com>
 */

// Define difficulty levels.
define('LEVEL_VERY_EASY', 1);
define('LEVEL_EASY', 2);
define('LEVEL_MEDIUM', 3);
define('LEVEL_DIFFICULT', 4);
define('LEVEL_EVIL', 5);

class SudokuPuzzle {
  /**
   * Represents the whole sudoku puzzle board.
   */
  protected $puzzle;

  /**
   * Represents the locked version sudoku puzzle board.
   */
  protected $lockedPuzzle;

  /**
   * Loop threshold for the terminal pattern algorithm.
   * Makes sure it doesn't fall in infinite loop.
   */
  protected $loopThreshold;

  /**
   * It stores a measure of difficulty of the puzzle.
   */
  protected $searchEnumeration;

  /* SETUP FUNCTIONS */

  /**
   * SudokuPuzzle constructor.
   *
   * Initializes board.
   *
   * @ingroup setup
   */
  public function __construct($threshold = 100) {
    $this->clearPuzzle();
    $this->loopThreshold = $threshold;
  }

  /**
   * Clears puzzle.
   *
   * Clears the puzzle by setting all cells to 0.
   *
   * @ingroup setup
   */
  protected function clearPuzzle() {
    $keys = array(1, 2, 3, 4, 5, 6, 7, 8 , 9);

    $this->puzzle = array();
    for ($i = 1; $i <= 9; $i++) {
      $this->puzzle[$i] = array_fill_keys($keys, 0);
    }
  }

  /**
   * Get loop threshold.
   *
   * Returns the loop threshold value.
   *
   * @return int
   *   The currently set loop threshold.
   *
   * @ingroup setup
   */
  public function getLoopThreshold() {
    return $this->loopThreshold;
  }

  /**
   * Set loop threshold.
   *
   * Sets the loop threshold value.
   *
   * @param int $lth
   *   A limit for all the looping functions.
   *
   * @ingroup setup
   */
  public function setLoopThreshold($lth) {
    if ($lth > 0) {
      $this->loopThreshold = (int) $lth;
    }
  }

  /**
   * Get search enumeration.
   *
   * Returns the search enumeration from the last solving.
   *
   * @return int
   *   Search enumeration.
   *
   * @ingroup setup
   */
  public function getSearchEnumeration() {
    return $this->searchEnumeration;
  }

  /* UTILITY FUNCTIONS */

  /**
   * Random number generator.
   *
   * Generates a random number from $min to $max1 to 9.
   *
   * @param int $min
   *   Minimum limit.
   * @param int $max
   *   Maximum limit. Larger than $min.
   *
   * @return int
   *   The generated value.
   *
   * @ingroup utility
   */
  protected static function sRand($min = 1, $max = 9) {
    if ($max <= $min) {
      return FALSE;
    }

    list($usec, $sec) = explode(' ', microtime());
    $seed = (float) $sec + ((float) $usec * 100000);
    mt_srand($seed);
    $randval = mt_rand($min, $max);

    return $randval;
  }

  /**
   * Get box coordinates from cell coordinates.
   *
   * Finds out to which box does the given cell belong to.
   *
   * @param int $r
   *   Row coordinate from 1 to 9.
   * @param int $c
   *   Column coordinate from 1 to 9.
   *
   * @return array
   *   The box coordinates.
   */
  protected static function getBoxFromCell($r, $c) {
    $x = ceil($r / 3);
    $y = ceil($c / 3);

    return array($x, $y);
  }

  /**
   * Get board in raster order.
   *
   * Converts and returns the given board in raster order.
   * Horizontal from left to right, vertical from top to bottom.
   *
   * @param array $board
   *   A 9x9 game board.
   *
   * @return array
   *   The board in raster order.
   *
   * @ingroup utility
   */
  protected static function getBoardInRasterOrder($board, $reverse = FALSE) {
    $c = 1;
    $raster = array();
    foreach ($board as $r => $row) {
      $ar = ($r % 2 == 0 && $reverse) ? array_reverse($row) : $row;
      foreach ($ar as $cell) {
        $raster[$c++] = $cell;
      }
    }

    return $raster;
  }

  /**
   * Convert raster order to coordinates.
   *
   * Converts and returns the given raster index to global coordinates.
   *
   * @param int $order
   *   A raster order index (1 to 81).
   *
   * @return array
   *   Bi-dimensional global coordinates.
   *
   * @ingroup utility
   */
  protected static function rasterOrderToCoord($order) {
    $r = ((int) ($order / 9.1)) + 1;
    $c = (($order % 9) == 0) ? 9 : (int) ($order % 9);
    return array($r, $c);
  }

  /**
   * Convert coordinates to raster order.
   *
   * Converts and returns the given raster index to global coordinates.
   *
   * @param int $r
   *   Row coordinate from 1 to 9.
   * @param int $c
   *   Column coordinate from 1 to 9.
   *
   * @return array
   *   A raster order index (1 to 81).
   *
   * @ingroup utility
   */
  protected static function coordToRasterOrder($r, $c) {
    $index = ($r - 1) * 9 + $c;
    return $index;
  }

  /**
   * Convert reverse raster order to coordinates.
   *
   * Converts and returns the given reverse raster index to global coordinates.
   *
   * @param int $order
   *   A reverse raster order index (1 to 81).
   *
   * @return array
   *   Bi-dimensional global coordinates.
   *
   * @ingroup utility
   */
  protected static function reverseRasterToCoord($order) {
    $r = ((int) ($order / 9.1)) + 1;
    $c = (($order % 9) == 0) ? 9 : (int) ($order % 9);
    if ($r % 2 == 0) {
      $c = 10 - $c;
    }

    return array($r, $c);
  }

  /**
   * Convert coordinates to reverse raster order.
   *
   * Converts and returns the given raster index to global coordinates.
   *
   * @param int $r
   *   Row coordinate from 1 to 9.
   * @param int $c
   *   Column coordinate from 1 to 9.
   *
   * @return array
   *   A raster order index (1 to 81).
   *
   * @ingroup utility
   */
  protected static function coordToReverseRaster($r, $c) {
    $index = 0;
    foreach ($this->puzzle as $i => $row) {
      $ar = ($i % 2 == 0) ? array_reverse($row, TRUE) : $row;
      foreach ($ar as $j => $cell) {
        $index++;
        if ($i == $r && $j == $c) {
          return $index;
        }
      }
    }

    return $index;
  }

  /**
   * Get number of givens.
   *
   * Returns the number of givens in the current puzzle.
   *
   * @return int
   *   Number of givens.
   *
   * @ingroup setup
   */
  protected function getGivensCount() {
    $givens_count = 0;
    for ($r = 1; $r <= 9; $r++) {
      for ($c = 1; $c <= 9; $c++) {
        if ($this->puzzle[$r][$c] != 0) {
          $givens_count++;
        }
      }
    }

    return $givens_count;
  }

  /**
   * Counts the givens in row/column.
   *
   * Counts and returns how many givens exists in the given row/column.
   *
   * @param int $elem
   *   Row/column.
   *
   * @return int
   *   Number of givens
   *
   * @ingroup utility
   */
  protected static function countArrayGivens($elem) {
    $count = 0;
    foreach ($elem as $cell) {
      if ($cell != 0) {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Get minimum of gives in rows and columns.
   *
   * Returns the lowest number of givens found in a row/column.
   *
   * @return int
   *   Minimum number of givens.
   *
   * @ingroup setup
   */
  protected function getMinGivensRowCol() {
    $givens_count = array();

    // Go through rows.
    for ($r = 1; $r <= 9; $r++) {
      $row = $this->getRow($r);
      $givens_cur = 0;
      foreach ($row as $val) {
        if ($val != 0) {
          $givens_cur++;
        }
      }
      $givens_count[] = $givens_cur;
    }

    // Go through columns.
    for ($c = 1; $c <= 9; $c++) {
      $col = $this->getCol($c);
      $givens_cur = 0;
      foreach ($col as $val) {
        if ($val != 0) {
          $givens_cur++;
        }
      }
      $givens_count[] = $givens_cur;
    }

    return min($givens_count);
  }

  /* ACCESSING FUNCTIONS */

  /**
   * Get the board.
   *
   * Fetches the entire board.
   *
   * @return array
   *   The board
   *
   * @ingroup access
   */
  public function getBoard() {
    return $this->puzzle;
  }

  /**
   * Set the board.
   *
   * Sets the entire board to the given values.
   *
   * @param array $board
   *   The board to set to
   *
   * @ingroup access
   */
  public function setBoard($board) {
    // First validate.
    if (count($board) != 9) {
      return FALSE;
    }

    foreach ($board as $row) {
      if (count($row) != 9) {
        return FALSE;
      }
    }

    // Then fix keys.
    $fixed_board = array();
    $r = 1;
    foreach ($board as $row) {
      $values = array();
      foreach ($row as $value) {
        if (in_array($value, array(1, 2, 3, 4, 5, 6, 7, 8, 9))) {
          $values[] = $value;
        }
        else {
          $values[] = 0;
        }
      }
      $fixed_board[$r++] = array_combine(array(1, 2, 3, 4, 5, 6, 7, 8, 9), array_values($row));
    }

    // Finally set.
    $this->puzzle = $fixed_board;
  }

  /**
   * Get a cell value.
   *
   * Fetches the value inside the given cell coordinates.
   *
   * @param int $x
   *   Row from 1 to 9
   * @param int $y
   *   Column from 1 to 9
   *
   * @return int
   *   The value of the cell
   *
   * @ingroup access
   */
  public function getCell($x, $y) {
    if (!in_array($x, array(1, 2, 3, 4, 5, 6, 7, 8, 9)) ||
        !in_array($y, array(1, 2, 3, 4, 5, 6, 7, 8, 9))) {
      return FALSE;
    }

    $val = $this->puzzle[$x][$y];

    return $val;
  }

  /**
   * Set a cell value.
   *
   * Sets the value inside the given cell coordinates.
   *
   * @param int $x
   *   Row from 1 to 9
   * @param int $y
   *   Column from 1 to 9
   * @param int $val
   *   The value of the cell
   *
   * @ingroup access
   */
  public function setCell($x, $y, $val) {
    if (!in_array($x, array(1, 2, 3, 4, 5, 6, 7, 8, 9)) ||
        !in_array($y, array(1, 2, 3, 4, 5, 6, 7, 8, 9))) {
      return FALSE;
    }

    $this->puzzle[$x][$y] = $val;
  }

  /**
   * Get a box from the puzzle board.
   *
   * Fetches a 9-cell box from the puzzle board.
   *
   * @param int $x
   *   Row from 1 to 3
   * @param int $y
   *   Column from 1 to 3
   *
   * @return array
   *   A 3x3 array with the requested data from the board.
   *
   * @ingroup access
   */
  protected function getBox($x, $y) {
    if (!in_array($x, array(1, 2, 3)) || !in_array($y, array(1, 2, 3))) {
      return FALSE;
    }

    $box = array();
    for ($i = ($x - 1) * 3 + 1; $i <= ($x - 1) * 3 + 3; $i++) {
      for ($j = ($y - 1) * 3 + 1; $j <= ($y - 1) * 3 + 3; $j++) {
        $box[$i][$j] = $this->puzzle[$i][$j];
      }
    }

    return $box;
  }

  /**
   * Get a row.
   *
   * Fetches a 9-cell row from the puzzle board.
   *
   * @param int $r
   *   Row number from 1 to 9
   *
   * @return array
   *   A 1x9 array with the requested data from the board.
   *
   * @ingroup access
   */
  protected function getRow($r) {
    if ($r < 1 || $r > 9) {
      return FALSE;
    }

    $row = array();
    for ($i = 1; $i <= 9; $i++) {
      $row[$i] = $this->puzzle[$r][$i];
    }

    return $row;
  }

  /**
   * Get a column.
   *
   * Fetches a 9-cell column from the puzzle board.
   *
   * @param int $c
   *   Column number from 1 to 9
   *
   * @return array
   *   A 1x9 array with the requested data from the board.
   *
   * @ingroup access
   */
  protected function getCol($c) {
    if ($c < 1 || $c > 9) {
      return FALSE;
    }

    $col = array();
    for ($i = 1; $i <= 9; $i++) {
      $col[$i] = $this->puzzle[$i][$c];
    }

    return $col;
  }

  /* TRANSFORMATION FUNCTIONS */

  /**
   * Swaps 2 columns belonging to the same block column.
   *
   * @param int $c1
   *   First column to switch
   * @param int $c2
   *   Second column to switch
   *
   * @ingroup access
   */
  protected function swapColumns($c1, $c2) {
    if (($c1 - 1) % 3 != ($c2 - 1) % 3) {
      return FALSE;
    }

    $col1 = $this->getCol($c1);
    $col2 = $this->getCol($c2);

    for ($r = 1; $r <= 9; $r++) {
      $this->puzzle[$r][$c1] = $col2[$r];
      $this->puzzle[$r][$c2] = $col1[$r];
    }
  }

  /**
   * Swaps 2 block columns.
   *
   * @param int $c1
   *   First column to switch
   * @param int $c2
   *   Second column to switch
   *
   * @ingroup access
   */
  protected function swapBlockColumns($c1, $c2) {
    if ($c1 == $c2) {
      return FALSE;
    }

    $col1 = array();
    $col2 = array();
    for ($i = 1; $i <= 3; $i++) {
      $col1[($c1 - 1) * 3 + $i] = $this->getCol(($c1 - 1) * 3 + $i);
      $col2[($c2 - 1) * 3 + $i] = $this->getCol(($c2 - 1) * 3 + $i);
    }

    foreach ($col1 as $col) {
      for ($r = 1; $r <= 9; $r++) {
        $this->puzzle[$r][$c1] = $col2[$r];
        $this->puzzle[$r][$c2] = $col1[$r];
      }
    }
  }


  /* VALIDATION FUNCTIONS */

  /**
   * Check box for value.
   *
   * Checks if given box includes the given value.
   *
   * @param int $x
   *   Row number from 1 to 3
   * @param int $y
   *   Column number from 1 to 3
   * @param int $val
   *   Value to check for
   *
   * @return bool
   *   Cell's global coordinates if found, FALSE if not
   *
   * @ingroup validation
   */
  protected function boxHasValue($x, $y, $val) {
    if ($box = $this->getBox($x, $y)) {
      foreach ($box as $r => $row) {
        foreach ($row as $c => $cell) {
          if ($cell == $val) {
            return array($r, $c);
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Check row for value.
   *
   * Checks if given row includes the given value.
   *
   * @param int $x
   *   Row number from 1 to 9
   * @param int $val
   *   Value to check for
   *
   * @return bool
   *   Cell's global coordinates if value found, FALSE if not
   *
   * @ingroup validation
   */
  protected function rowHasValue($x, $val) {
    if ($row = $this->getRow($x)) {
      foreach ($row as $c => $cell) {
        if ($cell == $val) {
          return array($x, $c);
        }
      }
    }

    return FALSE;
  }

  /**
   * Check column for value.
   *
   * Checks if given column includes the given value.
   *
   * @param int $y
   *   Row number from 1 to 9
   * @param int $val
   *   Value to check for
   *
   * @return array/bool
   *   Cell's global coordinates if value found, FALSE if not
   *
   * @ingroup validation
   */
  protected function colHasValue($y, $val) {
    if ($col = $this->getCol($y)) {
      foreach ($col as $r => $cell) {
        if ($cell == $val) {
          return array($r, $y);
        }
      }
    }

    return FALSE;
  }

  /**
   * Check if box is full.
   *
   * Checks if given box is completed.
   *
   * @param int $x
   *   Row number from 1 to 3
   * @param int $y
   *   Column number from 1 to 3
   *
   * @return bool
   *   TRUE if box is full, the number of missing values if not.
   *
   * @ingroup validation
   */
  protected function isBoxFull($x, $y) {
    if ($box = $this->getBox($x, $y)) {
      $empty = 0;
      foreach ($box as $col) {
        foreach ($col as $cell) {
          if ($cell == 0) {
            $empty++;
          }
        }
      }

      return ($empty == 0) ? TRUE : $empty;
    }
  }

  /**
   * Check if row is full.
   *
   * Checks if given row is completed.
   *
   * @param int $x
   *   Row number from 1 to 3
   *
   * @return bool
   *   TRUE if row is full, the number of missing values if not.
   *
   * @ingroup validation
   */
  protected function isRowFull($x) {
    if ($row = $this->getRow($x)) {
      $empty = 0;
      foreach ($row as $cell) {
        if ($cell == 0) {
          $empty++;
        }
      }

      return ($empty == 0) ? TRUE : $empty;
    }
  }

  /**
   * Check if column is full.
   *
   * Checks if given column is completed.
   *
   * @param int $y
   *   Column number from 1 to 3
   *
   * @return bool
   *   TRUE if column is full, the number of missing values if not.
   *
   * @ingroup validation
   */
  protected function isColFull($y) {
    if ($col = $this->getCol($y)) {
      $empty = 0;
      foreach ($col as $cell) {
        if ($cell == 0) {
          $empty++;
        }
      }

      return ($empty == 0) ? TRUE : $empty;
    }
  }

  /**
   * Check if puzzle is full.
   *
   * Checks if the entire puzzle is completed.
   *
   * @return bool
   *   TRUE if puzzle is full, the number of missing values if not.
   *
   * @ingroup validation
   */
  protected function isPuzzleFull() {
    $empty = 0;
    foreach ($this->puzzle as $row) {
      foreach ($row as $cell) {
        if ($cell == 0) {
          $empty++;
        }
      }
    }

    return ($empty == 0) ? TRUE : $empty;
  }


  /**
   * Validates given box.
   *
   * Checks if the given box is completed with correct values.
   *
   * @param int $x
   *   Row number from 1 to 3
   * @param int $y
   *   Column number from 1 to 3
   *
   * @return bool
   *   TRUE if it is valid, FALSE if not.
   *
   * @ingroup validation
   */
  protected function validateBox($x, $y, $ignore_zeroes = FALSE) {
    if ($box = $this->getBox($x, $y)) {

      $numbers = array();
      foreach ($box as $col) {
        foreach ($col as $cell) {

          if ($cell == 0 && !$ignore_zeroes) {
            return FALSE;
          }
          else {
            if (in_array($cell, $numbers)) {
              return FALSE;
            }
            else {
              if ($cell != 0) {
                $numbers[] = $cell;
              }
            }
          }

        }
      }

      return (count($numbers) == 9) || $ignore_zeroes;
    }
  }

  /**
   * Validates given row.
   *
   * Checks if the given row is completed with correct values.
   *
   * @param int $x
   *   Row number from 1 to 9
   *
   * @return bool
   *   TRUE if it is valid, FALSE if not.
   *
   * @ingroup validation
   */
  protected function validateRow($x, $ignore_zeroes = FALSE) {
    if ($row = $this->getRow($x)) {

      $numbers = array();
      foreach ($row as $cell) {

        if ($cell == 0 && !$ignore_zeroes) {
          return FALSE;
        }
        else {
          if (in_array($cell, $numbers)) {
            return FALSE;
          }
          else {
            if ($cell != 0) {
              $numbers[] = $cell;
            }
          }
        }

      }

      return (count($numbers) == 9) || $ignore_zeroes;
    }
  }

  /**
   * Validates given column.
   *
   * Checks if the given column is completed with correct values.
   *
   * @param int $y
   *   Column number from 1 to 9
   *
   * @return bool
   *   TRUE if it is valid, FALSE if not.
   *
   * @ingroup validation
   */
  protected function validateCol($y, $ignore_zeroes = FALSE) {
    if ($col = $this->getCol($y)) {

      $numbers = array();
      foreach ($col as $cell) {

        if ($cell == 0 && !$ignore_zeroes) {
          return FALSE;
        }
        else {
          if (in_array($cell, $numbers)) {
            return FALSE;
          }
          else {
            if ($cell != 0) {
              $numbers[] = $cell;
            }
          }
        }

      }

      return (count($numbers) == 9) || $ignore_zeroes;
    }
  }

  /**
   * Validates puzzle.
   *
   * Checks if the entire puzzle is validly completed.
   *
   * @return bool
   *   TRUE if it is valid, FALSE if not.
   *
   * @ingroup validation
   */
  public function validatePuzzle($ignore_zeroes = FALSE) {
    for ($i = 1; $i <= 3; $i++) {
      for ($j = 1; $j <= 3; $j++) {
        if (!$this->validateBox($i, $j, $ignore_zeroes)) {
          return FALSE;
        }
      }
    }

    for ($i = 1; $i <= 9; $i++) {
      if (!$this->validateRow($i, $ignore_zeroes)) {
        return FALSE;
      }
    }

    for ($i = 1; $i <= 9; $i++) {
      if (!$this->validateCol($i, $ignore_zeroes)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /* AI FUNCTIONS */

  /**
   * Check if it satisfies rules.
   *
   * Check if the given data satisfies the game's rules.
   *
   * @param int $r
   *   Row number from 1 to 9
   * @param int $c
   *   Column number from 1 to 9
   * @param int $value
   *   Column number from 1 to 9
   *
   * @return bool
   *   TRUE/FALSE if game rules are satisfied.
   *
   * @ingroup ai
   */
  public function satisfiesRules($r, $c, $value) {
    list($x, $y) = self::getBoxFromCell($r, $c);
    return (!$this->rowHasValue($r, $value) && !$this->colHasValue($c, $value) && !$this->boxHasValue($x, $y, $value));
  }

  /**
   * Get box's row neighbours.
   *
   * Finds and returns the box's row neighbours.
   *
   * @param int $x
   *   Row number from 1 to 3
   * @param int $y
   *   Column number from 1 to 3
   *
   * @return array
   *   An array of boxes
   *
   * @ingroup ai
   */
  protected function getRowBoxNeighbours($x, $y) {
    $rnc = array(1, 2, 3);
    unset($rnc[$y]);

    $row_neighbours = array();
    foreach ($rnc as $c) {
      $row_neighbours[] = $this->getBox($x, $c);
    }

    return $row_neighbours;
  }

  /**
   * Get box's column neighbours.
   *
   * Finds and returns the box's column neighbours.
   *
   * @param int $x
   *   Row number from 1 to 3
   * @param int $y
   *   Column number from 1 to 3
   *
   * @return array
   *   An array of boxes
   *
   * @ingroup ai
   */
  protected function getColBoxNeighbours($x, $y) {
    $cnc = array(1, 2, 3);
    unset($cnc[$x]);

    $col_neighbours = array();
    foreach ($cnc as $r) {
      $col_neighbours[] = $this->getBox($r, $y);
    }

    return $col_neighbours;
  }

  /**
   * Get box's neighbours.
   *
   * Finds and returns the box's neighbours.
   *
   * @param int $x
   *   Row number from 1 to 3
   * @param int $y
   *   Column number from 1 to 3
   *
   * @return array
   *   An array of boxes
   *
   * @ingroup ai
   */
  protected function getBoxNeighbours($x, $y) {
    return array(
      'row' => $this->getRowBoxNeighbours($x, $y),
      'col' => $this->getColBoxNeighbours($x, $y),
    );
  }

  /**
   * Lock puzzle.
   *
   * Basically creates a copy of the puzzle in which non-zero values
   * are considered locked cells and zeroes are unlocked cells.
   *
   * @ingroup ai
   */
  protected function lockBoard() {
    $this->lockedPuzzle = $this->puzzle;
  }

  /**
   * Solve puzzle.
   *
   * Solves puzzle by Depth-first Search algorithm.
   *
   * @return bool
   *   TRUE/FALSE if puzzle was solved.
   *
   * @ingroup ai
   */
  public function solve() {
    // Lock board.
    $this->lockBoard();

    // Set difficulty evaluation result to zero.
    $this->searchEnumeration = 0;

    // Enter loop.
    $processed = array();
    $r = 1;
    $diff_eval = 0;
    while ($r <= 9) {
      $c = 1;
      while ($c <= 9) {
        if ($this->lockedPuzzle[$r][$c] == 0) {

          // Assume a value that satisfies game rules and place it in the cell.
          $valid_found = FALSE;
          for ($given = $this->puzzle[$r][$c] + 1; $given <= 9; $given++) {
            if ($this->satisfiesRules($r, $c, $given)) {
              $this->puzzle[$r][$c] = $given;
              array_push($processed, array($r, $c));
              $valid_found = TRUE;
              $diff_eval++;
              break;
            }
          }

          // If we couldn't find a value to place then:
          // - we step back and assume another value for the previous cell.
          // - if there's no steps back then the puzzle is unsolvable.
          if (!$valid_found) {
            if (count($processed) > 0) {
              $this->puzzle[$r][$c] = 0;
              list($r, $c) = array_pop($processed);
              continue;
            }
            else {
              return FALSE;
            }
          }
        }
        $c++;
      }
      $r++;
    }

    $this->searchEnumeration = $diff_eval;

    return TRUE;
  }

  /**
   * Returns a difficulty evaluation for the currently puzzle.
   *
   * @return string
   *   A constant string from the already set 5 difficulty levels.
   */
  public function evaluateDifficulty() {
    // Total givens factor.
    $total_givens = $this->getGivensCount();
    if ($total_givens >= 50) {
      $total_givens_factor = LEVEL_VERY_EASY;
    }
    elseif (36 <= $total_givens && $total_givens < 50) {
      $total_givens_factor = LEVEL_EASY;
    }
    elseif (32 <= $total_givens && $total_givens < 36) {
      $total_givens_factor = LEVEL_MEDIUM;
    }
    elseif (28 <= $total_givens && $total_givens < 32) {
      $total_givens_factor = LEVEL_DIFFICULT;
    }
    elseif (22 <= $total_givens && $total_givens < 28) {
      $total_givens_factor = LEVEL_EVIL;
    }

    // Lower bound in rows and columns factor.
    $min_givens_row_col = $this->getMinGivensRowCol();
    $min_givens_factor = ($min_givens_row_col == 0) ? LEVEL_EVIL : 6 - $min_givens_row_col;

    // Search enumeration factor.
    $puzzle = new SudokuPuzzle();
    $puzzle->setBoard($this->getBoard());
    if ($puzzle->solve()) {
      $search_enum = $puzzle->getSearchEnumeration();
      if ($search_enum < 100) {
        $search_factor = LEVEL_VERY_EASY;
      }
      elseif (100 <= $search_enum && $search_enum < 1000) {
        $search_factor = LEVEL_EASY;
      }
      elseif (1000 <= $search_enum && $search_enum < 10000) {
        $search_factor = LEVEL_MEDIUM;
      }
      elseif (10000 <= $search_enum && $search_enum < 100000) {
        $search_factor = LEVEL_DIFFICULT;
      }
      elseif (100000 <= $search_enum) {
        $search_factor = LEVEL_EVIL;
      }
    }

    // We are actually missing the applicable technique factor.
    // It has to do with the human logic techniques applicable
    // for solving the puzzle. This is not implemented here.
    // We calculate from the other 3 factor and share the missing
    // weight in between the two last factors.
    $difficulty = 0.4 * $total_givens_factor + 0.3 * $min_givens_factor + 0.3 * $search_factor;

    return $difficulty;
  }

  /**
   * Check if solvable.
   *
   * Check if current puzzle is solvable.
   *
   * @return bool
   *   TRUE --> Solvable, FALSE --> Not solvable.
   *
   * @ingroup ai
   */
  protected function isSolvable() {
    $test_solvable = new SudokuPuzzle();
    $test_solvable->setBoard($this->getBoard());
    $test_solvable->solve();
    return $test_solvable->validatePuzzle();
  }

  /**
   * Generate a terminal pattern.
   *
   * Generates a terminal pattern (a solvable puzzle)
   * using the Las Vegas algorithm.
   *
   * @return bool
   *   TRUE --> Success, FALSE --> Failure.
   *
   * @ingroup ai
   */
  protected function generateTerminalPattern() {
    $attempts = 0;
    while ($attempts++ < $this->loopThreshold) {

      // Placing 11 random givens that satisfy the rules
      // optimizes computational time while enhancing the
      // diversity of the puzzle.
      $loops = 0;
      while ($loops < 11) {
        // Generate random coordinates.
        $r = self::sRand();
        $c = self::sRand();
        list($x, $y) = self::getBoxFromCell($r, $c);

        $given = self::sRand();

        if ($this->puzzle[$r][$c] == 0 && $this->satisfiesRules($r, $c, $given)) {
          $this->puzzle[$r][$c] = $given;
          $loops++;
        }
      }

      // Test if it is solvable.
      if ($this->isSolvable()) {
        return TRUE;
      }

    }

    return FALSE;
  }

  /**
   * Generate a unique-solvable puzzle.
   *
   * Generate a unique-solvable puzzle with in easy or very easy mode.
   *
   * @param int $difficulty
   *   Very easy, easy
   *
   * @ingroup ai
   */
  public function generateEasy($difficulty = LEVEL_VERY_EASY) {
    $cells_checked = array();

    // Set restrictions.
    $range_givens = ($difficulty == LEVEL_VERY_EASY) ? array(50, 64) : array(36, 49);
    list($min, $max) = $range_givens;
    $givens = self::sRand($min, $max);
    $min_givens = ($difficulty == LEVEL_VERY_EASY) ? 5 : 4;
    while (count($cells_checked) < 81) {
      // We select a point.
      $r = self::sRand();
      $c = self::sRand();

      // If we have walked this cell before then try again.
      if (in_array(array($r, $c), $cells_checked)) {
        continue;
      }
      else {
        $cells_checked[] = array($r, $c);
      }

      // We check if digging this cell would violate restrictions.
      // Total givens. If it would then we're finished.
      if ($this->getGivensCount() - 1 < $givens) {
        continue;
      }

      // Row minimum givens.
      $row = $this->getRow($r);
      $count = self::countArrayGivens($row) - 1;
      if ($count < $min_givens) {
        continue;
      }

      // Column minimum givens.
      $col = $this->getCol($c);
      $count = self::countArrayGivens($col) - 1;
      if ($count < $min_givens) {
        continue;
      }

      // We try to dig and see if it yields a unique solution.
      $num = $this->puzzle[$r][$c];
      $unique = TRUE;
      for ($t = 1; $t <= 9; $t++) {
        if ($t != $num && $this->satisfiesRules($r, $c, $t)) {
          $this->puzzle[$r][$c] = $t;
          if ($this->isSolvable()) {
            $unique = FALSE;
          }
        }
      }

      $this->puzzle[$r][$c] = ($unique) ? 0 : $num;
    }
  }

  /**
   * Generate a unique-solvable puzzle.
   *
   * Generate a unique-solvable puzzle with in medium mode.
   *
   * @ingroup ai
   */
  public function generateMedium() {
    $range_givens = array(32, 35);
    list($min, $max) = $range_givens;
    $givens = self::sRand($min, $max);
    $min_givens = 3;
    for ($index = 1; $index <= 81; $index += 2) {
      list($r, $c) = self::reverseRasterToCoord($index);

      // We check if digging this cell would violate restrictions.
      // Total givens. If it would then we're finished.
      if ($this->getGivensCount() - 1 < $givens) {
        continue;
      }

      // Row minimum givens.
      $row = $this->getRow($r);
      $count = self::countArrayGivens($row) - 1;
      if ($count < $min_givens) {
        continue;
      }

      // Column minimum givens.
      $col = $this->getCol($c);
      $count = self::countArrayGivens($col) - 1;
      if ($count < $min_givens) {
        continue;
      }

      // We try to dig and see if it yields a unique solution.
      $num = $this->puzzle[$r][$c];
      $unique = TRUE;
      for ($t = 1; $t <= 9; $t++) {
        if ($t != $num && $this->satisfiesRules($r, $c, $t)) {
          $this->puzzle[$r][$c] = $t;
          if ($this->isSolvable()) {
            $unique = FALSE;
          }
        }
      }

      $this->puzzle[$r][$c] = ($unique) ? 0 : $num;
    }
  }

  /**
   * Generate a unique-solvable puzzle.
   *
   * Generate a unique-solvable puzzle with in difficult mode.
   *
   * @ingroup ai
   */
  public function generateDifficult() {
    $range_givens = array(28, 31);
    list($min, $max) = $range_givens;
    $givens = self::sRand($min, $max);
    $min_givens = 2;
    for ($index = 1; $index <= 81; $index++) {
      list($r, $c) = self::reverseRasterToCoord($index);

      // We check if digging this cell would violate restrictions.
      // Total givens. If it would then we're finished.
      if ($this->getGivensCount() - 1 < $givens) {
        continue;
      }

      // Row minimum givens.
      $row = $this->getRow($r);
      $count = self::countArrayGivens($row) - 1;
      if ($count < $min_givens) {
        continue;
      }

      // Column minimum givens.
      $col = $this->getCol($c);
      $count = self::countArrayGivens($col) - 1;
      if ($count < $min_givens) {
        continue;
      }

      // We try to dig and see if it yields a unique solution.
      $num = $this->puzzle[$r][$c];
      $unique = TRUE;
      for ($t = 1; $t <= 9; $t++) {
        if ($t != $num && $this->satisfiesRules($r, $c, $t)) {
          $this->puzzle[$r][$c] = $t;
          if ($this->isSolvable()) {
            $unique = FALSE;
          }
        }
      }

      $this->puzzle[$r][$c] = ($unique) ? 0 : $num;
    }
  }

  /**
   * Generate a unique-solvable puzzle.
   *
   * Generate a unique-solvable puzzle with in evil mode. \m/
   *
   * @ingroup ai
   */
  public function generateEvil() {
    $range_givens = array(22, 27);
    list($min, $max) = $range_givens;
    $givens = self::sRand($min, $max);
    $min_givens = 0;
    for ($index = 1; $index <= 81; $index++) {
      list($r, $c) = self::rasterOrderToCoord($index);

      // We check if digging this cell would violate restrictions.
      // Total givens. If it would then we're finished.
      if ($this->getGivensCount() - 1 < $givens) {
        continue;
      }

      // Row minimum givens.
      $row = $this->getRow($r);
      $count = self::countArrayGivens($row) - 1;
      if ($count < $min_givens) {
        continue;
      }

      // Column minimum givens.
      $col = $this->getCol($c);
      $count = self::countArrayGivens($col) - 1;
      if ($count < $min_givens) {
        continue;
      }

      // We try to dig and see if it yields a unique solution.
      $num = $this->puzzle[$r][$c];
      $unique = TRUE;
      for ($t = 1; $t <= 9; $t++) {
        if ($t != $num && $this->satisfiesRules($r, $c, $t)) {
          $this->puzzle[$r][$c] = $t;
          if ($this->isSolvable()) {
            $unique = FALSE;
          }
        }
      }

      $this->puzzle[$r][$c] = ($unique) ? 0 : $num;
    }
  }

  /**
   * Generate a unique-solvable puzzle.
   *
   * Generate a unique-solvable puzzle with adjustable difficulty.
   *
   * @param int $difficulty
   *   Very easy, easy, medium, difficult, evil
   *
   * @return bool
   *   Generation succeeded or not
   *
   * @ingroup ai
   */
  public function generate($difficulty = LEVEL_VERY_EASY) {
    // Clear the board to begin with.
    $this->clearPuzzle();

    // Set all the cells can-be-dug.
    $this->lockBoard();

    // Firstly we generate a terminal pattern.
    $this->generateTerminalPattern();

    // We proceed to solve it.
    $this->solve();

    // Walk the board in different ways
    // according to selected difficulty level.
    if ($difficulty == LEVEL_VERY_EASY || $difficulty == LEVEL_EASY) {
      $this->generateEasy($difficulty);
    }
    elseif ($difficulty == LEVEL_MEDIUM) {
      $this->generateMedium();
    }
    elseif ($difficulty == LEVEL_DIFFICULT) {
      $this->generateDifficult();
    }
    elseif ($difficulty == LEVEL_EVIL) {
      $this->generateEvil();
    }

    return $this->evaluateDifficulty();
  }

  /**
   * Get candidates for box given a value.
   *
   * Scans and finds the candidates where the given value could be placed.
   *
   * @return bool
   *   An array of (global) cell coordinates / FALSE if none could be found.
   *
   * @ingroup ai
   */
  public function hint() {

    // Find all possible hints we can give.
    $candidates = array();
    for ($r = 1; $r <= 9; $r++) {
      for ($c = 1; $c <= 9; $c++) {

        if ($this->puzzle[$r][$c] == 0) {

          for ($num = 0; $num <= 9; $num++) {
            if ($this->satisfiesRules($r, $c, $num)) {
              $candidates[] = array('pos' => array($r, $c), 'val' => $num);
            }
          }

        }

      }
    }

    // If we found some choose one randomly and return it.
    if (count($candidates) > 0) {
      $random_index = self::sRand(0, count($candidates) - 1);
      return $candidates[$random_index];
    }

    return FALSE;
  }

  /**
   * Check puzzle validity.
   *
   * Scans the puzzle and returns if it has been
   * properly fulled.
   *
   * @ingroup ai
   */
  public function checkPuzzle() {
    for ($r = 1; $r <= 9; $r++) {
      for ($c = 1; $c <= 9; $c++) {

        list($x, $y) = self::getBoxFromCell($r, $c);
        if ($this->puzzle[$r][$c] != 0) {
          if (!$this->validateRow($r, TRUE) ||
              !$this->validateCol($c, TRUE) ||
              !$this->validateBox($x, $y, TRUE)) {
            return FALSE;
          }
        }

      }
    }

    return TRUE;
  }

}

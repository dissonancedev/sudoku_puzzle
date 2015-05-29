<?php
/**
 * @file
 * Theme implementation for a Sudoku puzzle.
 *
 * Available variables:
 * - levels: Difficulty levels values and labels.
 */
?>

<div id="sudoku-puzzle-container">
  <table id="sudoku-puzzle-board">
    <thead>
      <tr>
        <?php for ($j = 1; $j <= 9; $j++) : ?>
          <td class="puzzle-num-selection<?php echo ($j == 1) ? ' puzzle-num-selected' : ''; ?>">
            <span><?php print $j; ?></span>
          </td>
        <?php endfor; ?>
      </tr>
    </thead>
    <tbody>
      <?php for ($i = 1; $i <= 9; $i++) : ?>
        <tr class="puzzle-row<?php echo ($i == 1) ? ' puzzle-row-first' : ''; ?><?php echo ($i % 3 == 0) ? ' puzzle-row-last' : ''; ?>">
          <?php for ($j = 1; $j <= 9; $j++) : ?>
            <td class="puzzle-cell<?php echo ($j == 1) ? ' puzzle-col-first' : ''; ?><?php echo ($j % 3 == 0) ? ' puzzle-col-last' : ''; ?>">
              <span></span>
            </td>
          <?php endfor; ?>
        </tr>
      <?php endfor; ?>
    </tbody>
  </table>
  <div class="game-controls">
    <select id="sudoku-puzzle-level">
      <?php foreach ($levels as $value => $label) : ?>
        <option value="<?php print $value; ?>"><?php print $label; ?></option>
      <?php endforeach; ?>
    </select>
    <button id="sudoku-puzzle-new">New</button>
    <button id="sudoku-puzzle-check">Check</button>
    <button id="sudoku-puzzle-solve">Solve</button>
    <button id="sudoku-puzzle-hint">Hint!</button>
  </div>
</div>

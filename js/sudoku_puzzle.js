/**
 * @file
 * JS functionality for SuDoKu game.
 * @author Nikos Parastatidis <nparasta@gmail.com>
 */

(function ($) {
  Drupal.behaviors.sudokuPuzzle =  {
    attach: function(context, settings) {
      var lockedPuzzle = [];

      function getPuzzle() {
        var puzzle = [];
        var r = 0;
        var $rows = $('#sudoku-puzzle-board tr.puzzle-row');
        $rows.each( function() {
          puzzle[r] = [];
          var $cells = $(this).children('.puzzle-cell');
          var c = 0;
          $cells.each( function() {
            puzzle[r][c++] = parseInt($(this).text());
          });
          r++;
        });

        return puzzle;
      }

      function setPuzzle(puzzle) {
        var $rows = $('#sudoku-puzzle-board tr.puzzle-row');
        $.each(puzzle, function(i, row) {
          lockedPuzzle[i] = [];
          var $cells = $rows.eq(i - 1).children('.puzzle-cell');
          $.each(row, function(j, val) {
            lockedPuzzle[i][j] = val;
            if (val > 0) {
              $cells.eq(j - 1).children('span').text(val);
            }
            else {
              $cells.eq(j - 1).children('span').text('');
            }
          });
        });
      }

      function buttonBlink(success) {
        var color = (success) ? 'bgreen' : 'bred';
        $('#sudoku-puzzle-check').addClass(color);
        setTimeout(function() {
          $('#sudoku-puzzle-check').removeClass(color);
        }, 3000);
      }

      function cellBlink(info) {
        var row = info['pos'][0],
            col = info['pos'][1],
            value = info['val'];

        var $rows = $('#sudoku-puzzle-board tr.puzzle-row');
        var $cells = $rows.eq(row - 1).children('.puzzle-cell');
        var $cell = $cells.eq(col - 1);

        $cell.addClass('puzzle-cell-hint');
        $cell.children('span').text(value);
        setTimeout(function() {
          $cell.removeClass('puzzle-cell-hint');
          $cell.children('span').text('');
        }, 3000);
      }

      /* GAME EVENTS */

      $('.puzzle-num-selection').click(function() {
        if (!$(this).hasClass('puzzle-num-selected')) {
          $('.puzzle-num-selected').removeClass('puzzle-num-selected');
          $(this).addClass('puzzle-num-selected');
        }
      });

      $('.puzzle-cell').click( function() {
        var r = $(this).parent().index() + 1, c = $(this).index() + 1;
        if (lockedPuzzle[r][c] == 0) {
          var selectedValue = parseInt($('.puzzle-num-selected').text());
          $(this).text(selectedValue);
        }
      });

      /* BUTTONS EVENTS */

      $('#sudoku-puzzle-new').click(function() {
        $.ajax({
          url: "/sudoku_puzzle/api",
          method: "POST",
          format: "json",
          data: { command: 'new_puzzle', options: { level: $('#sudoku-puzzle-level').val() } },
          success: function( response ) {
            if (!response.error) {
              setPuzzle(response);
            }
          }
        });
      });

      $('#sudoku-puzzle-check').click(function() {
        $.ajax({
          url: "/sudoku_puzzle/api",
          method: "POST",
          format: "json",
          data: { command: 'check', options: { board: getPuzzle() } },
          success: function( response ) {
            buttonBlink(response.valid);
          }
        });
      });

      $('#sudoku-puzzle-solve').click(function() {
        $.ajax({
          url: "/sudoku_puzzle/api",
          method: "POST",
          format: "json",
          data: { command: 'solve', options: { board: getPuzzle() } },
          success: function( response ) {
            if (!response.error) {
              setPuzzle(response);
            }
          }
        });
      });

      $('#sudoku-puzzle-hint').click(function() {
        $.ajax({
          url: "/sudoku_puzzle/api",
          method: "POST",
          format: "json",
          data: { command: 'hint', options: { board: getPuzzle() } },
          success: function( response ) {
            if (!response.error) {
              cellBlink(response);
            }
          }
        });
      });
    }
  };

})(jQuery);

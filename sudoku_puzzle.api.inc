<?php
/**
 * @file
 * Sudoku puzzle module API implementation.
 * @author Nikos Parastatidis <nparasta@gmail.com>
 */

function _sudoku_puzzle_api_request() {
  $command = $_REQUEST['command'];
  if (empty($command)) {
    return FALSE;
  }

  $options = (!empty($_REQUEST['options'])) ? $_REQUEST['options'] : array();

  $request_function = '_sudoku_puzzle_api_request_' . $command;
  $result = $request_function($options);

  drupal_json_output($result);
}

function _sudoku_puzzle_api_request_new_puzzle($options = array()) {
  $puzzle_engine = new SudokuPuzzle();
  $difficulty_level = $options['level'];
  $eval_diff = $puzzle_engine->generate($difficulty_level);

  $board = $puzzle_engine->getBoard();
  if (!$board) {
    $board = array('error' => t('Could not generate a puzzle'));
  }

  $response = array(
    'board' => $board,
    'difficulty_level' => $eval_diff,
  );

  return $response;
}

function _sudoku_puzzle_api_request_check($options = array()) {
  $puzzle_engine = new SudokuPuzzle();
  $board = $options['board'];
  $puzzle_engine->setBoard($board);
  $result = $puzzle_engine->validatePuzzle(TRUE);

  return array('valid' => $result);
}

function _sudoku_puzzle_api_request_solve($options = array()) {
  $puzzle_engine = new SudokuPuzzle();
  $board = $options['board'];
  $puzzle_engine->setBoard($board);
  $result = $puzzle_engine->solve();

  return ($result) ? $puzzle_engine->getBoard() : array('error' => t('Could not generate a puzzle'));
}

function _sudoku_puzzle_api_request_hint($options = array()) {
  $puzzle_engine = new SudokuPuzzle();
  $board = $options['board'];
  $puzzle_engine->setBoard($board);
  $result = $puzzle_engine->hint();

  return ($result) ? $result : array('error' => t('Could not find a hint'));
}

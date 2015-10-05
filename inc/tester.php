<?php

require_once 'SudokuPuzzle.php';

$puzzle = new SudokuPuzzle(100);

$board_easy = array(
  array(0, 4, 3, 8, 2, 0, 0, 0, 0),
  array(1, 2, 0, 6, 3, 0, 5, 8, 0),
  array(8, 0, 0, 7, 0, 0, 0, 4, 0),
  array(6, 0, 0, 0, 0, 0, 0, 0, 5),
  array(0, 7, 0, 5, 4, 9, 0, 6, 0),
  array(4, 0, 0, 0, 0, 0, 0, 0, 1),
  array(0, 3, 0, 0, 0, 7, 0, 0, 6),
  array(0, 6, 7, 0, 5, 8, 0, 2, 3),
  array(0, 0, 0, 0, 6, 2, 9, 7, 0),
);

$board_hard = array(
  array(3, 0, 0, 4, 0, 0, 0, 2, 0),
  array(0, 0, 2, 0, 0, 0, 7, 6, 9),
  array(0, 0, 0, 0, 7, 0, 0, 0, 0),
  array(0, 8, 0, 9, 0, 0, 5, 1, 0),
  array(0, 9, 1, 0, 0, 0, 3, 7, 0),
  array(0, 4, 6, 0, 0, 1, 0, 9, 0),
  array(0, 0, 0, 0, 6, 0, 0, 0, 0),
  array(9, 2, 7, 0, 0, 0, 6, 0, 0),
  array(0, 6, 0, 0, 0, 5, 0, 0, 1),
);

$puzzle->setBoard($board_easy);

echo $puzzle->evaluateDifficulty() . PHP_EOL;

$puzzle->setBoard($board_hard);

echo $puzzle->evaluateDifficulty() . PHP_EOL;
//$puzzle->generate();
//print_r($puzzle->getBoard());

/*$puzzle->solve();

print_r($puzzle->getBoard());
*/

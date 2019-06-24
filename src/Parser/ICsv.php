<?php


namespace CsvParser\Parser;


interface ICsv
{
  public static function getParser($delimiter, $quote, $escape, array $record_end);

  /**
   * Feed the parser a chunck of the csv formatted string to be parsed.
   *
   * @param string $chunk
   *   Part or all of a csv file.
   */
  public function feed($chunk);

  /**
   * Gets a record.
   */
  public function getRecord();

  /**
   * It sets the parser's state to its initial state.
   */
  public function reset();

  /**
   * Informs the parser that we are done.
   */
  public function finish();
}
<?php


namespace CsvParser\Parser;


use Contracts\Parser;

interface ICsv extends Parser
{
  public static function getParser($delimiter = ",", $quote = '"', $escape = "\\", $record_end = ["\n", "\r"]);
}
<?php

namespace CsvParserTest;

use CsvParser\Parser\Csv;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function test()
    {
        $parser = Csv::getParser();
        $parser->feed(file_get_contents(__DIR__ . "/data/countries.csv"));
        $parser->finish();

        $records = $parser->getRecords();

        $this->assertEquals(5, count($records));
    }
}

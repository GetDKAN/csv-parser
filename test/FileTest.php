<?php

namespace CsvParserTest;

class FileTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        $parser = \CsvParser\Parser\Csv::getParser();
        $parser->feed(file_get_contents(__DIR__ . "/data/countries.csv"));
        $parser->finish();

        $records = $parser->getRecords();

        $this->assertEquals(5, count($records));
    }
}

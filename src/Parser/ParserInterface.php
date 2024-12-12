<?php

namespace CsvParser\Parser;

/**
 * Interface for parsing CSV files.
 */
interface ParserInterface
{

    /**
     * Feed a string into the parser.
     *
     * @param string $chunk
     *   A chunk of the CSV being fed into the parser.
     */
    public function feed(string $chunk): void;

    /**
     * Get a record.
     *
     * @return array|null
     *   The record as an array, or null.
     */
    public function getRecord();

    /**
     * Reset the parser to an initialized state.
     */
    public function reset(): void;

    /**
     * At the end of a run of parsing, ensure there were no errors.
     *
     * @throws \Exception
     *   Throws an exception if the state machine was out of sync with our
     *   expectations.
     */
    public function finish(): void;
}

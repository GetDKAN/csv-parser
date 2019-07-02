<?php


namespace CsvParser\Parser;

use Maquina\StateMachine\MachineOfMachines;

class Csv implements ICsv
{

  const STATE_NEW_FIELD = "s_new_field";
  const STATE_CAPTURE = "s_capture";
  const STATE_NO_CAPTURE = "s_no_capture";
  const STATE_ESCAPE = "s_escape";
  const STATE_RECORD_END = "s_record_end";

  const STATE_QUOTE_INITIAL = "s_q_initial";
  const STATE_QUOTE_FINAL = "s_q_final";
  const STATE_QUOTE_CAPTURE = "s_q_capture";
  const STATE_QUOTE_NO_CAPTURE = "s_q_no_capture";
  const STATE_QUOTE_ESCAPE = "s_q_escape";
  const STATE_QUOTE_ESCAPE_QUOTE = "s_q_escape_quote";

  const CHAR_TYPE_DELIMITER = "c_delimiter";
  const CHAR_TYPE_QUOTE = "c_quote";
  const CHAR_TYPE_ESCAPE = "c_escape";
  const CHAR_TYPE_RECORD_END = "c_record_end";
  const CHAR_TYPE_BLANK = "c_blank";
  const CHAR_TYPE_OTHER = "c_other";

  private $delimiter;
  private $quote;
  private $escape;
  private $recordEnd;

  private $records;
  private $fields;
  private $field;

  private $machine;

  private $lastCharType;
  private $quoted = FALSE;

  private $trailingDelimiter = FALSE;

  public function activateTrailingDelimiter() {
    $this->trailingDelimiter = TRUE;
  }

  public static function getParser($delimiter = ",", $quote = '"', $escape = "\\", $record_end = ["\n", "\r"])
  {
    return new self($delimiter, $quote, $escape, $record_end);
  }

  public function __construct($delimiter, $quote, $escape, array $record_end)
  {
    $this->recordEnd = $record_end;
    $this->delimiter = $delimiter;
    $this->quote = $quote;
    $this->escape = $escape;
    $this->reset();
    $this->machine = $this->getMachine();
  }

  public function feed(string $chunk)
  {
    if (strlen($chunk) > 0) {
      $chars = str_split($chunk);
      foreach ($chars as $char) {
        $initial_states = $this->machine->getCurrentStates();
        $char_type = $this->getCharType($char);

        $this->machine->processInput($char_type);

        $this->lastCharType = $char_type;
        $end_states = $this->machine->getCurrentStates();

        $this->processTransition($initial_states, $end_states, $char);
      }
    }
    else {
      throw new \Exception("The CSV parser can not parse empty chunks.");
    }
  }

  public function getRecord()
  {
    $record = array_shift($this->records);

    if (isset($record)) {
      $last_field = array_pop($record);
      if ($this->trailingDelimiter && empty($last_field)) {
        return $record;
      } else {
        array_push($record, $last_field);
      }
    }

    return $record;
  }

  public function reset()
  {
    $this->field = "";
    $this->fields = [];
    $this->records = [];

    $this->machine = $this->getMachine();
    $this->lastCharType = null;
    $this->quoted = FALSE;
  }

  public function finish()
  {
    // There will be csv strings that do not end in a "end of record" char.
    // This will flush them.
    if ($this->lastCharType != self::CHAR_TYPE_RECORD_END) {
      $this->feed($this->recordEnd[0]);
    }

    // We just flushed the machine. This should never happen.
    if (!$this->machine->isCurrentlyAtAnEndState()) {
      throw new \Exception("Machine did not halt");
    }
  }

  private function getMachine() {
    $machine = new MachineOfMachines([self::STATE_NEW_FIELD]);
    $machine->addEndState(self::STATE_NEW_FIELD);
    $machine->addEndState(self::STATE_RECORD_END);

    // NEW FIELD.
    $machine->addTransition(self::STATE_NEW_FIELD,[
      self::CHAR_TYPE_DELIMITER,
      self::CHAR_TYPE_RECORD_END
    ], self::STATE_NEW_FIELD);

    $machine->addTransition(self::STATE_NEW_FIELD,[
      self::CHAR_TYPE_BLANK,
    ], self::STATE_NO_CAPTURE);

    $machine->addTransition(self::STATE_NEW_FIELD,[
      self::CHAR_TYPE_OTHER
    ], self::STATE_CAPTURE);

    $machine->addTransition(self::STATE_NEW_FIELD,[self::CHAR_TYPE_QUOTE], self::STATE_QUOTE_INITIAL);

    // NO CAPTURE
    $machine->addTransition(self::STATE_NO_CAPTURE,[
      self::CHAR_TYPE_BLANK,
    ], self::STATE_NO_CAPTURE);

    $machine->addTransition(self::STATE_NO_CAPTURE,[
      self::CHAR_TYPE_OTHER,
    ], self::STATE_CAPTURE);

    $machine->addTransition(self::STATE_NO_CAPTURE,[
      self::CHAR_TYPE_QUOTE,
    ], self::STATE_QUOTE_INITIAL);

    $machine->addTransition(self::STATE_NO_CAPTURE,[
      self::CHAR_TYPE_DELIMITER,
      self::CHAR_TYPE_RECORD_END,
    ], self::STATE_NEW_FIELD);

    $machine->addTransition(self::STATE_NO_CAPTURE,[
      self::CHAR_TYPE_ESCAPE
    ], self::STATE_ESCAPE);

    // CAPTURE.
    $machine->addTransition(self::STATE_CAPTURE,[
      self::CHAR_TYPE_OTHER,
      self::CHAR_TYPE_BLANK,
      ], self::STATE_CAPTURE);

    $machine->addTransition(self::STATE_CAPTURE,[
      self::CHAR_TYPE_ESCAPE,
    ], self::STATE_ESCAPE);

    $machine->addTransition(self::STATE_CAPTURE,[
      self::CHAR_TYPE_RECORD_END,
      self::CHAR_TYPE_DELIMITER
    ], self::STATE_NEW_FIELD);

    $machine->addTransition(self::STATE_CAPTURE,[self::CHAR_TYPE_ESCAPE], self::STATE_ESCAPE);

    // ESCAPE.
    $machine->addTransition(self::STATE_ESCAPE,[
      self::CHAR_TYPE_DELIMITER,
      self::CHAR_TYPE_QUOTE,
      self::CHAR_TYPE_ESCAPE,
      self::CHAR_TYPE_RECORD_END,
      self::CHAR_TYPE_BLANK,
      self::CHAR_TYPE_OTHER,
    ], self::STATE_CAPTURE);

    // QUOTE INITIAL.
    $machine->addTransition(self::STATE_QUOTE_INITIAL,[
      self::CHAR_TYPE_DELIMITER,
      self::CHAR_TYPE_ESCAPE,
      self::CHAR_TYPE_RECORD_END,
      self::CHAR_TYPE_BLANK,
      self::CHAR_TYPE_OTHER,
    ], self::STATE_QUOTE_CAPTURE);

    $machine->addTransition(self::STATE_QUOTE_INITIAL,[self::CHAR_TYPE_QUOTE], self::STATE_QUOTE_ESCAPE_QUOTE);
    $machine->addTransition(self::STATE_QUOTE_INITIAL,[self::CHAR_TYPE_QUOTE], self::STATE_QUOTE_FINAL);

    // QUOTE CAPTURE.
    $machine->addTransition(self::STATE_QUOTE_CAPTURE,[
      self::CHAR_TYPE_DELIMITER,
      self::CHAR_TYPE_RECORD_END,
      self::CHAR_TYPE_BLANK,
      self::CHAR_TYPE_OTHER,
    ], self::STATE_QUOTE_CAPTURE);

    $machine->addTransition(self::STATE_QUOTE_CAPTURE,[
      self::CHAR_TYPE_ESCAPE,
    ], self::STATE_QUOTE_ESCAPE);

    $machine->addTransition(self::STATE_QUOTE_CAPTURE,[
      self::CHAR_TYPE_QUOTE,
    ], self::STATE_QUOTE_FINAL);

    $machine->addTransition(self::STATE_QUOTE_CAPTURE,[
      self::CHAR_TYPE_QUOTE,
    ], self::STATE_QUOTE_ESCAPE_QUOTE);

    // QUOTE ESCAPE QUOTE
    $machine->addTransition(self::STATE_QUOTE_ESCAPE_QUOTE,[
      self::CHAR_TYPE_QUOTE,
    ], self::STATE_QUOTE_CAPTURE);

    // QUOTE ESCAPE
    $machine->addTransition(self::STATE_QUOTE_ESCAPE,[
      self::CHAR_TYPE_ESCAPE,
      self::CHAR_TYPE_DELIMITER,
      self::CHAR_TYPE_RECORD_END,
      self::CHAR_TYPE_BLANK,
      self::CHAR_TYPE_OTHER,
      self::CHAR_TYPE_QUOTE,
    ], self::STATE_QUOTE_CAPTURE);

    // QUOTE FINAL.
    $machine->addTransition(self::STATE_QUOTE_FINAL,[
      self::CHAR_TYPE_BLANK,
    ], self::STATE_QUOTE_FINAL);

    $machine->addTransition(self::STATE_QUOTE_FINAL,[
      self::CHAR_TYPE_DELIMITER,
      self::CHAR_TYPE_RECORD_END
    ], self::STATE_NEW_FIELD);

    return $machine;
  }

  /**
   * Private method.
   */
  private function getCharType($char) {
    if (in_array($char, $this->recordEnd)) {
      return self::CHAR_TYPE_RECORD_END;
    }
    if ($char == $this->delimiter) {
      return self::CHAR_TYPE_DELIMITER;
    }
    if ($char == $this->quote) {
      return self::CHAR_TYPE_QUOTE;
    }
    if ($char == $this->escape) {
      return self::CHAR_TYPE_ESCAPE;
    }
    if (ctype_space($char)) {
      return self::CHAR_TYPE_BLANK;
    }
    return self::CHAR_TYPE_OTHER;
  }

  /**
   * Private method.
   */
  private function addCharToField($char) {
    $this->field .= $char;
  }

  /**
   * Private method.
   */
  private function createNewRecord() {
    $this->createNewField();
    $this->records[] = $this->fields;
    $this->fields = [];
  }

  /**
   * Private method.
   */
  private function createNewField() {
    if ($this->quoted) {
      $this->fields[] = $this->field;
      $this->quoted = FALSE;
    }
    else {
      $this->fields[] = trim($this->field, " ");
    }

    $this->field = "";
  }

  private function processTransition($initialStates, $endStates, $input) {
    foreach ($endStates as $endState) {
      if ($this->lastCharType == self::CHAR_TYPE_RECORD_END && $endState == self::STATE_NEW_FIELD) {
        $this->createNewRecord();
      }
      else if ($endState == self::STATE_NEW_FIELD) {
        $this->createNewField();
      }
      else if ($endState == self::STATE_CAPTURE || $endState == self::STATE_QUOTE_CAPTURE) {
        $this->addCharToField($input);
      }
      else if ($endState == self::STATE_QUOTE_INITIAL) {
        $this->quoted = TRUE;
      }
    }
  }
}
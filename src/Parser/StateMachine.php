<?php
namespace CsvParser\Parser;

use Maquina\StateMachine\MachineOfMachines;

class StateMachine extends MachineOfMachines
{
    public const STATE_NEW_FIELD = "s_new_field";
    public const STATE_CAPTURE = "s_capture";
    public const STATE_NO_CAPTURE = "s_no_capture";
    public const STATE_ESCAPE = "s_escape";
    public const STATE_RECORD_END = "s_record_end";
    public const STATE_REDUNDANT_RECORD_END = "s_redundant_record_end";

    public const STATE_QUOTE_INITIAL = "s_q_initial";
    public const STATE_QUOTE_FINAL = "s_q_final";
    public const STATE_QUOTE_CAPTURE = "s_q_capture";
    public const STATE_QUOTE_ESCAPE = "s_q_escape";
    public const STATE_QUOTE_ESCAPE_QUOTE = "s_q_escape_quote";

    public const CHAR_TYPE_DELIMITER = "c_delimiter";
    public const CHAR_TYPE_QUOTE = "c_quote";
    public const CHAR_TYPE_ESCAPE = "c_escape";
    public const CHAR_TYPE_RECORD_END = "c_record_end";
    public const CHAR_TYPE_BLANK = "c_blank";
    public const CHAR_TYPE_OTHER = "c_other";

    public function __construct()
    {
        parent::__construct([self::STATE_NEW_FIELD]);

        $this->addEndState(self::STATE_NEW_FIELD);
        $this->addEndState(self::STATE_RECORD_END);
        $this->addEndState(self::STATE_REDUNDANT_RECORD_END);

        $this->addNewFieldTransitions();
        $this->addNoCaptureTransitions();
        $this->addRecordEndTransitions();
        $this->addRedundantRecordEndTranstions();
        $this->addCaptureTransitions();
        $this->addEscapeTransitions();
        $this->addQuoteInitialTransitions();
        $this->addQuoteCaptureTransitions();
        $this->addQuoteEscapeQuoteTransitions();
        $this->addQuoteEscapeTransitions();
        $this->addQuoteFinalTransitions();
    }

    private function addNewFieldTransitions()
    {
        $this->recordEndAndNewFieldCommons(self::STATE_NEW_FIELD);

        $this->addTransition(
            self::STATE_NEW_FIELD,
            [self::CHAR_TYPE_RECORD_END],
            self::STATE_RECORD_END
        );
    }

    private function noCaptureAndRedundantRecordEndCommon($state)
    {
        $this->addTransition(
            $state,
            [self::CHAR_TYPE_BLANK],
            self::STATE_NO_CAPTURE
        );

        $this->addTransition(
            $state,
            [self::CHAR_TYPE_OTHER],
            self::STATE_CAPTURE
        );

        $this->addTransition(
            $state,
            [self::CHAR_TYPE_QUOTE],
            self::STATE_QUOTE_INITIAL
        );

        $this->addTransition(
            $state,
            [self::CHAR_TYPE_DELIMITER],
            self::STATE_NEW_FIELD
        );

        $this->addTransition(
            $state,
            [self::CHAR_TYPE_ESCAPE],
            self::STATE_ESCAPE
        );
    }

    private function addNoCaptureTransitions()
    {
        $this->noCaptureAndRedundantRecordEndCommon(self::STATE_NO_CAPTURE);

        $this->addTransition(
            self::STATE_NO_CAPTURE,
            [self::CHAR_TYPE_RECORD_END],
            self::STATE_RECORD_END
        );
    }

    private function recordEndAndNewFieldCommons($state)
    {
        $this->addTransition(
            $state,
            [self::CHAR_TYPE_DELIMITER],
            self::STATE_NEW_FIELD
        );

        $this->addTransition(
            $state,
            [self::CHAR_TYPE_BLANK],
            self::STATE_NO_CAPTURE
        );

        $this->addTransition(
            $state,
            [self::CHAR_TYPE_OTHER],
            self::STATE_CAPTURE
        );

        $this->addTransition(
            $state,
            [self::CHAR_TYPE_QUOTE],
            self::STATE_QUOTE_INITIAL
        );
    }

    private function addRecordEndTransitions()
    {
        $this->recordEndAndNewFieldCommons(self::STATE_RECORD_END);

         $this->addTransition(
             self::STATE_RECORD_END,
             [self::CHAR_TYPE_RECORD_END],
             self::STATE_REDUNDANT_RECORD_END
         );
    }

    private function addRedundantRecordEndTranstions()
    {
        $this->noCaptureAndRedundantRecordEndCommon(self::STATE_REDUNDANT_RECORD_END);

        $this->addTransition(
            self::STATE_REDUNDANT_RECORD_END,
            [self::CHAR_TYPE_RECORD_END],
            self::STATE_REDUNDANT_RECORD_END
        );
    }

    private function addCaptureTransitions()
    {
        $this->addTransition(
            self::STATE_CAPTURE,
            [
              self::CHAR_TYPE_OTHER,
              self::CHAR_TYPE_BLANK,
            ],
            self::STATE_CAPTURE
        );

        $this->addTransition(
            self::STATE_CAPTURE,
            [self::CHAR_TYPE_ESCAPE],
            self::STATE_ESCAPE
        );

        $this->addTransition(
            self::STATE_CAPTURE,
            [self::CHAR_TYPE_DELIMITER],
            self::STATE_NEW_FIELD
        );

        $this->addTransition(
            self::STATE_CAPTURE,
            [self::CHAR_TYPE_RECORD_END],
            self::STATE_RECORD_END
        );
    }

    private function addEscapeTransitions()
    {
        $this->addTransition(
            self::STATE_ESCAPE,
            [
              self::CHAR_TYPE_DELIMITER,
              self::CHAR_TYPE_QUOTE,
              self::CHAR_TYPE_ESCAPE,
              self::CHAR_TYPE_RECORD_END,
              self::CHAR_TYPE_BLANK,
              self::CHAR_TYPE_OTHER,
            ],
            self::STATE_CAPTURE
        );
    }

    private function addQuoteInitialTransitions()
    {
        $this->addTransition(
            self::STATE_QUOTE_INITIAL,
            [
              self::CHAR_TYPE_DELIMITER,
              self::CHAR_TYPE_ESCAPE,
              self::CHAR_TYPE_RECORD_END,
              self::CHAR_TYPE_BLANK,
              self::CHAR_TYPE_OTHER,
            ],
            self::STATE_QUOTE_CAPTURE
        );

        $this->addTransition(
            self::STATE_QUOTE_INITIAL,
            [self::CHAR_TYPE_QUOTE],
            self::STATE_QUOTE_ESCAPE_QUOTE
        );

        $this->addTransition(
            self::STATE_QUOTE_INITIAL,
            [self::CHAR_TYPE_QUOTE],
            self::STATE_QUOTE_FINAL
        );
    }

    private function addQuoteCaptureTransitions()
    {
        $this->addTransition(
            self::STATE_QUOTE_CAPTURE,
            [
              self::CHAR_TYPE_DELIMITER,
              self::CHAR_TYPE_RECORD_END,
              self::CHAR_TYPE_BLANK,
              self::CHAR_TYPE_OTHER,
            ],
            self::STATE_QUOTE_CAPTURE
        );

        $this->addTransition(
            self::STATE_QUOTE_CAPTURE,
            [self::CHAR_TYPE_ESCAPE],
            self::STATE_QUOTE_ESCAPE
        );

        $this->addTransition(
            self::STATE_QUOTE_CAPTURE,
            [self::CHAR_TYPE_QUOTE],
            self::STATE_QUOTE_FINAL
        );

        $this->addTransition(
            self::STATE_QUOTE_CAPTURE,
            [self::CHAR_TYPE_QUOTE],
            self::STATE_QUOTE_ESCAPE_QUOTE
        );
    }

    private function addQuoteEscapeQuoteTransitions()
    {
        $this->addTransition(
            self::STATE_QUOTE_ESCAPE_QUOTE,
            [self::CHAR_TYPE_QUOTE],
            self::STATE_QUOTE_CAPTURE
        );
    }

    private function addQuoteEscapeTransitions()
    {
        $this->addTransition(
            self::STATE_QUOTE_ESCAPE,
            [
              self::CHAR_TYPE_ESCAPE,
              self::CHAR_TYPE_DELIMITER,
              self::CHAR_TYPE_RECORD_END,
              self::CHAR_TYPE_BLANK,
              self::CHAR_TYPE_OTHER,
              self::CHAR_TYPE_QUOTE,
            ],
            self::STATE_QUOTE_CAPTURE
        );
    }

    private function addQuoteFinalTransitions()
    {
        $this->addTransition(
            self::STATE_QUOTE_FINAL,
            [self::CHAR_TYPE_BLANK],
            self::STATE_QUOTE_FINAL
        );

        $this->addTransition(
            self::STATE_QUOTE_FINAL,
            [self::CHAR_TYPE_DELIMITER],
            self::STATE_NEW_FIELD
        );

        $this->addTransition(
            self::STATE_QUOTE_FINAL,
            [self::CHAR_TYPE_RECORD_END],
            self::STATE_RECORD_END
        );
    }
}

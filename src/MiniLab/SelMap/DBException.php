<?php

namespace MiniLab\SelMap;

class DBException extends \Exception {
    public $sql = "";
    public $dbMessage = "";
    public $sormLine;
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
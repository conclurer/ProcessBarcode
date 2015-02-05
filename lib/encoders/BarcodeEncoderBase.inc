<?php

abstract class BarcodeEncoderBase extends Wire {

    protected $code;

    public function __construct($code=null) {
        $this->code = $code;
    }

    abstract public function bars();
    abstract public function text();
    abstract public function encoding();

    public function __set($x, $y) {
        if ($x == 'code') $this->code = $y;
    }
} 
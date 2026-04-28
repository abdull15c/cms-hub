<?php
namespace Src\Core;

class Validator {
    private $errors = [];
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function required($field) {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field][] = "Field $field is required.";
        }
        return $this;
    }

    public function email($field) {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Invalid email format.";
        }
        return $this;
    }

    public function min($field, $len) {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $len) {
            $this->errors[$field][] = "Must be at least $len characters.";
        }
        return $this;
    }

    public function numeric($field) {
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = "Must be a number.";
        }
        return $this;
    }

    public function passes() {
        return empty($this->errors);
    }

    public function errors() {
        return $this->errors;
    }
    
    public function firstError() {
        foreach($this->errors as $field => $errs) return $errs[0];
        return null;
    }
}
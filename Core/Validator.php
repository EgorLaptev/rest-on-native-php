<?php

include 'connect.php';

Validator::$db = $pdo;

class Validator
{

    public static $db; // Connect to DB

    public $errors = [];
    public $valid  = [];

    public function unique($prop, $value, $table)
    {
        return self::$db->query("SELECT 1 FROM `$table` WHERE `$prop` = '$value' LIMIT 1")->rowCount();
    }

    public function required($value)
    {
        return isset($value) && !empty($value);
    }

    public function exists($prop, $value, $table)
    {
        return self::$db->query("SELECT 1 FROM `$table` WHERE `$prop` = '$value' LIMIT 1")->rowCount();
    }

    public function isString($value)
    {
        return gettype($value) === 'string';
    }

    public function length($value, $length){
        return strlen($value) == $length;
    }

    public function min($value, $min_value) {
        return $value >= $min_value;
    }

    public function max($value, $max_value) {
        return $value <= $max_value;
    }

    public function dateFormat($date, $format) {
        return !!DateTime::createFromFormat($format, $date);
    }

    public function validate($data, $validation)
    {

        foreach ($validation as $prop => $rules) {

            $rules = array_reverse($rules); // Reverse to set more important rules at start

            foreach ($rules as $rule) {

                $rule = explode(':', $rule);

                switch ($rule[0]) {
                    case 'string':
                        if (!$this->isString($data[$prop])) $this->errors[$prop] = "The $prop field must be string!";
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'unique':
                        if ($this->unique($prop, $data[$prop], $rule[1])) $this->errors[$prop] = "The $prop field must be unique!";
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'required':
                        if (!$this->required($data[$prop])) $this->errors[$prop] = "The $prop field is required!";
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'length':
                        if (!$this->length($data[$prop], $rule[1])) $this->errors[$prop] = "The $prop field's length must be ".$rule[1];
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'exists':
                        if (!$this->exists($rule[2], $data[$prop], $rule[1])) $this->errors[$prop] = "The $prop field's must be exists in ".$rule[1]." table";
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'date':
                        if (!$this->dateFormat($data[$prop], $rule[1]) && $this->required($data[$prop])) $this->errors[$prop] = "The $prop field's has format error!";
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'max':
                        if (!$this->max($data[$prop], $rule[1])) $this->errors[$prop] = "The max value for the property '$prop' is ".$rule[1];
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'min':
                        if (!$this->min($data[$prop], $rule[1])) $this->errors[$prop] = "The min value for the property '$prop' is ".$rule[1];
                        else $this->valid[$prop] = $data[$prop];
                        break;

                }

            }

        }

        if (!empty($this->errors)) {
            header('HTTP/1.1 422 Validation error');
            echo json_encode([
               "error" => [
                   "code" => 422,
                   "message" => "Validation error",
                   "errors" => $this->errors
               ]
            ]);
        }

    }

}


<?php

include 'connect.php';

Validate::$pdo = $pdo;

class Validate
{

    public static $pdo; // Connect to DB

    public $errors = [];
    public $valid  = [];

    public function unique($prop, $value, $table)
    {
        return self::$pdo->query("SELECT 1 FROM `$table` WHERE `$prop` = '$value' LIMIT 1")->rowCount();
    }

    public function exists($value)
    {
        return isset($value) && !empty($value);
    }

    public function isString($value)
    {
        return gettype($value) === 'string';
    }

    public function isInteger($value)
    {
        return gettype($value) === 'integer';
    }

    public function isBoolean($value)
    {
        return gettype($value) === 'boolean';
    }

    public function length($value, $length){
        return strlen($value) == $length;
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
                    case 'integer':
                        if (!$this->isInteger($data[$prop])) $this->errors[$prop] = "The $prop field must be integer!";
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'boolean':
                        if (!$this->isBoolean($data[$prop])) $this->errors[$prop] = "The $prop field must be boolean!";
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'unique':
                        if ($this->unique($prop, $data[$prop], $rule[1])) $this->errors[$prop] = "The $prop field must be unique!";
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'required':
                        if (!$this->exists($data[$prop])) $this->errors[$prop] = "The $prop field is required!";
                        else $this->valid[$prop] = $data[$prop];
                        break;
                    case 'length':
                        if (!$this->length($data[$prop], $rule[1])) $this->errors[$prop] = "The $prop field's length must be ".$rule[1];
                        else $this->valid[$prop] = $data[$prop];
                        break;

                }


            }
        }

    }

}


<?php

namespace Qlcorp\VextFramework;

class VextValidate {
    protected $rules = array();

    public function reset() {
        $this->rules = array();
    }

    public function minLength($minLength, $minLengthText = null) {
        $this->setRule(compact('minLength'));
        $this->setMessage($this->rules, 'minLengthText', $minLengthText);
    }

    public function maxLength($maxLength, $maxLengthText = null) {
        $this->setRule(compact('maxLength'));
        $this->setMessage($this->rules, 'maxLengthText', $maxLengthText);
    }

    public function minValue($minValue, $minText = null) {
        $this->setRule(compact('minValue'));
        $this->setMessage($this->rules, 'minText', $minText);
    }

    public function maxValue($maxValue, $maxText = null) {
        $this->setRule(compact('maxValue'));
        $this->setMessage($this->rules, 'maxText', $maxText);
    }

    protected function setRule($rule) {
        $this->rules = array_merge($this->rules, $rule);
    }

    public function getRules() {
        return $this->rules;
    }

    protected function setMessage(&$array, $key, $var, $value = null) {
        $value = ($value === null) ? $var : $value;
        if ( !empty($var) ) {
            $array[$key] = $value;
        }

        return $array;
    }


}
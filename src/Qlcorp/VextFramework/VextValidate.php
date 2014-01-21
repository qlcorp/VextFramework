<?php

namespace Qlcorp\VextFramework;

class VextValidate {
    protected $rules = array();

    public function minLength($minLength, $minLengthText = null) {
        $this->setRule(compact('minLength'));
        $this->setOption($this->rules, 'minLengthText', $minLengthText);
    }

    public function maxLength($maxLength, $maxLengthText = null) {
        $this->setRule(compact('maxLength'));
        $this->setOption($this->rules, 'maxLengthText', $maxLengthText);
    }

    public function min($min, $minText = null) {
        $this->setRule(compact('min'));
        $this->setOption($this->rules, 'minText', $minText);
    }

    public function max($max, $maxText = null) {
        $this->setRule(compact('max'));
        $this->setOption($$this->rules, 'maxText', $maxText);
    }

    protected function setRule($rule) {
        $this->rules = array_merge($this->rules, $rule);
    }

    public function getRules() {
        return $this->rules;
    }

    protected function setOption(&$array, $key, $var, $value = null) {
        $value = ($value === null) ? $var : $value;
        if ( !empty($var) ) {
            $array[$key] = $value;
        }

        return $array;
    }

}
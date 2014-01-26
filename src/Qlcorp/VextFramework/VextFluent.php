<?php namespace Qlcorp\VextFramework;

use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Fluent;
use Closure;
use Qlcorp\VextFramework\Facades\VextValidate;

class VextFluent extends Fluent implements JsonableInterface, ArrayableInterface {

    protected $blueprint = null;

    // -- column ---
    protected $grid = null;
    protected $rules = array();
    protected $dropdown = array();
    protected $vtype = null;
    protected $fieldLabel = null;
    protected $fieldWidth = null;
    protected $regex = null;
    protected $required = null;
    protected $fillable = false;
    protected $fieldConfig = array();

    public function __construct($attributes = array(), VextBlueprint $blueprint) {
        parent::__construct($attributes);
        $this->blueprint = $blueprint;
    }

    public function getRules() {
        return $this->rules;
    }

    public function getFieldConfig() {
        return $this->fieldConfig;
    }

    public function getName() {
        return $this->attributes['name'];
    }

    public function getType() {
        return $this->attributes['type'];
    }

    public function getRequired() {
        return $this->required;
    }
    //Fillable

    public function fillable() {
        return $this->blueprint->getCurrentColumn()->setFillable();
    }

    protected function setFillable() {
        $this->fillable = true;
        $this->blueprint->addFillable($this->attributes['name']);
        return $this;
    }

    //Requires/AllowBlank
    public function required($required = true) {
        return $this->blueprint->getCurrentColumn()->setRequired($required);
    }

    protected function setRequired($required) {
        $this->required = (bool) $required;
        return $this;
    }

    //regex
    public function regex($regex) {
        return $this->blueprint->getCurrentColumn()->setRegex($regex);
    }

    protected function setRegex($regex) {
        $this->regex = $regex;
        return $this;
    }

    //Field Label/Width
    public function field($label, $width = null) {
        return $this->blueprint->getCurrentColumn()->setField($label, $width);
    }

    protected function setField($label, $width) {
        $this->fieldLabel = $label;
        $this->fieldWidth = $width;
        return $this;
    }

    //Grid
    public function grid($name, $length = null) {
        return $this->blueprint->getCurrentColumn()->setGrid($name, $length);
    }

    protected function setGrid($title, $width = null) {
        $this->grid = compact('title', 'width');
        return $this;
    }

    //Dropdown
    public function dropdown($elements) {
        return $this->blueprint->getCurrentColumn()->setDropdown($elements);
    }

    protected function setDropdown($elements) {
        foreach($elements as $key => $value) {
            $this->dropdown[] = compact('key', 'value');
        }
        return $this;
    }

    //Vtype
    public function vtype($vtype) {
        return $this->blueprint->getCurrentColumn()->setVtype($vtype);
    }

    protected function setVtype($vtype) {
        $this->vtype = $vtype;
        return $this;
    }

    //Validations
    public function validation(Closure $callback) {
        return $this->setValidation($callback);
    }

    protected function setValidation(Closure $callback) {
        $callback();
        $this->rules = VextValidate::getRules();
        return $this;
    }

    public function toJson($options = 0) {
        return json_encode($this->toArray());
    }

    public function toArray() {
        $this->setOption($field, 'name', $this->getName());
        $this->setOption($field, 'type', $this->getType());
        $this->setOption($field, 'fieldConfig', $this->fieldConfig());
        $this->setOption($field, 'grid', $this->grid);
        $this->setOption($field, 'dropdown', $this->dropdown);

        return $field;
    }

    protected function fieldConfig() {
        //TODO: xtype?
        $field = array();
        $this->setOption($field, 'vtype', $this->vtype);
        $this->setOption($field, 'fieldLabel', $this->fieldLabel);
        $this->setOption($field, 'fieldWidth', $this->fieldWidth);
        $this->setOption($field, 'regex', $this->regex);
        $this->setOption($field, 'allowBlank', $this->required, !$this->required);
        $this->setOption($field, 'fillable', $this->fillable);

        $this->fieldConfig = $field = array_merge($field, $this->rules);
        return $field;
    }

    /**
     * Set array[$key] to $value if $var is not empty
     *
     * $value = $var if $value is not set
     *
     * @param $array
     * @param $key
     * @param $var
     * @param null $value
     * @return mixed
     */
    protected function setOption(&$array, $key, $var, $value = null) {
        $value = ($value === null) ? $var : $value;

        if ( !empty($var) ) {
            $array[$key] = $value;
        }

        return $array;
    }


} 
<?php namespace Qlcorp\VextFramework;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;

/**
 * Class VextBlueprint
 *
 * Available Validation Rules:
 * required
 * date
 * min
 * max
 * range
 * grid
 *
 */
class VextBlueprint extends Blueprint implements JsonableInterface, ArrayableInterface {

    protected $current_column;
    protected $current_name;
    protected $model_name = '';
    protected $fillable = array();
    protected $timestamps = 'false';

    public function addFillable($col) {
        $this->fillable[] = $col;
    }

    public function timestamps() {
        parent::timestamps();
        $this->timestamps = 'true';
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param  string  $type
     * @param  string  $name
     * @param  array   $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function addColumn($type, $name, array $parameters = array()) {
        $attributes = array_merge(compact('type', 'name'), $parameters);
        $this->columns[] = $this->current_column = new VextFluent($attributes, $this);
        //$column = parent::addColumn($type, $name, $parameters);
        //$this->current_column = new VextFluent($column->getAttributes(),$this);

        $this->current_name = $name;

        return $this->current_column;
    }

    public function model($model) {
        $this->model_name = $model;
    }

    public function getModel() {
        return $this->model_name;
    }

    public function getCurrentColumn() {
        return $this->current_column;
    }
    public function getCurrentName() {
        return $this->current_name;
    }

    public function toJson($options = 0) {
        return json_encode($this->toArray());
    }

    public function toArray() {
        $fields = array();
        foreach ($this->columns as $column) {
            $fields[] = $column->toArray();
        }
        return array('fields' => $fields);
    }

    public function laravelModel() {
        $str = "<?php
class $this->model_name extends Base$this->model_name {

}
";

        return $str;
    }

    public function laravelBaseModel() {
        $fillable = 'array(\'' . implode('\', \'', $this->fillable) . '\')';
        $rules = array();
        foreach ($this->columns as $column) {
            //$config = $column->getFieldConfig();
            $ruleList = array();
            $validations = $column->getRules();
            $name = $column->getName();

            if ( ($required = $column->getRequired()) !== null )  {
                $ruleList[] = 'required';
            }

            if ( isset($validations['minLength']) )  {
                $ruleList[] = 'min:' . $validations['minLength'];
            }

            if ( !empty($ruleList) ) {
                $ruleList = implode($ruleList, '|');
                $rules[] = "'$name' => '$ruleList'";
            }
        }
        $rules = 'array(' . implode($rules, ',') . ')';

        $str = "<?php
use Qlcorp\VextFramework\CrudModel;

class Base$this->model_name extends CrudModel {
    protected \$table = '$this->table';
    public \$timestamps = $this->timestamps;
    protected \$fillable = $fillable;
    protected \$rules = $rules;
} ";

        return $str;
    }

}
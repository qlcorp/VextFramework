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
    protected $tree = false;
    protected $parentKey = null;

    public function addFillable($col) {
        $this->fillable[] = $col;
        return $this;
    }

    public function timestamps() {
        $this->timestamps = 'true';
        return parent::timestamps();
    }

    //todo: make this happen after blueprint is done so primarykey can be dynamic
    public function tree() {
        $this->tree = true;

        $this->bigInteger('parent_id')->nullable()->fillable();
        $this->bigInteger('index')->fillable();
        $this->string('text', 200)->fillable();

        $this->unique(array('parent_id', 'index'));

        $this->foreign('parent_id')
            ->references('id')
            ->on($this->table)
            ->onDelete('cascade');

        return $this;
    }

    //todo: make this a function of VextFluent for foreignKey purposes
    public function parent($parentKey) {
        $this->parentKey = $parentKey;
        return $this;
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
        return array($fields);
    }

    public function laravelModel() {
        $stub = $this->getStub('model.stub');
        $stub = str_replace('{{model}}', $this->model_name , $stub);
        return $stub;
    }

    public function laravelBaseModel() {
        $fillable = '\'' . implode("',\r\n\t\t'", $this->fillable) . '\'';
        $rules = array();
        $messages = array();
        foreach ($this->columns as $column) {
            //$config = $column->getFieldConfig();
            $laravelRules = array();
            $extJsRules = $column->getRules();
            $name = $column->getName();

            if ( ($required = $column->getRequired()) !== null )  {
                $laravelRules[] = 'required';
            }

            $this->addRule($laravelRules, $extJsRules, 'min', 'minLength');
            $this->addRule($laravelRules, $extJsRules, 'max', 'maxLength');
            $this->addRule($laravelRules, $extJsRules, 'min', 'minValue');
            $this->addRule($laravelRules, $extJsRules, 'max', 'maxValue');
            if ( isset($extJsRules['minText']) ) {
                $messages[] = "'$name.min' => '" . $extJsRules['minText'] . '\'';
            }

            if ( !empty($laravelRules) ) {
                $laravelRules = implode($laravelRules, '|');
                $rules[] = "'$name' => '$laravelRules'";
            }
        }

        $rules = implode($rules, ",\r\n\t\t");
        $messages = implode($messages, "\r\n\t\t");
        if ($this->tree) {
            $stub = $this->getStub('base_tree_model.stub');
        } else {
            $stub = $this->getStub('base_model.stub');
        }

        $parentKey = $this->parentKey;

        return $this->populateStub($stub, compact('fillable', 'rules', 'messages', 'parentKey'));

    }

    protected function addRule(&$laravelRules, $extJsRules, $laravelName, $extJsName) {
        if ( isset($extJsRules[$extJsName]) )  {
            $laravelRules[] = $laravelName . ':' . $extJsRules[$extJsName];
        }

        return $laravelRules;
    }

    protected function getStubPath() {
        return __DIR__ . '/stubs';
    }

    protected function getStub($name) {
        return file_get_contents($this->getStubPath() . "/{$name}");
    }

    protected function populateStub($stub, $data = array()) {
        $stub = str_replace('{{model}}', $this->model_name, $stub);
        $stub = str_replace('{{table}}', $this->table, $stub);
        $stub = str_replace('{{timestamps}}', $this->timestamps, $stub);
        $stub = str_replace('{{fillable}}', $data['fillable'], $stub);
        $stub = str_replace('{{rules}}', $data['rules'], $stub);
        $stub = str_replace('{{messages}}', $data['messages'], $stub);
        $stub = str_replace('{{parentKey}}', $data['parentKey'], $stub);
        return $stub;
    }

}
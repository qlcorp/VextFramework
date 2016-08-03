<?php namespace Qlcorp\VextFramework;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Facades\Config;

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
 * @method VextFluent[] getColumns()
 *
 */
class VextBlueprint extends Blueprint implements JsonableInterface, ArrayableInterface
{

    protected $current_column;
    protected $current_name;
    protected $model_name = '';
    protected $fillable = array();
    protected $timestamps = 'false';
    protected $userstamps = 'false';
    protected $tree = false;
    protected $parentKey = null;
    protected $pretend = false;
    protected $relationships = array();
    protected $with = array();
    protected $appends = array();
    protected $showTimestampsInGrid = false;

    /**
     * Add a new column to the blueprint.
     *
     * @param  string $type
     * @param  string $name
     * @param  array  $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function addColumn($type, $name, array $parameters = array())
    {
        $attributes = array_merge(compact('type', 'name'), $parameters);
        $column = new VextFluent($attributes, $this);

        $this->columns[] = $this->current_column = $column;
        $this->current_name = $name;

        $column->validation(function (VextValidate $validate) use ($column) {
            $type = $column->getType();

            if ($length = $column->length) {
                if ($type === 'string') {
                    $validate->maxLength($length);
                }
            }
        });

        return $this->current_column;
    }

    public function date($name)
    {
        return $this->addColumn('date', $name, array('dateFormat' => 'Y-m-d'));
    }

    public function appends($name, $type)
    {
        //$this->appends[] = $name;
        //$this->fillable = array_merge($this->fillable, $attributes);
        $attributes = compact('name', 'type');
        $this->appends[] = $this->current_column = new VextFluent($attributes, $this);

        return $this->current_column;
    }

    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $type = 'hasMany';
        $this->relationships[] = compact('type', 'related', 'foreignKey', 'localKey');
        $this->with[] = camel_case($related);
    }

    public function addFillable($col)
    {
        $this->fillable[] = $col;

        return $this;
    }

    public function timestamp($column)
    {
        $timestamp = parent::timestamp($column);
        $label = ucfirst(str_replace('_', ' ', $column));
        $timestamp->dateFormat = 'Y-m-d';
        $timestamp = $timestamp->fieldConfig(array(
            'fieldLabel' => $label,
        ));

        if ($this->showTimestampsInGrid) {
            $timestamp->gridConfig(array(
                'text' => $label,
                'width' => 150,
            ));
        }

        return $timestamp;
    }

    public function timestamps($showInGrid = false)
    {
        $this->timestamps = 'true';
        $this->showTimestampsInGrid = $showInGrid;

        parent::timestamps();
    }

    public function userstamp($column)
    {
        //$this->with[] = camel_case($column);
        $this->unsignedInteger($column)
            ->fieldConfig(array(
                'fieldLabel' => ucfirst(str_replace('_', ' ', $column)),
            ));

        $this->foreign($column)
            ->references('id')->on(Config::get('auth.table', 'users'));
    }

    public function userstamps()
    {
        $this->userstamps = 'true';

        $this->userstamp('created_by');
        $this->userstamp('updated_by');

        return $this;
    }

    public function pretend()
    {
        $this->pretend = true;

        return $this->pretend;
    }

    public function isPretend()
    {
        return $this->pretend;
    }

    //todo: make this happen after blueprint is done so primarykey can be dynamic
    public function tree()
    {
        $this->tree = true;

        $this->unsignedInteger('parentId')->nullable()->fillable();
        $this->integer('index')->fillable()->required();

        //$this->string('text', 200)->fillable()->required();

        $this->boolean('leaf')->fillable()->required();

        //Composite Unique Key ParentId-Index
        //$this->unique(array('parentId', 'index'));

        $this->foreign('parentId')
            ->references('id')
            ->on($this->table)
            ->onDelete('cascade');

        return $this;
    }

    //todo: make this a function of VextFluent for foreignKey purposes
    public function parent($parentKey)
    {
        $this->parentKey = $parentKey;

        return $this;
    }

    public function model($model)
    {
        $this->model_name = $model;
    }

    public function getModel()
    {
        return $this->model_name;
    }

    /**
     * @return VextFluent
     */
    public function getCurrentColumn()
    {
        return $this->current_column;
    }

    public function getCurrentName()
    {
        return $this->current_name;
    }

    public function laravelModel()
    {
        $stub = $this->getStub('model.stub');
        $stub = str_replace('{{model}}', $this->model_name, $stub);

        return $stub;
    }

    public function laravelBaseModel()
    {
        $fillable = $this->prettyPrintArray($this->fillable);
        $rules = array();
        $messages = array();
        foreach ($this->columns as &$column) {
            $laravelRules = array();
            $extJsRules = $column->getRules();
            $name = $column->getName();

            if (($required = $column->getRequired()) !== null) {
                $laravelRules[] = 'required';
            }

            $this->addRule($laravelRules, $extJsRules, 'min', 'minLength');
            $this->addRule($laravelRules, $extJsRules, 'max', 'maxLength');
            $this->addRule($laravelRules, $extJsRules, 'min', 'minValue');
            $this->addRule($laravelRules, $extJsRules, 'max', 'maxValue');
            if (isset($extJsRules['minText'])) {
                $messages[] = "'$name.min' => '" . $extJsRules['minText'] . '\'';
            }

            if (!empty($laravelRules)) {
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

        $appends = $this->prettyPrintArray($this->getColNames($this->appends));

        $stub = $this->populateStub($stub, compact('fillable', 'rules', 'messages', 'parentKey', 'appends'));
        $stub = $this->addRelationships($stub);

        return $stub;

    }

    protected function getColNames($cols)
    {
        $names = array();

        foreach ($cols as $col) {
            $names[] = $col->name;
        }

        return $names;
    }

    private function prettyPrintArray($array)
    {
        if (empty($array)) {
            return '';
        } else {
            return '\'' . implode("',\r\n\t\t'", $array) . '\'';
        }
    }

    protected function addRelationships($stub)
    {
        $relationships = $this->relationships;
        $relation_stubs = array();

        foreach ($relationships as $relationship) {
            $name = camel_case($relationship['related']);
            $model = studly_case($name);
            $type = $relationship['type'];
            $param_array = array($model, $relationship['foreignKey'], $relationship['localKey']);
            $params = "'" . implode("','", array_filter($param_array)) . "'";
            $relation_stubs[] = "\tpublic function {$name}() {\n" .
                "\t\t" . 'return $this->' . $type . "($params);" .
                "\n\t}\n";
        }

        $columns = $this->getColumns();
        foreach ($columns as $col) {
            if (!is_null($lookup = $col->getLookup())) {
                if (isset($lookup['name'])) {
                    $name = camel_case($lookup['name']);
                } else {
                    $name = camel_case($lookup['model']);
                }

                $name = strtolower($name);

                $model = studly_case($lookup['model']);

                $relationship = "\tpublic function {$name}() {\n" .
                    "\t\t" . 'return $this->belongsTo("' . $model . '", "' . $col->getName() . '");' .
                    "\n\t}\n";

                $relation_stubs[] = $relationship;

                if ($col->getEagerLoad()) {
                    $this->with[] = $name;
                }
            }
        }

        $relationships = implode("\n\n", $relation_stubs);
        if (!empty($this->with)) {
            $this->with = '\'' . implode('\', \'', $this->with) . '\'';
        } else {
            $this->with = '';
        }

        $stub = $this->stubFill('relationships', $relationships, $stub);
        $stub = $this->stubFill('with', $this->with, $stub);

        return $stub;
    }

    protected function addRule(&$laravelRules, $extJsRules, $laravelName, $extJsName)
    {
        if (isset($extJsRules[$extJsName])) {
            $laravelRules[] = $laravelName . ':' . $extJsRules[$extJsName];
        }

        return $laravelRules;
    }

    protected function getStubPath()
    {
        return __DIR__ . '/stubs';
    }

    protected function getStub($name)
    {
        return file_get_contents($this->getStubPath() . "/{$name}");
    }

    protected function populateStub($stub, $data = array())
    {
        $stub = $this->stubFill('model', $this->model_name, $stub);
        $stub = $this->stubFill('table', $this->table, $stub);
        $stub = $this->stubFill('timestamps', $this->timestamps, $stub);
        $stub = $this->stubFill('fillable', $data['fillable'], $stub);
        $stub = $this->stubFill('rules', $data['rules'], $stub);
        $stub = $this->stubFill('messages', $data['messages'], $stub);
        $stub = $this->stubFill('parentKey', $data['parentKey'], $stub);
        $stub = $this->stubFill('userstamps', $this->userstamps, $stub);
        $stub = $this->stubFill('appends', $data['appends'], $stub);
        $stub = $this->stubFill('properties', $this->getProperties(), $stub);

        return $stub;
    }

    protected function getProperties()
    {
        $properties = array();
        $cols = $this->getColumns();

        foreach ($cols as $col) {
            $name  = $col->getName();
            $type = $col->getPhpType();
            $properties[] = "@property $type $$name";
        }

        return ' * ' . implode("\r\n * ", $properties);
    }

    protected function stubFill($param, $data, $stub)
    {
        return str_replace('{{$' . $param . '}}', $data, $stub);
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        $fields = array();
        $model = array();

        foreach ($this->columns as $column) {
            $fields[] = $column->toArray();
        }

        foreach ($this->appends as $appended) {
            $fields[] = $appended->toArray();
        }

        if ($this->tree) {
            $fields[] = array(
                'name' => 'root',
                'type' => 'boolean',
            );
        }

        $model = compact('fields');
        $relationships = $this->relationships;
        foreach ($relationships as $relationship) {
            $model[$relationship['type']][] = $relationship['related'];
        }

        return $model;
    }

}

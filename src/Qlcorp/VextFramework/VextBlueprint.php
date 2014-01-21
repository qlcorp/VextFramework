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

}
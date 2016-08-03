<?php namespace Qlcorp\VextFramework;

use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Fluent;
use Closure;

/**
 * Class VextFluent
 *
 * Represents a column in the database.
 * Used for generating properties in both the PHP and ExtJs Models.
 *
 * @author   Tony
 * @package  Qlcorp\VextFramework
 *
 * @property string $dateFormat
 * @property string $dateReadFormat
 */
class VextFluent extends Fluent implements JsonableInterface, ArrayableInterface
{

    protected $blueprint = null;
    protected $gridConfig = null;
    protected $rules = array();
    protected $dropdown = array();
    protected $required = null;
    protected $fillable = false;
    protected $fieldConfig = array();
    protected $tree = false;
    protected $lookup = null;
    protected $eagerLoad = true;

    public function __construct($attributes = array(), VextBlueprint $blueprint)
    {
        parent::__construct($attributes);
        $this->blueprint = $blueprint;
    }

    public function getEagerLoad()
    {
        return $this->eagerLoad;
    }

    public function getLookup()
    {
        return $this->lookup;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function getFieldConfig()
    {
        return $this->fieldConfig;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function getType()
    {
        $type = $this->attributes['type'];

        switch ($type) {
            case 'char':
            case 'enum':
                return 'string';
            case 'timestamp':
                return 'date';
            default:
                return $type;
        }
    }

    public function getPhpType()
    {
        $type = $this->getType();
        $name = $this->getName();
        $carbon = array('created_at', 'updated_at');

        switch ($type) {
            case 'date':
                return in_array($name, $carbon) ? 'Carbon' : 'timestamp';
            default:
                return $type;
        }
    }

    public function getNullable()
    {
        return isset($this->attributes['nullable']);
    }

    public function getRequired()
    {
        return $this->required;
    }

    public function lookup($model, $param = null, $name = null)
    {
        return $this->blueprint->getCurrentColumn()->setLookup($model, $param, $name);
    }

    public function setLookup($model, $param = null, $name = null)
    {
        $this->lookup = compact('model');
        if (!is_null($param)) {
            $this->lookup['param'] = $param;
        }
        if (!is_null($name)) {
            $this->lookup['name'] = $name;
        }

        return $this;
    }

    public function eagerLoad($eager = true)
    {
        return $this->blueprint->getCurrentColumn()->setEagerLoad($eager);
    }

    public function setEagerLoad($eager)
    {
        $this->eagerLoad = $eager;

        return $this;
    }

    public function fillable()
    {
        return $this->blueprint->getCurrentColumn()->setFillable();
    }

    protected function setFillable()
    {
        $this->fillable = true;
        $this->blueprint->addFillable($this->attributes['name']);

        return $this;
    }

    public function required($required = true)
    {
        return $this->blueprint->getCurrentColumn()->setRequired($required);
    }

    protected function setRequired($required)
    {
        $required = (bool)$required;
        $this->fieldConfig['allowBlank'] = !$required;
        $this->required = $required;

        return $this;
    }

    public function regex($regex)
    {
        return $this->blueprint->getCurrentColumn()->setRegex($regex);
    }

    protected function setRegex($regex)
    {
        $this->fieldConfig['regex'] = $regex;

        return $this;
    }

    public function fieldConfig($config = array())
    {
        return $this->blueprint->getCurrentColumn()->setFieldConfig($config);
    }

    protected function setFieldConfig($config = array())
    {
        $this->fieldConfig = array_merge($this->fieldConfig, $config);

        return $this;
    }

    public function gridConfig($configs = array())
    {
        return $this->blueprint->getCurrentColumn()->setGridConfig($configs);
    }

    protected function setGridConfig($configs = array())
    {
        $this->gridConfig = $configs;

        return $this;
    }

    public function dropdown($elements)
    {
        return $this->blueprint->getCurrentColumn()->setDropdown($elements);
    }

    protected function setDropdown($elements)
    {
        foreach ($elements as $key => $value) {
            $this->dropdown[] = compact('key', 'value');
        }

        if (isset($this->fieldConfig['maxLength'])) {
            unset($this->fieldConfig['maxLength']);
        }
        if (isset($this->fieldConfig['minLength'])) {
            unset($this->fieldConfig['minLength']);
        }

        return $this;
    }

    public function vtype($vtype)
    {
        return $this->blueprint->getCurrentColumn()->setVtype($vtype);
    }

    protected function setVtype($vtype)
    {
        $this->fieldConfig['vtype'] = $vtype;

        return $this;
    }

    public function validation(Closure $callback)
    {
        return $this->setValidation($callback);
    }

    protected function setValidation(Closure $callback)
    {
        $validate = new VextValidate;
        $callback($validate);
        $this->rules = $validate->getRules();
        $this->fieldConfig($this->rules);

        return $this;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        $this->setOption($field, 'name', $this->getName());
        $this->setOption($field, 'type', $this->getType());
        $this->setOption($field, 'dateFormat', $this->dateFormat);
        $this->setOption($field, 'dateReadFormat', $this->dateReadFormat);
        $this->setOption($field, 'useNull', $this->getNullable());
        $this->setOption($field, 'fieldConfig', $this->fieldConfig);
        $this->setOption($field, 'gridConfig', $this->gridConfig);
        $this->setOption($field, 'dropdown', $this->dropdown);
        $this->setOption($field, 'lookup', $this->lookup);

        return $field;
    }

    /**
     * Set array[$key] to $value if $var is not empty
     *
     * $value = $var if $value is not set
     *
     * @param      $array
     * @param      $key
     * @param      $var
     * @param null $value
     * @return mixed
     */
    protected function setOption(&$array, $key, $var, $value = null)
    {
        $value = ($value === null) ? $var : $value;

        if (!empty($var)) {
            $array[$key] = $value;
        }

        return $array;
    }


}

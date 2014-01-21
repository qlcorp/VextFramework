<?php namespace Qlcorp\VextFramework;
/**
 * Class CrudModel
 *
 * @author Tony
 */

use \Validator;

abstract class CrudModel extends \Eloquent {

    /**
     * Protect against mass assignment in both create() and update() functions
     *
     * Timestamps are updated automatically via ORM
     * Can be overwritten in child
     *
     * @var array
     */
    protected $guarded = array('id', 'created_at', 'updated_at', 'deleted_at');

    //validation parameters to overwrite
    //validator will pass automatically if $rules is not defined in child
    protected $rules = array();
    protected $messages = array();
    public $errors;

    /**
     * Validates generation input
     * @return bool
     */
    public function validate() {
        $validator = Validator::make($this->toArray(), $this->rules,
            $this->messages);
        if ( $validator->fails() ) {
            $this->errors = $validator->messages();
            return false;
        } else return true;
    }

    /**
     * Get validation errors
     * @return string
     */
    public function getErrors() {
        if ( isset($this->errors) ) {
            return implode($this->errors->all('<li>:message</li>'));
        } else {
            return "Request failed.";
        }
    }

   /**
    * Event Handlers
    *
    * By calling parent::boot() in child, the model events can be overwritten
    * individually within the boot() function
    *
    */
    public static function boot() {
        parent::boot();
        //Halt saving if validation fails
        static::saving(function($model) {
            if ( !$model->validate() ) return false;
        });
    }

    public function fields() {
        /*$schema = new VextBuilder();
        $fields = new FieldBuilder();

        $columns = $schema->columns($this->getTable());

        $fields->add($columns);
        return $fields;*/
    }


}

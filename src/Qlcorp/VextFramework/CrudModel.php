<?php namespace Qlcorp\VextFramework;
/**
 * Class CrudModel
 *
 * @author Tony
 */

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

abstract class CrudModel extends \Eloquent {
    protected $parentKey = null;
    //validation parameters to overwrite
    //validator will pass automatically if $rules is not defined in child
    protected $rules = array();
    protected $messages = array();
    public $errors;

    public function getParentKey() {
        return $this->parentKey;
    }

    public function updatedByUser() {
        return $this->belongsTo('User', 'updated_by');
    }

    public function createdByUser() {
        return $this->belongsTo('User', 'created_by');
    }

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

        static::creating(function($model) {
            if (!Auth::guest()) {
                $model->created_by = Auth::user()->id;
            } else {
                $model->created_by = 1;
            }
        });

        static::updating(function($model) {
            if (!Auth::guest()) {
                $model->updated_by = Auth::user()->id;
            } else {
                $model->update_by = 1;
            }
        });
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 2/10/14
 * Time: 7:57 PM
 */

namespace Qlcorp\VextFramework;

class TreeModel extends CrudModel {
    protected $appends = array('root');

    public function children() {
        return $this->hasMany(get_class($this), 'parentId')
            ->with('children')
            ->orderBy('index');
    }

    public function parent() {
        return $this->belongsTo(get_class($this), 'parentId');
    }

    public function getRootAttribute() {
        //todo: switch statements when getParentIdAttribute is removed
        //return !isset($this->attributes['parentId']);
        return $this->attributes['parentId'] == 0;
    }

    public function scopeRoot($query) {
        return $query->whereNull('parentId');
    }

    /**
     * todo: remove this in favor of returning null
     */
    public function getParentIdAttribute() {
        $parentId = $this->attributes['parentId'];
        if ( is_null($parentId) ) {
            return 0;
        } else {
            return $this->attributes['parentId'];
        }
    }

    public function setParentIdAttribute($value) {
        if ( $value === 0 ) {
            $this->attributes['parentId'] = null;
        } else {
            $this->attributes['parentId'] = $value;
        }
    }

} 
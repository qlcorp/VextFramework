<?php namespace Qlcorp\VextFramework;

/**
 * Abstract Controller to implement CRUD functionality
 *
 * Example:
 *      Class UserController extends this class
 *      Performs CRUD operations on User Model
 * @author Tony
 */

use Illuminate\Support\Facades\Input;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;

abstract class CrudController extends BaseController {
    /**
     * Class name of Model for this CRUD controller
     *
     * Used to call methods on model
     *
     * @var string
     */
    protected $Model;
    protected $root;
    protected $model;

    /**
     * Implicitly constructs CrudController from child
     *
     * Determines Model by child's class name, unless $Model attribute is overwritten
     */
    public function __construct() {
        if (!$this->Model) {
            $class = get_class($this);
            $this->Model = substr($class, 0, -10);
        }
        //todo: use existing Laravel function getBaseClass()
        if ( !$this->root ) {
            $this->root = lcfirst($this->Model);
        }

        $Model = $this->Model;
        $this->model = new $Model;

        $me = $this;

        App::error(function(ModelNotFoundException $e) use ($me) {
            return $me->failure(null, $e->getMessage());
        });

        App::error(function(\Exception $e) use ($me) {
            return $me->failure(null, $e->getMessage());
        });
    }

    /**
     * Retrieve model information for ExtJs
     * @return string
     */
    public function getModel() {
        $Model = $this->Model;
        $table = $this->model->getTable();

        return \VextSchema::getExtJsModel($table);
    }

    /**
     * Retrieve records
     *
     * int $_GET['limit'] maximum number of records
     * int $_GET['start'] record offset
     *
     * @return string
     */
    public function getRead() {
        $Model = $this->Model;
        $parentKey = $this->model->getParentKey();
        $query = $this->model->newQuery();

        if ( $parentKey && Input::has($parentKey) ) {
            $parentValue = Input::get($parentKey);
            $query = $query->where($parentKey, $parentValue);
        }

        //Get single record by primary key
        if ( ($id = $this->getKeyFromInput()) !== null ) {
            $record = $query->find($id);
            if ( $record !== null ) {
                return $this->success($record);
            } else {
                //todo: what to return here?
                return $this->failure($record, "$Model not found.");
            }
        }
        //Get multiple records (pagination)
        $filter = Input::get('filter');
        $limit =  Input::get('limit');
        $offset = Input::get('start');

        if ( $filter ) $this->filterQuery($query, json_decode($filter));

        $count = $query->count();

        if ( $limit ) {
            $query->take($limit);
            if ( $offset ) {
                $query->take($offset);
            }
        }

        return $this->success($query->get(), array('total' => $count));
    }

    /**
     * Sets filters for query
     *
     * @param $query
     * @param array $filters
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function filterQuery(&$query, $filters) {
        foreach ($filters as $filter) {
            $property = $filter->property;
            $value = '%' . $filter->value . '%';

            if ( empty($property) || empty($value) ) {
                throw new \InvalidArgumentException();
            }

            $query->where($property, 'like', $value);
        }

        return $query;
    }

    /**
     * Insert record
     *
     * @return string
     */
    public function postCreate() {
        $Model = $this->Model;

        $record = $Model::create(Input::all());

        return ( $record->errors )
            ? $this->failure($record, $record->getErrors())
            : $this->success($record);

    }

    /**
     * Edit record
     *
     * Determines primary key from Eloquent Model (defaults to 'id')
     * Consider dropping this to simply expect 'id' field from client-side
     *
     * @return string
     */
    public function postUpdate() {
        $Model = $this->Model;

        //determine which field from ExtJs to use as the primary key
        //may replace with $id = Input::get('id');
        $id = $this->getKeyFromInput();

        //get record
        $record = $Model::findOrFail($id);
        $record->update(Input::all());

        return ( $record->errors )
            ? $this->failure($record, $record->getErrors())
            : $this->success($record);

    }

    /**
     * Delete record
     */
    public function postDelete() {
        $Model = $this->Model;

        $id = $this->getKeyFromInput();
        $record = $Model::findOrFail($id);
        $record->delete();

        return $this->success($record);
    }

    /**
     * Retrieve primary key from client-side data
     *
     * Determined by Model
     *
     * @return mixed
     */
    protected function getKeyFromInput() {
        $Model = $this->Model;

        $key = $this->model->getKeyName();
        $id = Input::get($key);

        return $id;
    }

    public function success($records = null, $options = array()) {
        if (!is_null($records)) {
            $options[$this->root] = $records->toArray();
        }
        return parent::success($options);
    }

    public function failure($record = null, $message = null, $options = array()) {
        if ( !is_null($record) ) {
            $options[$this->root] = $record->toArray();
        }
        if ( !is_null($message) ) {
            $options['message'] = $message;
        }
        return parent::failure($options);
    }
}

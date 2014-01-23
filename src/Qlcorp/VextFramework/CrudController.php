<?php namespace Qlcorp\VextFramework;

/**
 * Abstract Controller to implement CRUD functionality
 *
 * Example:
 *      Class UserController extends this class
 *      Performs CRUD operations on User Model
 *
 * @author Tony
 */

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;

abstract class CrudController extends Controller {
    /**
     * Class name of Model for this CRUD controller
     *
     * Used to call methods on model
     *
     * @var string
     */
    protected $Model;
    private $root;
    private $model;

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
        $this->root = lcfirst($this->Model) . 's';  //use existing Laravel function getBaseClass()
        $Model = $this->Model;
        $this->model = new $Model;
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
        // TODO: get by id, query
        $Model = $this->Model;

        //$id = $this->getKeyFromInput();

        $limit = Input::get('limit');
        $offset = Input::get('start', 0);

        $query = $Model::skip($offset);
        if ($limit) $query->take($limit);

        return $this->success($query->get());
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
    private function getKeyFromInput() {
        $Model = $this->Model;

        $key = $this->model->getKeyName();
        $id = Input::get($key);

        return $id;
    }

    /**
     * Handle bad methods
     *
     * @param array $parameters
     * @return mixed|void
     */
    public function missingMethod($parameters = array()) {
        App::abort(404);
    }

    protected function success($records, $options = array()) {
        return json_encode(array_merge(array(
            'success' => true,
            $this->root => $records->toArray(),
        ), $options));
    }

    protected function failure($record, $message = "", $options = array()) {
        return json_encode(
            array_merge(
                array(
                    'success' => false,
                    'message' => $message,
                    $this->root => $record->toArray(),
                ),
                $options
            )
        );
    }
}

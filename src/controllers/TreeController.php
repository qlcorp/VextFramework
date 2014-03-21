<?php namespace Qlcorp\VextFramework;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class TreeController extends CrudController {

    protected $root = 'children';

    public function getIndex() {
        echo "Hello world!";
        $Model = $this->Model;
    }

    protected function createRoot($parentKey) {
        $Model = $this->Model;
        return $Model::Create(array(
            'leaf' => false,
            'index' => 0,
            'text' => 'New Root',
            $this->model->parentKey => $parentKey
        ));
    }

    public function getRead() {
        $Model = $this->Model;

        $parentKey = $this->model->getParentKey();
        $parentValue = $parentKey ? Input::get($parentKey) : null;

        if (isset($_GET['node'])) {  // Process request as tree
            $node = Input::get('node');
            $id = Input::get($this->model->getTable() . '_id');
            $node = $this->getNode($id, $parentKey, $parentValue);
        }
        else {
            $node = $this->getRecords($parentKey, $parentValue);
        }
        /*
                if ( $root ) {
                    $node = $Model::create(Input::all());
                }
                else {
                    if (!isset($_GET['node'])) {

                    }
                    else {
                        if ( !is_null($parentKey) ) {
                            $parentKey = Input::get($parentKey);
                            $node = $this->getNode($id, $parentKey);
                        }
                        else {
                            $node = $this->getNode($id);
                        }
                    }
                }
        */


        return $this->success($node);
    }

    protected function getRecords($parentKey = null, $parentValue = null) {
        $this->root = $this->model->getTable();
        $query = $this->model->newQuery();

        if ( $parentKey ) {
            $query = $query->where($parentKey, $parentValue);
        }

        $node = $query->whereNull('parentId')->get();

        return $node;
    }


    protected function getNode($id, $parentKey = null, $parentValue = null) {
        $this->root = 'children';
        $Model = $this->Model;
        $query = $this->model->newQuery();

        if ( $parentKey && !is_null($parentValue) ) {
            $query->where($parentKey, $parentValue);
        }

        //dd(Input::has('node'));

        if ( $id === '' ) {  //Todo: check for non existent root & create if needed
            $node = $query->whereNull('parentId')->first();
            if ( !$node ) {
                $node =  $this->createRoot($parentValue);
            }
        } else {
            $node = $query->find($id);
        }

        if ($node && !$node->leaf) {
            $node->load('children');
        }

        return $node;
    }

    public function postMove() {
        $Model = $this->Model;

        DB::transaction(function() use ($Model) {

            $id = Input::get('id');
            $oldParentId = Input::get('oldParentId');
            $newParentId = Input::get('newParentId');
            $newIndex = Input::get('newIndex');

            $node = $Model::findOrFail($id);
            $oldIndex = $node->index;

            //$node->delete();

            $Model::where('parentId', $oldParentId)
                ->where('index', '>', $oldIndex)
                ->decrement('index');

            $Model::where('parentId', $newParentId)
                ->where('index', '>=', $newIndex)
                ->increment('index');

            $node->index = $newIndex;
            $node->parentId = $newParentId;
            $node->save();
        });


    }


} 
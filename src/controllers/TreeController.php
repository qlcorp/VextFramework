<?php namespace Qlcorp\VextFramework;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TreeController extends CrudController {

    protected $root = 'children';

    protected function createRoot($parentKey) {
        $Model = $this->Model;
        return $Model::Create(array(
            'leaf'  => false,
            'index' => 0,
            'text'  => 'New Root',
            $this->model->parentKey => $parentKey
        ));
    }

    public function postCopy() {
        $Model = $this->Model;
        $id = Input::get('id');

        $node = $Model::with('children')->findOrFail($id);
        $attributes = $node->attributesToArray();

        if ( !$node->leaf ) {
            $copy = $this->copyBranch($node, $node->parentId);
            $copy->load('children');
        } else {
            $copy = new $Model($attributes);
        }

        $copy->index = $Model::where('parentId', $node->parentId)->count();
        $copy->save();

        return $this->success($copy);
    }

    /**
     * Duplicates a branch
     *
     * @param Eloquent $branch root node of branch to copy
     * @param int $parentId id of parent node to paste copy to
     * @return Eloquent duplicated root node
     */
    protected function copyBranch($branch, $parentId) {
        $Model = $this->Model;

        $copy = new $Model($branch->attributesToArray());
        $copy->parentId = $parentId;
        $copy->save();
        $parentId = $copy->id;

        foreach ( $branch->children as $child ) {
            $this->copyBranch($child, $parentId);
        }

        return $copy;
    }

    public function getRead() {
        $Model = $this->Model;

        $parentKey   = $this->model->getParentKey();
        $parentValue = $parentKey ? Input::get($parentKey) : null;

        if (isset($_GET['node'])) {  // Process request as tree
            $node = Input::get('node');
            $id   = Input::get($this->model->getTable() . '_id');
            $node = $this->getNode($id, $parentKey, $parentValue);
        }
        else {
            $node = $this->getRecords($parentKey, $parentValue);
        }

        return $this->success($node);
    }

    protected function getRecords($parentKey = null, $parentValue = null) {
        $this->root = $this->model->getTable();
        $query = $this->model->newQuery();

        if ( $parentKey ) {
            $query = $query->where($parentKey, $parentValue);
        }

        $node = $query->whereNull('parentId')->with('children')->first();

        return new Collection($this->flatten($node));
    }

    protected function flatten($tree) {
        $flat_tree = array($tree);

        if ( !$tree->children->isEmpty() ) {
            foreach ($tree->children as $child) {
                $flat_tree = array_merge($flat_tree, $this->flatten($child));
            }
        }

        return $flat_tree;
    }

    protected function getNode($id, $parentKey = null, $parentValue = null) {
        $this->root = 'children';
        $Model = $this->Model;
        $query = $this->model->newQuery();

        if ( $parentKey && !is_null($parentValue) ) {
            $query->where($parentKey, $parentValue);
        }

        //dd(Input::has('node'));

        if ( is_null($id) || ($id === '') ) {  //Todo: check for non existent root & create if needed
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
<?php namespace Qlcorp\VextFramework;

use Illuminate\Support\Facades\Input;

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
        if ( !Input::has('node') ) {
            return $this->failure();
        }

        $id = Input::get('node');

        if ( !is_null($parentKey = $this->model->parentKey) ) {
            $parentKey = Input::get($parentKey);
            $node = $this->getNode($id, $parentKey);
        } else {
            $node = $this->getNode($id);
        }

        return $this->success($node);
    }

    protected function getNode($id, $parentKey = null) {
        $Model = $this->Model;
        $query = $this->model->newQuery();

        if ( !is_null($parentKey) ) {
            $query->where($this->model->parentKey, $parentKey);
        }

        if ($id === 'root') {
            $node = $query->whereNull('parentId')->first();
            if ( !$node ) {
                $node =  $this->createRoot($parentKey);
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

        $oldParentId = Input::get('oldParentId');
        $newParentId = Input::get('newParentId');
        $node = Input::get('node');
        $id = Input::get('id');
        $newIndex = Input::get('index');

        $oldNode = $Model::findOrFail($id);
        $oldIndex = $oldNode->index;

        $oldNode->delete();

        $Model::where('parentId', $oldParentId)
            ->where('index', '>', $oldIndex)
            ->decrement('index');

        $Model::where('parentId', $newParentId)
            ->where('index', '>=', $newIndex)
            ->increment('index');

        $Model::create($node);
    }


} 
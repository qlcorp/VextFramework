<?php namespace Qlcorp\VextFramework;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class VextBuilder extends Builder {

    protected static $extJsModelsDir = 'public/ExtJsModels';
    protected static $laravelModelsDir = 'app/models/';
    protected static $laravelBaseModelsDir = 'app/models/vext/';
    protected static $fileName = 'extJsModel.json';

    protected function build(Blueprint $blueprint) {
        //dd($blueprint->toJson());
        $this->writeModels($blueprint);
        //todo: tree code here

        parent::build($blueprint);
    }

    protected function writeModels($blueprint) {
        $fields = $blueprint->toArray();

        $this->writeJsonModel($fields, $blueprint);
        $this->writeLaravelModel($fields, $blueprint);
    }

    protected function writeJsonModel($fields, $blueprint) {
        $table = $blueprint->getTable();
        $dir = self::getWriteDirectory($table);
        self::mkdir($dir);
        $json = json_encode($fields);

        return File::put($dir . self::$fileName, $json);
    }

    protected function writeLaravelModel($fields, $blueprint) {
        $table = $blueprint->getTable();
        $modelName = $blueprint->getModel();

        if ($modelName === '') {
            $modelName = str_singular(studly_case($table));
            $blueprint->model($modelName);
        }

        $baseModel = $blueprint->laravelBaseModel();
        $model = $blueprint->laravelModel();

        File::put($this->laravelBaseModelFile($modelName), $baseModel);

        $laravelModelFile = $this->laravelModelFile($modelName);

        if ( !File::isFile($laravelModelFile) ) {
            File::put($laravelModelFile, $model);
        }
    }

    protected function laravelBaseModelFile($modelName) {
        self::mkDir(self::$laravelBaseModelsDir);
        return self::$laravelBaseModelsDir . 'Base' . $modelName . '.php';
    }

    protected function laravelModelFile($modelName) {
        self::mkDir(self::$laravelModelsDir);
        return self::$laravelModelsDir . $modelName . '.php';
    }

    public function getExtJsModel($table) {
        $dir = self::getReadDirectory($table);
        return File::get($dir . self::$fileName);
    }

    protected function getWriteDirectory($table) {
        $path = self::$extJsModelsDir . '/' . $table . '/';
        return $path;
    }

    protected static function getReadDirectory($table) {
        $path = '../' . self::$extJsModelsDir . '/' . $table . '/';
        return $path;
    }

    protected function mkDir($path) {
        $result = null;

        if ( !File::isDirectory($path) ) {
            $result = File::makeDirectory($path, 0777, true, true);
        }

        return $result;
    }

    public function createBlueprint($table, Closure $callback = null) {
        return new VextBlueprint($table, $callback);
    }

}



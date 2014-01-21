<?php namespace Qlcorp\VextFramework;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\File;

class VextBuilder extends Builder {

    protected static $dir = 'public/ExtJsModels';
    protected static $fileName = 'extJsModel.json';

    protected function build(Blueprint $blueprint) {
        //dd($blueprint->toJson());
        $this->writeJsonModel($blueprint);

        parent::build($blueprint);
    }

    protected function writeJsonModel($blueprint) {
        $table = $blueprint->getTable();
        $dir = self::getWriteDirectory($table);
        self::mkdir($dir);

        return File::put($dir . self::$fileName, $blueprint->toJson());
    }

    public function getExtJsModel($table) {
        $dir = self::getReadDirectory($table);
        return File::get($dir . self::$fileName);
    }

    protected function getWriteDirectory($table) {
        $path = self::$dir . '/' . $table . '/';
        return $path;
    }

    protected static function getReadDirectory($table) {
        $path = '../' . self::$dir . '/' . $table . '/';
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



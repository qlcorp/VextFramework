<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 3/20/14
 * Time: 3:48 PM
 */

namespace Qlcorp\VextFramework;

use Illuminate\Database\Migrations\Migrator;

class VextMigrator extends Migrator {

    public function run($path, $pretend = false) {
        $this->notes = array();
        $this->requireFiles($path, $files = $this->getMigrationFiles($path));
        $migrations = $files;
        $this->runMigrationList($migrations, $pretend);
    }

    protected function runUp($file, $batch, $pretend) {
        $migration = $this->resolve($file);
        $this->pretendToRun($migration, 'up');
        $this->note("<info>Models generated for:</info> $file");
    }

    protected function pretendToRun($migration, $method) {
        foreach ($this->getQueries($migration, $method) as $query) {
            $name = get_class($migration);
        }
    }

} 
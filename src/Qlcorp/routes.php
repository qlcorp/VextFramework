<?php

Route::get('/models', function() {
    $files = File::allFiles(public_path('ExtJsModels'));
    $models = array();

    foreach($files as $file) {
        $models[$file->getRelativePath()] = File::get($file->getPathName());
    }

    return Response::json($models);
});

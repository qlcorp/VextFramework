<?php namespace Qlcorp\VextFramework;

use Illuminate\Support\Facades\App;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;

class BaseController extends Controller {
	/**
     * Handle bad methods
     *
     * @param array $parameters
     * @return mixed|void
     */
    public function missingMethod($parameters = array()) {
        App::abort(404);
    }

    protected function success($options = array()) {
        $options['success'] = true;
        return Response::json($options);
    }

    protected function failure($options = array()) {
        $options['success'] = false;
        return Response::json($options);
    }
}
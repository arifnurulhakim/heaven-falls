<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function jsonResponse($data = null, $messages = null, $code = 200)
    {
        $array = [
            'data'  => $data,
            'message'  => $messages,
            'status'  => in_array($code, $this->codeHTTP()) ? true : false,
        ];
        return response($array, $code);
    }

    public function codeHTTP(): array
    {
        return ['200', '201', '202'];
    }
}

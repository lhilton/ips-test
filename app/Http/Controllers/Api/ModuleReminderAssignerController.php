<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ModuleReminderAssignerRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ModuleReminderAssignerController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ModuleReminderAssignerRequest $request)
    {
        return response()->json(['success' => true, 'message' => null]);
    }
}

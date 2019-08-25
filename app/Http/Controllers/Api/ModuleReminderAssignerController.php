<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ModuleReminderAssignerHelper;
use App\Http\Requests\ModuleReminderAssignerRequest;

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
        $mraHelper = new ModuleReminderAssignerHelper();
        return $mraHelper->sendModuleReminderForUser($request->input('email'));
    }
}

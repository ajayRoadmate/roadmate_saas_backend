<?php

namespace App\Http\Controllers\Admin\Executive;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 

class ExecutiveController extends Controller
{

    public function testFunExec(Request $request){

        return response("hello ExecutiveController");
    }


    


}


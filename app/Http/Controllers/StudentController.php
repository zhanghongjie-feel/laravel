<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
class StudentController extends Controller
{
    public function index()
    {
        
        $info=DB::table('student')->paginate(2);
        // dd($info);
        return view('studentList',['student'=>$info]);
    }
}

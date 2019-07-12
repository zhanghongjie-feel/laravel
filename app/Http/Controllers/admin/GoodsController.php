<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GoodsController extends Controller
{
    public function add_goods()
    {
        // dd(storage_path('app\public'));
        return view('admin.add_goods');
    }

    public function do_add_goods(Request $request)
    {
        // dd($_FILES);
        $path=$request->file('goods_pic')->store('goods');
        echo asset('storage').'/'.$path;
        // dd($path);
    }
}

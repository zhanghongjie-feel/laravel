<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
class indexController extends Controller
{
    public function index()
    {
        echo '这就是特么admin';
    }
    public function login()
    {
        return view('login');
    }

    public function do_login(Request $request)
    {
        
        $req=$request->all();
        $name=$req['name'];
        $password=$req['password'];
        // dd($password);
        $result=DB::table('user')->where(['name'=>$name,'password'=>$password])->count();
        // dd($result);
        if($result>0){
            return redirect('student/index');
        }else{
            echo 'fail';
        }
    }


    public function register()
    {
        return view('register');
    }

    public function do_register(Request $request)
    {
        $validateData=$request->validate([
            'name'=>'required',
            'password'=>'required'
        ],[
            'name.required'=>'名字没写',
            'password.required'=>'年龄没写'
        ]);
        $req=$request->all();
        // dd($req);
        $result=DB::table('user')->insert([
            $req
        ]);
        // dd($result);
        $result=true;
        if($result){
            return redirect('/admin/login');
        }else{
            echo 'fail';
        }
    }
}

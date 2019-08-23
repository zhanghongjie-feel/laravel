<?php

namespace App\Http\Controllers\lonely;

use Illuminate\Http\Request;
use App\Http\Tools\Wechat;
use App\Http\Controllers\Controller;
use DB;
class LiuyanController extends Controller
{
    public $wechat;
    public function __construct(Wechat $wechat){
        $this->wechat=$wechat;
    }
    public function view(){
        return view('lonely/login');
    }
    public function login(){
        $redirect_uri=env('APP_UR   L1').'/liuyan/wechat_code'; //接受code [wechat客户端帮助用户自动跳转]
//        dd($redirect_uri);
        $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.env('WECHAT_APPID').'&redirect_uri='.urlencode($redirect_uri).'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
//        dd($url);
        header('Location:'.$url);
    }
    public function wechat_code(Request $request){
        $req=$request->all();
        $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.env('WECHAT_APPID').'&secret='.env('WECHAT_APPSECRET').'&code='.$req['code'].'&grant_type=authorization_code';
        $re=file_get_contents($url);
//        dd($re);
//        dd($url);
        $res=json_decode($re,1);
//        dd($res);
        //登陆网站
        $user_wechat=DB::connection('weixin')->table('wechat_openid')->where(['openid'=>$res['openid']])->first();
//        dd($user_wechat);
        $user_wechat=get_object_vars($user_wechat);
//        json_decode($user_wechat);
//        dd($user_wechat);
        //用户基本信息
        $wechat_info=$this->wechat->wechat_user_info($res['openid']);
        if(!empty($user_wechat)){
            //已经注册
//            dd($user_wechat['uid']);
            $request->session()->put(['uid'=>$user_wechat['uid']]);
//             dd($request->session()->put('uid'));
            return redirect('liuyan/index');
        }else{
            //未注册，先注册在登陆
            //开启事务
            $uid=DB::connection('weixin')->table('user')->insertGetId([
                'name'=>$wechat_info['nickname'],
                'password'=>'',
                'req_time'=>time()
            ]);
            $wechat_insert=DB::connection('weixin')->table('user_wechat')->insert([
                'uid'=>$uid,
                'openid'=>$user_wechat['uid']
            ]);
            //登录
            $request->session()->put(['uid'=>$user_wechat['uid']]);
        }

    }
    public function w_login(Request $request){
        return view('/lonely/login');
    }
    public function do_w_login(Request $request){
        $req=$request->all();
//        dd($req);
        $request->session()->put('username',$req['name']);
        $request->session()->put('uid',$req['password']);
        return redirect('liuyan/index');
    }
    public function index(Request $request){
        $se = $request->session()->all();
//        dd($se);
        //echo "<pre>";print_r($se);
        if(!empty($se['username'])){
            //已经登陆
            echo "已经登陆";
        }
        $info = DB::connection('weixin')->table('wechat_openid')->get();
        foreach($info as $v){
            $user_info = $this->wechat->wechat_user_info($v->openid);
            $v->nick_name = $user_info['nickname'];
        }
        /* $redis = new \Redis();
         $redis->connect('127.0.0.1','6379');
         $key = 'liuyyan:list';
         if($redis->exists($key)){
             $redis_info = $redis->get($key);
             $info = json_decode($redis_info,1);
             echo 11111;
         }else{
             $info = DB::connection('mysql_cart')->table('liu')->get()->toArray();
             $info = json_decode(json_encode($info),1);
             $redis->set($key,json_encode($info),30);
         }*/
        //dd($info);
        return view('lonely/index',['info'=>$info]);
    }

}

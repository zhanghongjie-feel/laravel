<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
class Liuyan extends Controller
{
    public function liuyan_info(Request $request){
        if(empty($request->all()['access_token']) || $request->all()['access_token'] != '12345'){
            return json_encode(['errno'=>'40014']);
        }
        $info=DB::connection('mysql')->table('student')->get()->toArray();
        $info=json_decode(json_encode($info),1);
        echo json_encode($info);
       
    }
    public function login()
    {
 
      $redirect_uri='http://www.laravel.com/wechat/code';
      $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.env('WECHAT_APPID').'&redirect_uri='.urlencode($redirect_uri).'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect ';
      header('Location:'.$url);
    }
 
 
    public function code(Request $request)
    {
      $data = $request->all();
      // dd($data);
      $code= $data['code'];
      // dd($code);
      //获取access_token
      $result = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".env('WECHAT_APPID')."&secret=".env('WECHAT_APPSECRET')."&code=$code&grant_type=authorization_code");
      $res = json_decode($result,1);
      $access_token=$res['access_token'];
      $openid = $res['openid'];
      //获取用户基本信息
         $wechat_user_info = $this->wechat->wechat_user_info($openid);
      // dd($wechat_user_info);
      //去user_openid 表查 是否有数据 openid = $openid
       $user_openid = DB::connection('weixin')->table("user_wechat")->where(['openid'=>$openid])->first();
       // dd($user_openid);
       if(!empty($user_openid)){
           //有数据 在网站有用户 user表有数据[ 登陆 ]
           $user_info = DB::connection('weixin')->table("user_wechat")->where(['id'=>$user_openid->uid])->first();
           $request->session()->put('username','name');
           //推送模板消息 [告诉用户你在我门的网站登录了]
           dd('查出此账号，干得漂亮');
           // header('Location:www.myshop.com');
       }else{
             //没有数据 注册信息  insert user  user_openid   生成新用户
             DB::connection("weixin")->beginTransaction();
             $user_result = DB::connection('weixin')->table('user_wechat')->insertGetId([
                 'password' => '',
                 'name' => $wechat_user_info['nickname'],
                 'reg_time' => time()
             ]);
             $openid_result = DB::connection('weixin')->table('user_wechat')->insert([
                 'uid'=>$user_result,
                 'openid' => $openid,
             ]);
             DB::connection('weixin')->commit();
             //登陆操作
           $user_info = DB::connection('mysql_cart')->table("user")->where(['id'=>$user_openid->uid])->first();
           $request->session()->put('username','name');
           //你在我们的网站登录了
           dd('没有账号，你太难了');
           // header('Location:www.laravel.com');
       }
    //   $where=[
    //     ['openid','=',$openid],
    //   ];
    //   // dd($where);
    //   $count=DB::connection('weixin')->table('user_wechat')->where($where)->count();
    // if($count<=0){
    //    $arr=['openid'=>$openid];
    //    // dd($arr);
    //   $re=DB::connection('weixin')->table('user_wechat')->insert($arr);
    //    dd($re);
    //
    // }else{
    //      echo "已有openid请更换";die;
    //   }
 
 
 
 }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Http\Tools\Wechat;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
class WechatController extends Controller
{
    public $request;
    public $wechat;
    public function __construct(Request $request,Wechat $wechat)
    {
        $this->request = $request;
        $this->wechat = $wechat;
    }
    /**
     * 粉丝列表
     */
    public function user_list(Request $request)
    {
        $tag_id = !empty($request->all()['tag_id'])?$request->all()['tag_id']:'';
        $openid_info = DB::connection('weixin')->table('wechat_openid')->get();
        return view('wechat.userList',['openid_info'=>$openid_info,'tag_id'=>$tag_id]);
    }
    /**
   * 根据标签为用户推送消息
   */
    public function push_tag_message(Request $request)
    {
        $re = $this->wechat->tag_user($request->all()['tag_id']);
        // dd($re);
        return view('wechat.pushTagMessage',['openid'=>json_encode($re['data']['openid']),'tag_id'=>$request->all()['tag_id']]);
    }
    /**
    * 执行根据标签为用户推送消息
    * @param Request $request
    */
   public function do_push_tag_message(Request $request)
   {
       $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token='.$this->wechat->get_access_token();
       $push_type = $request->all()['push_type'];
       if($push_type == 1){
           //文本消息
           $data = [
               'filter' => ['is_to_all'=>false,'tag_id'=>$request->all()['tag_id']],
               'text' => ['content' => $request->all()['message']],
               'msgtype' => 'text'
           ];
       }elseif($push_type == 2){
           //素材消息 图
           $data = [
               'filter' => ['is_to_all'=>false,'tag_id'=>$request->all()['tag_id']],
               'image' => ['media_id' => $request->all()['media_id']],
               'msgtype' => 'image'
           ];
       }
       $re = $this->wechat->post($url,json_encode($data,JSON_UNESCAPED_UNICODE));
       dd(json_decode($re,1));
   }
   /**
   * 获取用户标签
   */
  public function get_user_tag(Request $request){
      $url = 'https://api.weixin.qq.com/cgi-bin/tags/getidlist?access_token='.$this->wechat->get_access_token();
      $data = ['openid'=>$request->all()['openid']];
      $re = $this->wechat->post($url,json_encode($data));
      $user_tag_info = json_decode($re,1);
      $tag_info = $this->wechat->wechat_tag_list();
      $tag_arr = $tag_info['tags'];
      foreach($tag_arr as $v){
          foreach($user_tag_info['tagid_list'] as $vo){
              if($vo == $v['id']){
                  echo $v['name']."<a href='".env('APP_URL').'/wechat/del_user_tag'.'?tag_id='.$v['id'].'&openid='.$request->all()['openid']."'>删除</a><br/>";
              }
          }
      }
  }
  /**
    * 为用户删除标签
    * @param Request $request
    */
   public function del_user_tag(Request $request)
   {
       $url = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchuntagging?access_token='.$this->wechat->get_access_token();
       if(!is_array($request->all()['openid'])){
           $openid_list = [$request->all()['openid']];
       }else{
           $openid_list = $request->all()['openid'];
       }
       $data = [
           'openid_list' => $openid_list,
           'tagid' => $request->all()['tag_id']
       ];
       $re = $this->wechat->post($url,json_encode($data));
       dd(json_decode($re,1));
   }
   /**
       * 标签列表
       * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
       */
      public function tag_list(){
          $tag_info = $this->wechat->wechat_tag_list();
        //   $tag_info['tags'][0]['count']=2;
        //   dd($tag_info);
          return view('wechat/tagList',['info'=>$tag_info['tags']]);
      }
      /**
    * 删除标签
    * @param Request $request
    */
   public function del_tag(Request $request){
       $url = 'https://api.weixin.qq.com/cgi-bin/tags/delete?access_token='.$this->wechat->get_access_token();
       $data = [
           'tag' => ['id' => $request->all()['id']]
       ];
       $re = $this->wechat->post($url,json_encode($data));
       $result = json_decode($re,1);
       // dd($result);
       if($result){
         return redirect('wechat/tag_list');
       }
   }
   public function add_tag(){
          return view('wechat.addTag');
      }
      /**
      * 批量给用户打标签
      */
     public function add_user_tag(Request $request)
     {
         $openid_info = DB::connection('weixin')->table('wechat_openid')->whereIn('id',$request->all()['id_list'])->select(['openid'])->get()->toArray();
         $openid_list = [];
         foreach($openid_info as $v){
             $openid_list[] = $v->openid;
         }
         $url = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging?access_token='.$this->wechat->get_access_token();
         $data = [
             'openid_list'=>$openid_list,
             'tagid'=>$request->all()['tagid'],
         ];
         $re = $this->wechat->post($url,json_encode($data));
        //  dd(json_decode($re,1));
        return redirect('wechat/tag_list');
     }
      /**
          * 添加标签
          */
         public function do_add_tag(Request $request)
         {
             $url = 'https://api.weixin.qq.com/cgi-bin/tags/create?access_token='.$this->wechat->get_access_token();
             $data = [
                 'tag' => ['name'=>$request->all()['name']]
             ];
            // dd($data);
             $re = $this->wechat->post($url,json_encode($data,JSON_UNESCAPED_UNICODE));
             // dd(json_decode($re,1));
             if($re){
               return redirect('wechat/tag_list');
             }

         }
    /**
   * 标签下的粉丝列表
   */
  public function tag_user(Request $request)
  {
      $re = $this->wechat->tag_user($request->all()['id']);
      dd($re);
  }
  public function event()
      {
        // echo $_GET['echostr'];
        // die();
        //   $data = file_get_contents("php://input");
        //   //解析XML
        //   $xml = simplexml_load_string($data);        //将 xml字符串 转换成对象
        //   $xml = (array)$xml; //转化成数组
        //   \Log::Info(json_encode($xml));
        //   //echo $_GET['echostr'];
          //$this->checkSignature();
        $data = file_get_contents("php://input");
        dd($data);
        //解析XML
        $xml = simplexml_load_string($data,'SimpleXMLElement', LIBXML_NOCDATA);        //将 xml字符串 转换成对象
        $xml = (array)$xml; //转化成数组
        $log_str = date('Y-m-d H:i:s') . "\n" . $data . "\n<<<<<<<";
        file_put_contents(storage_path('logs/wx_event.log'),$log_str,FILE_APPEND);
        \Log::Info(json_encode($xml));
        $message = '你好!';
        $xml_str = '<xml><ToUserName><![CDATA['.$xml['FromUserName'].']]></ToUserName><FromUserName><![CDATA['.$xml['ToUserName'].']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.$message.']]></Content></xml>';
        echo $xml_str;
        //echo $_GET['echostr'];
      }
    /**
   * 模板列表
   */
  public function template_list()
  {
      $url = 'https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token='.$this->wechat->get_access_token();
      $re = file_get_contents($url);
      dd(json_decode($re,1));
  }

  public function del_template()
   {
       $url = 'https://api.weixin.qq.com/cgi-bin/template/del_private_template?access_token='.$this->wechat->get_access_token();
       $data = [
           'template_id' => 'cKsgHR1Azunc7wKy04pxJzu1oV2GRvE4gerEpGCadeI'
       ];
       $re = $this->wechat->post($url,json_encode($data));
       dd($re);

   }


    /**
     * 推送模板消息
     */
    public function push_template()
    {
        $openid_info = DB::connection('weixin')->table("wechat_openid")->select('openid')->limit(2)->get()->toArray();
        // dd($openid_info);
        foreach($openid_info as $v){
            $this->wechat->push_template($v->openid);
        }

    }

    /**
    * 我的素材
    */
   public function upload_source()
   {
       return view('wechat.uploadSource');
   }


public function get_voice_source()
{
    $media_id = 'UKml31rzRRlr8lYfWgAno9mGe-meph0BKmVtZugAHQTqZIxOhUoBvCnqfJMRMKTG';
    $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->wechat->get_access_token().'&media_id='.$media_id;
    //echo $url;echo '</br>';
    //保存图片
    $client = new Client();
    $response = $client->get($url);
    //$h = $response->getHeaders();
    //echo '<pre>';print_r($h);echo '</pre>';die;
    //获取文件名
    $file_info = $response->getHeader('Content-disposition');
    $file_name = substr(rtrim($file_info[0],'"'),-20);
    //$wx_image_path = 'wx/images/'.$file_name;
    //保存图片
    $path = 'wechat/voice/'.$file_name;
    $re = Storage::put($path, $response->getBody());
    echo env('APP_URL').'/storage/'.$path;
    dd($re);
}
    public function get_video_source(){
        $media_id = 'f9-GxYnNAinpu3qY4oFadJaodRVvB6JybJOhdjdbh7Z0CR0bm8nO4uh8bqSaiS_d'; //视频
        $url = 'http://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->wechat->get_access_token().'&media_id='.$media_id;
        $client = new Client();
        $response = $client->get($url);
        $video_url = json_decode($response->getBody(),1)['video_url'];
        $file_name = explode('/',parse_url($video_url)['path'])[2];
        //设置超时参数
        $opts=array(
            "http"=>array(
                "method"=>"GET",
                "timeout"=>3  //单位秒
            ),
        );
        //创建数据流上下文
        $context = stream_context_create($opts);
        //$url请求的地址，例如：
        $read = file_get_contents($video_url,false, $context);
        $re = file_put_contents('./storage/wechat/video/'.$file_name,$read);
        var_dump($re);
        die();
    }
    public function get_source()
    {
        $media_id = 'pREe_hxV86zjyFsmSlMNnewpYTFf5x6NuckIDkOTLgcF58FhejU-DNDucyme6x_n'; //图片
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->wechat->get_access_token().'&media_id='.$media_id;
        //echo $url;echo '</br>';
        //保存图片
        $client = new Client();
        $response = $client->get($url);
        //$h = $response->getHeaders();
        //echo '<pre>';print_r($h);echo '</pre>';die;
        //获取文件名
        $file_info = $response->getHeader('Content-disposition');
        $file_name = substr(rtrim($file_info[0],'"'),-20);
        //$wx_image_path = 'wx/images/'.$file_name;
        //保存图片
        $path = 'wechat/image/'.$file_name;
        $re = Storage::disk('local')->put($path, $response->getBody());
        echo env('APP_URL').'/storage/'.$path;
        dd($re);
        //return $file_name;
    }
   public function do_upload(Request $request)
   {
       $client = new Client();
       if($request->hasFile('image')){
           //图片类型
           $path = $request->file('image')->store('wechat/image');
           $path='./storage/'.$path;
           $url='https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . $this->wechat->get_access_token().'&type=image';
           $response = $client->request('POST',$url,[
               'multipart' => [
                   [
                       'name' => 'username',
                       'contents' => 'xiaoming'
                   ],
                   [
                       'name'     => 'media',
                       'contents' => fopen(realpath($path), 'r')
                   ],
               ]
           ]);
           // dd($response);
           //返回信息
           $body = $response->getBody();
           dd($body);
           unlink($path);
           echo $body;
          dd();
       }elseif($request->hasFile('voice')){
           //音频类型
           //保存文件
           $img_file = $request->file('voice');
           $file_ext = $img_file->getClientOriginalExtension();          //获取文件扩展名
           //重命名
           $new_file_name = time().rand(1000,9999). '.'.$file_ext;
           //文件保存路径
           //保存文件
           $save_file_path = $img_file->storeAs('wechat/voice',$new_file_name);       //返回保存成功之后的文件路径
           $path = './storage/'.$save_file_path;
           $url='https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->wechat->get_access_token().'&type=voice';
           $response = $client->request('POST',$url,[
               'multipart' => [
                   [
                       'name'     => 'media',
                       'contents' => fopen(realpath($path), 'r')
                   ],
               ]
           ]);
           $body = $response->getBody();
           unlink(realpath($path));
           echo $body;
           dd();
       }elseif($request->hasFile('video')){
           //视频
           //保存文件
           $img_file = $request->file('video');
           $file_ext = $img_file->getClientOriginalExtension();          //获取文件扩展名
           //重命名
           $new_file_name = time().rand(1000,9999). '.'.$file_ext;
           //文件保存路径
           //保存文件
           $save_file_path = $img_file->storeAs('wechat/video',$new_file_name);       //返回保存成功之后的文件路径
           $path = './storage/'.$save_file_path;
           $url='https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->wechat->get_access_token().'&type=video';
           $response = $client->request('POST',$url,[
               'multipart' => [
                   [
                       'name'     => 'media',
                       'contents' => fopen(realpath($path), 'r')
                   ],
               ]
           ]);
           $body = $response->getBody();
           dd($body);
           unlink(realpath($path));
           echo $body;
           dd();
       }elseif($request->hasFile('thumb')){
           //缩略图
           $path = $request->file('thumb')->store('wechat/thumb');
       }
   }


    public function get_user_info(){
      $access_token=$this->get_access_token();
      // dd($access_token);
        $openid="oJMd0wcq7cO14e9PYacBE8SP9yug";
        $wechat_user=file_get_contents("https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN");
        $user_info=json_decode($wechat_user,1);
        // dd($user_info);
        $res=DB::connection('weixin')->table('wechat_openid')->insert([
          'openid'=>$user_info['openid'],
          'subscribe'=>$user_info['subscribe'],
          'add_time'=>time(),
          'headimgurl'=>$user_info['headimgurl'],
          'nickname'=>$user_info['nickname']

        ]);
        // dd($res);
        return $user_info;
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
    public function get_user_lists(){
      $data=DB::connection('weixin')->table('wechat_openid')->get();
      return view('wechatlists',['data'=>$data]);
    }
      public function detail(Request $request){
      $id=$request->all();
      // dd($id);
      $data=DB::connection('weixin')->table('wechat_openid')->where(['id'=>$id])->first();
      // dd($data);
      $nickname=$data->nickname;
      $headimgurl=$data->headimgurl;
      $add_time=$data->add_time;
      return view('detail',['nickname'=>$nickname,'headimgurl'=>$headimgurl,'add_time'=>$add_time]);
    }
    public function get_user_list(){
      $access_token=$this->get_access_token();
      // dd($access_token);
      //拉取关注用户列表
      $wechat_user=file_get_contents("https://api.weixin.qq.com/cgi-bin/user/get?access_token={$access_token}&next_openid=");
      // dd($wechat_user);
      $user_info=json_decode($wechat_user,true);
      dd($user_info);
    }

    public function get_access_token(){
      //获取access_token
      $access_token="";
      $redis=new \Redis();
      $redis->connect('127.0.0.1','6379');
      $access_token_key='wechat_access_token';
      // dd($access_token_key);
      if($redis->exists($access_token_key)){
        //找到缓存
        $access_token=$redis->get($access_token_key);
        // dd($access_token);
      }else{
        //去微信拿接口
        $access_re=file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WECHAT_APPID')."&secret=".env('WECHAT_APPSECRET'));
        // dd($access_re);

        $access_result=json_decode($access_re,1);
        // dd($access_result);
        $access_token=$access_result['access_token'];
        // dd($access_token);
        $expire_time=$access_result['expires_in'];
        // dd($expire_time);
        //加入缓存
        $redis->set($access_token_key,$access_token,$expire_time);
      }

      return $access_token;
    }
}

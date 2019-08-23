<!doctype html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <center>
        <h1>login</h1>
        <form action="{{url('lonely/do_login')}}" method="post">
            @csrf
            <input type="text" name="name">
            <input type="password" name="password" id="">
            <a href="{{url('/liuyan/login')}}">微信登陆</a>
            <input type="submit" value="提交">
        </form>
    </center>
    <script type="text/javascript">
        $(function(){
            $.ajaxSetup({
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
            });
            $.ajax({});
        });


    </script>
</body>
</html>
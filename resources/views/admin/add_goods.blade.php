<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>添加货物</title>
</head>
<body>
    <center>
        <form method="post" action="{{url('/admin/do_add_goods')}}" enctype="multipart/form-data">
            @csrf
            货物<input type="file" name="goods_pic"><br> 
            <input type="submit" value="提交">
        </form>
    </center>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>student</title>
</head>
<body>
    <center>
        <h1>学生列表</h1>
        <table border='\'>
            <tr>
                <td>姓名</td>
                <td>id</td>
            </tr>
            @foreach($student as $key=>$item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->id }}</td>
            </tr>
            @endforeach
        </table>
        {{ $student->links() }}
    </center>
</body>
</html>
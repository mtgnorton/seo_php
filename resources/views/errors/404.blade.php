<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404</title>
    <style>
        *, html, body{ margin: 0; padding: 0;}
        .box{
            width: 600px;
            height: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
            position: absolute;
            top: 50%;
            margin-top: -150px;
            left: 50%;
            margin-left: -300px;
        }
        .box > img{
            margin-right: 40px;
        }
        p{
            font-size: 16px;
            margin: 20px 0;
            color: #41516E;
        }
        a{
            text-decoration: none;
            min-width: 80px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            color: #fff;
            background-color: #024fff;
            border-radius: 6px;
            display: inline-block;
            font-size: 14px;
            padding: 0 10px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <divc class="box">
        <img src="/asset/imgs/errors/1.png" alt="">
        <div class="content">
            <img src="/asset/imgs/errors/3.png" alt="">
            <p>抱歉，您访问的页面不存在</p>
            <a href="/">回到首页</a>
        </div>
    </div>
</body>
</html>
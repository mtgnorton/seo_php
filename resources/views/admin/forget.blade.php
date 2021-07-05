<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <title>小洋云站群</title>
    <link rel="stylesheet" href="/asset/css/reset.css" type="text/css">
    <style>
        .content .input_content{
            position: relative;
            margin-bottom: 40px;
        }
        .control-label{
            color: red;
            position: absolute;
            top: 70px;
            display: block;
            height: 30px;
            line-height: 30px;
            left: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <form action="{{ admin_url('auth/forget') }}" method="post">
                <img src="/asset/imgs/3.png" alt="">
                <h1>忘记密码</h1>
                <h2>输入授权码修改密码</h2>
                
                <div class="input_content">
                    <input type="password" name="password" placeholder="请输入新密码" placeholder-class="place_class" >
                    @if($errors->has('password'))
                    @foreach($errors->get('password') as $message)
                        <label class="control-label" for="inputError">{{$message}}</label>
                    @endforeach
                    @endif
                </div>

                

                <div class="input_content">
                    <input type="password" name="password_confirmation" placeholder="请再次输入登录密码 ">
                    @if($errors->has('password_confirmation'))
                    @foreach($errors->get('password_confirmation') as $message)
                        <label class="control-label" for="inputError">{{$message}}</label>
                    @endforeach
                    @endif
                </div>
                

                <div class="input_content">
                    <input type="text" name="code" placeholder="请输入授权码">
                    @if($errors->has('code'))
                    @foreach($errors->get('code') as $message)
                        <label class="control-label" for="inputError">{{$message}}</label>
                    @endforeach
                    @endif
                </div>
                
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input class="login_btn" type="submit" value="修改" />
            </form>
        </div>
    </div>
</body>
<!-- jQuery 2.1.4 -->
<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js")}} "></script>
<!-- Bootstrap 3.3.5 -->
<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE/bootstrap/js/bootstrap.min.js")}}"></script>
<script>
</script>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <title>小洋云SEO</title>
    <link rel="stylesheet" href="/asset/css/reset.css" type="text/css">
    <style>
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <form action="{{ admin_url('auth/login') }}" method="post">
                <img src="/asset/imgs/3.png" alt="">
                <h1>Hello,欢迎登录后台</h1>
                <h2>WELCOME TO THE SYSTEM</h2>
                
                <div class="input_content">
                    <input type="text" name="username" placeholder="请输入后台账号" placeholder-class="place_class" value="{{ old('username') }}">
                </div>
                <div class="input_content" style="margin-bottom: 20px;">
                    <input type="password" name="password" placeholder="请输入登录密码 ">
                </div>
                <input type="hidden" name="remember" value="0">
                <div class="input_content">
                    <div class="input_pass">记住密码</div>
                    <div class="input_pass1">忘记密码？</div>
                </div>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input class="login_btn" type="submit" value="登录" />
            </form>
        </div>
    </div>
</body>
<!-- jQuery 2.1.4 -->
<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js")}} "></script>
<!-- Bootstrap 3.3.5 -->
<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE/bootstrap/js/bootstrap.min.js")}}"></script>
<script>
$('.input_pass').click(function () {
    var url = $(this).css('background-image');
    if (url == 'url("{{asset('/asset/imgs/check.png')}}")') {
        $(this).css('background-image', 'url("{{asset('/asset/imgs/check1.png')}}")');
        $("input[name='remember']").val(1);
    } else {
        $(this).css('background-image', 'url("{{asset('/asset/imgs/check.png')}}")');
        $("input[name='remember']").val(0);
    }
});
</script>
</html>
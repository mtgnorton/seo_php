<?php

namespace App\Exceptions;

use App\Services\CommonService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use PDOException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param \Exception $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, Exception $exception)
    {

        // 如果是测试环境就返回完整错误
        if (config('app.env') == 'dev' || config('app.env') == 'local') {
            return parent::render($request, $exception);
        }
        if ($exception instanceof ValidationException) {
            // 表单验证不通过
            $message = $exception->validator->errors()->first();
        } else if ($exception instanceof NotFoundHttpException) {
            // 404
            $message = '页面不存在';
        } else if ($exception instanceof ModelNotFoundException) {
            // 模型找不到时抛出异常
            $message = '获取对象失败';
        } else if ($exception instanceof MethodNotAllowedHttpException) {
            // 方法不允许, get的用post访问
            $message = '方法访问方式错误';
        } else if ($exception instanceof PDOException) {
            // 数据库数据错误
            $message = '数据错误';
        } else {
            $message = $exception->getMessage();
        }

        // 获取当前请求类型
        $type = CommonService::requestType();
        if (in_array($type, ['pjax', 'ajax'])) {
            // 不同请求类型生成不同响应
            $response = CommonService::responseErrorType($message, $type);

            return $response;
        } else {
            return parent::render($request, $exception);
        }
    }
}

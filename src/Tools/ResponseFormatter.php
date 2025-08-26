<?php

namespace Webman\Yii2Orm\Tools;

use support\Response;
use Webman\Yii2Orm\DataProvider\BaseDataProvider;

class ResponseFormatter
{
    /**
     * 成功响应
     */
    public static function success($data = null, $message = '操作成功', $code = 200)
    {
        return static::json([
            'code' => 0,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
    
    /**
     * 错误响应
     */
    public static function error($message = '操作失败', $code = 400, $errors = null)
    {
        $response = [
            'code' => -1,
            'message' => $message,
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return static::json($response, $code);
    }
    
    /**
     * 分页数据响应
     */
    public static function paginated(BaseDataProvider $dataProvider, $message = '获取成功')
    {
        $data = [
            'items' => $dataProvider->getModels(),
            'pagination' => $dataProvider->getPagination() ? $dataProvider->getPagination()->toArray() : null,
            'sort' => $dataProvider->getSort() ? $dataProvider->getSort()->getLinks() : null,
        ];
        
        return static::success($data, $message);
    }
    
    /**
     * 验证错误响应
     */
    public static function validationError($errors, $message = '数据验证失败')
    {
        return static::error($message, 422, $errors);
    }
    
    /**
     * 列表响应
     */
    public static function list($items, $message = '获取成功')
    {
        return static::success([
            'items' => $items,
            'total' => count($items),
        ], $message);
    }
    
    /**
     * 创建响应
     */
    public static function created($data = null, $message = '创建成功')
    {
        return static::success($data, $message, 201);
    }
    
    /**
     * 更新响应
     */
    public static function updated($data = null, $message = '更新成功')
    {
        return static::success($data, $message);
    }
    
    /**
     * 删除响应
     */
    public static function deleted($message = '删除成功')
    {
        return static::success(null, $message);
    }
    
    /**
     * 未找到响应
     */
    public static function notFound($message = '资源不存在')
    {
        return static::error($message, 404);
    }
    
    /**
     * 未授权响应
     */
    public static function unauthorized($message = '未授权访问')
    {
        return static::error($message, 401);
    }
    
    /**
     * 禁止访问响应
     */
    public static function forbidden($message = '禁止访问')
    {
        return static::error($message, 403);
    }
    
    /**
     * JSON 响应
     */
    protected static function json($data, $httpCode = 200)
    {
        return new Response($httpCode, [
            'Content-Type' => 'application/json',
        ], json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
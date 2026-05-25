<?php
namespace app\middleware;

use app\model\User;
use app\service\JwtService;

class JwtAuth
{
    public function process($request, \Closure $next)
    {
        $header = $request->header('authorization', '');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return json(['code' => 401, 'msg' => '未登录']);
        }

        $token = substr($header, 7);
        try {
            $decoded = JwtService::decode($token);
            $user = User::find($decoded->uid);
            if (!$user || $user->status !== 1) {
                return json(['code' => 403, 'msg' => '账户已被禁用']);
            }
            $request->user = $user;
        } catch (\Exception $e) {
            return json(['code' => 401, 'msg' => '登录已过期']);
        }

        return $next($request);
    }
}

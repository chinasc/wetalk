<?php
namespace app\middleware;

class AdminAuth
{
    public function process($request, \Closure $next)
    {
        $user = $request->user;
        if ($user->role !== 'admin') {
            return json(['code' => 403, 'msg' => '无管理员权限']);
        }
        return $next($request);
    }
}

<?php
namespace app\middleware;

class CsAuth
{
    public function process($request, \Closure $next)
    {
        $user = $request->user;
        if ($user->role !== 'customer_service' && $user->role !== 'admin') {
            return json(['code' => 403, 'msg' => '无客服权限']);
        }
        return $next($request);
    }
}

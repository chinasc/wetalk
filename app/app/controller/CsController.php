<?php
namespace app\controller;

use app\model\User;
use app\service\WsService;
use support\Request;

class CsController
{
    public function index(Request $request)
    {
        $csList = User::where('role', 'customer_service')
            ->where('status', 1)
            ->orderBy('id')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'avatar' => $user->avatar,
                    'online' => WsService::isOnline($user->id),
                ];
            });

        return json(['code' => 0, 'data' => $csList]);
    }

    public function setCs(Request $request)
    {
        $userId = $request->post('user_id');
        $user = User::find($userId);
        if (!$user || $user->role !== 'user') {
            return json(['code' => 1, 'msg' => '用户不存在或非普通用户']);
        }
        $user->role = 'customer_service';
        $user->save();
        return json(['code' => 0, 'msg' => '已设为客服']);
    }

    public function removeCs(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user || $user->role !== 'customer_service') {
            return json(['code' => 1, 'msg' => '客服不存在']);
        }
        $user->role = 'user';
        $user->save();
        return json(['code' => 0, 'msg' => '已取消客服身份']);
    }
}

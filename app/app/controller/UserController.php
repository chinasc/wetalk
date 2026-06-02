<?php
namespace app\controller;

use app\model\User;
use support\Request;

class UserController
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $query = User::where('role', 'user');
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('nickname', 'like', "%{$keyword}%");
            });
        }
        
        $users = $query->orderBy('id', 'desc')->paginate(15);
        $items = $users->items();

        foreach ($items as $user) {
            $user->online = $user->isOnline();
        }

        return json(['code' => 0, 'data' => $users]);
    }

    public function store(Request $request)
    {
        $username = trim($request->post('username', ''));
        $nickname = trim($request->post('nickname', ''));
        $password = $request->post('password', '');

        if (!$username) {
            return json(['code' => 1, 'msg' => '请输入用户名']);
        }
        if (User::where('username', $username)->exists()) {
            return json(['code' => 1, 'msg' => '用户名已存在']);
        }

        $verifyCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'username' => $username,
            'nickname' => $nickname ?: $username,
            'password' => $password ? password_hash($password, PASSWORD_DEFAULT) : '',
            'verify_code' => $verifyCode,
            'verify_code_used' => 0,
            'password_set' => $password ? 1 : 0,
            'role' => 'user',
            'status' => 1,
        ]);

        return json([
            'code' => 0,
            'msg' => '创建成功',
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'verify_code' => $verifyCode,
            ],
        ]);
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }

        $verifyCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->verify_code = $verifyCode;
        $user->verify_code_used = 0;
        $user->password_set = 0;
        $user->password = password_hash('123456', PASSWORD_DEFAULT);
        $user->save();

        return json([
            'code' => 0,
            'msg' => '重置成功',
            'data' => ['verify_code' => $verifyCode],
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }
        $user->status = $user->status ? 0 : 1;
        $user->save();
        return json(['code' => 0, 'msg' => $user->status ? '已启用' : '已禁用']);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }
        if ($user->role === 'admin') {
            return json(['code' => 1, 'msg' => '不能删除管理员']);
        }
        $user->delete();
        return json(['code' => 0, 'msg' => '已删除']);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }

        $data = [];
        if ($nickname = trim($request->post('nickname', ''))) {
            $data['nickname'] = $nickname;
        }
        if ($password = $request->post('password', '')) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            $data['password_set'] = 1;
        }
        if (empty($data)) {
            return json(['code' => 1, 'msg' => '无更新内容']);
        }

        $user->update($data);
        return json(['code' => 0, 'msg' => '更新成功', 'data' => $user->fresh()]);
    }
}

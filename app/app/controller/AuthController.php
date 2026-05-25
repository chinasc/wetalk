<?php
namespace app\controller;

use app\model\User;
use app\service\JwtService;
use support\Request;

class AuthController
{
    /**
     * 用户端登录：首次需验证码，后续只需用户名
     */
    public function login(Request $request)
    {
        $username = trim($request->post('username', ''));
        $verifyCode = trim($request->post('verify_code', ''));

        if (!$username) {
            return json(['code' => 1, 'msg' => '请输入用户名']);
        }

        $user = User::where('username', $username)->where('status', 1)->first();
        if (!$user) {
            return json(['code' => 1, 'msg' => '账户不存在或已被禁用']);
        }

        // 未使用过验证码 = 首次登录，必须验证
        if (!$user->verify_code_used) {
            if (!$verifyCode) {
                return json(['code' => 2, 'msg' => '首次登录请输入验证码']);
            }
            if ($user->verify_code !== $verifyCode) {
                return json(['code' => 1, 'msg' => '验证码错误']);
            }
            User::where('id', $user->id)->update(['verify_code_used' => 1]);
        }

        $token = JwtService::generate($user->id);

        return json([
            'code' => 0,
            'msg' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nickname' => $user->nickname,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * 检查用户是否首次登录（用户端调用）
     */
    public function checkFirstLogin(Request $request)
    {
        $username = trim($request->post('username', ''));
        if (!$username) {
            return json(['code' => 1, 'msg' => '请输入用户名']);
        }

        $user = User::where('username', $username)->where('status', 1)->first();
        if (!$user) {
            return json(['code' => 1, 'msg' => '账户不存在或已被禁用']);
        }

        return json([
            'code' => 0,
            'data' => [
                'is_first_login' => !$user->verify_code_used,
            ],
        ]);
    }

    /**
     * 管理后台登录：账号 + 密码，且必须是管理员角色
     */
    public function adminLogin(Request $request)
    {
        $username = trim($request->post('username', ''));
        $password = $request->post('password', '');

        if (!$username || !$password) {
            return json(['code' => 1, 'msg' => '请输入用户名和密码']);
        }

        $user = User::where('username', $username)->where('status', 1)->first();
        if (!$user) {
            return json(['code' => 1, 'msg' => '账号不存在或已被禁用']);
        }

        if ($user->role !== 'admin') {
            return json(['code' => 1, 'msg' => '该账号不是管理员账号']);
        }

        if (!$user->password || !password_verify($password, $user->password)) {
            return json(['code' => 1, 'msg' => '密码错误']);
        }

        $token = JwtService::generate($user->id);

        return json([
            'code' => 0,
            'msg' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nickname' => $user->nickname,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * 客服端登录：账号 + 密码，且必须是客服角色
     */
    public function csLogin(Request $request)
    {
        $username = trim($request->post('username', ''));
        $password = $request->post('password', '');

        if (!$username || !$password) {
            return json(['code' => 1, 'msg' => '请输入用户名和密码']);
        }

        $user = User::where('username', $username)->where('status', 1)->first();
        if (!$user) {
            return json(['code' => 1, 'msg' => '账号不存在或已被禁用']);
        }

        if ($user->role !== 'customer_service') {
            return json(['code' => 1, 'msg' => '该账号不是客服账号']);
        }

        if (!$user->password || !password_verify($password, $user->password)) {
            return json(['code' => 1, 'msg' => '密码错误']);
        }

        $token = JwtService::generate($user->id);

        return json([
            'code' => 0,
            'msg' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nickname' => $user->nickname,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    public function info(Request $request)
    {
        $user = $request->user;
        return json([
            'code' => 0,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'role' => $user->role,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user;
        $data = [];
        if ($nickname = trim($request->post('nickname', ''))) {
            $data['nickname'] = $nickname;
        }
        if ($request->file('avatar')) {
            $file = $request->file('avatar');
            $filename = uniqid() . '.' . $file->getUploadExtension();
            $file->move(public_path() . '/upload/avatars/' . $filename);
            $data['avatar'] = '/upload/avatars/' . $filename;
        }
        if ($data) {
            $user->update($data);
        }
        return json(['code' => 0, 'msg' => '更新成功', 'data' => $user->fresh()]);
    }
}

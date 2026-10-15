<?php
namespace app\controller;

use app\model\User;
use app\model\UserDevice;
use app\service\JwtService;
use support\Request;

class AuthController
{
    /**
     * 用户端登录：账号 + 密码，换设备后旧密码失效，需重新获取新密码
     */
    public function login(Request $request)
    {
        $username = trim($request->post('username', ''));
        $password = trim($request->post('verify_code', ''));
        $deviceToken = trim($request->post('device_token', ''));

        if (!$username) {
            return json(['code' => 1, 'msg' => '请输入用户名']);
        }

        $user = User::where('username', $username)->where('status', 1)->first();
        if (!$user) {
            return json(['code' => 1, 'msg' => '账户不存在或已被禁用']);
        }

        if (!$password) {
            return json(['code' => 1, 'msg' => '请输入密码']);
        }
        if ($user->verify_code !== $password) {
            return json(['code' => 1, 'msg' => '密码错误']);
        }

        if (!$user->verify_code_used) {
            // 新密码首次使用：踢掉旧设备，绑定当前设备并激活
            UserDevice::where('user_id', $user->id)->delete();
            if ($deviceToken) {
                UserDevice::create([
                    'user_id' => $user->id,
                    'device_token' => $deviceToken,
                ]);
            }
            User::where('id', $user->id)->update(['verify_code_used' => 1]);
        } else {
            // 已激活的密码仅限已绑定设备使用
            $deviceOk = $deviceToken && UserDevice::where('user_id', $user->id)
                ->where('device_token', $deviceToken)
                ->exists();
            if (!$deviceOk) {
                // 换设备：作废旧密码并生成新密码，需联系管理员获取
                $newCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                User::where('id', $user->id)->update([
                    'verify_code' => $newCode,
                    'verify_code_used' => 0,
                ]);
                UserDevice::where('user_id', $user->id)->delete();
                return json(['code' => 3, 'msg' => '检测到新设备登录，原密码已失效，请联系管理员获取新密码']);
            }
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

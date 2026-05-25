<?php
namespace app\controller;

use app\model\Setting;
use support\Request;

class SettingsController
{
    public function index(Request $request)
    {
        $settings = Setting::all()->pluck('value', 'key');
        return json(['code' => 0, 'data' => $settings]);
    }

    public function update(Request $request)
    {
        $data = $request->post();
        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function getAccessCode(Request $request)
    {
        return json(['code' => 0, 'data' => ['chat_access_code' => Setting::get('chat_access_code', '8888')]]);
    }
}

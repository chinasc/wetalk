<?php
namespace app\controller;

use support\Request;

class UploadController
{
    public function image(Request $request)
    {
        if (!$request->file('file')) {
            return json(['code' => 1, 'msg' => '请选择文件']);
        }

        $file = $request->file('file');
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getUploadMimeType(), $allowedMimes)) {
            return json(['code' => 1, 'msg' => '仅支持图片文件']);
        }

        $maxSize = ((int)\app\model\Setting::get('upload_max_size', 20)) * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            return json(['code' => 1, 'msg' => '文件过大']);
        }

        $ext = $file->getUploadExtension();
        $safeExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if (!in_array(strtolower($ext), $safeExts)) {
            $ext = 'jpg';
        }
        $filename = uniqid() . '.' . $ext;
        $file->move(public_path() . '/upload/chat/' . $filename);
        return json(['code' => 0, 'data' => ['url' => '/upload/chat/' . $filename]]);
    }
}

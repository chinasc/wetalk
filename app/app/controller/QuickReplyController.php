<?php
namespace app\controller;

use app\model\QuickReply;
use support\Request;

class QuickReplyController
{
    public function index(Request $request)
    {
        $replies = QuickReply::orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->get();

        return json(['code' => 0, 'data' => $replies]);
    }

    public function store(Request $request)
    {
        $title = trim($request->post('title', ''));
        $content = trim($request->post('content', ''));

        if (!$title || !$content) {
            return json(['code' => 1, 'msg' => '标题和内容不能为空']);
        }

        $reply = QuickReply::create([
            'title' => $title,
            'content' => $content,
            'sort_order' => (int)$request->post('sort_order', 0),
        ]);

        return json(['code' => 0, 'msg' => '创建成功', 'data' => $reply]);
    }

    public function update(Request $request, $id)
    {
        $reply = QuickReply::find($id);
        if (!$reply) {
            return json(['code' => 1, 'msg' => '常用语不存在']);
        }

        $data = [];
        foreach (['title', 'content', 'sort_order'] as $field) {
            if ($request->post($field) !== null) {
                $data[$field] = $field === 'sort_order'
                    ? (int)$request->post($field)
                    : trim($request->post($field));
            }
        }

        $reply->update($data);
        return json(['code' => 0, 'msg' => '更新成功', 'data' => $reply->fresh()]);
    }

    public function destroy(Request $request, $id)
    {
        $reply = QuickReply::find($id);
        if (!$reply) {
            return json(['code' => 1, 'msg' => '常用语不存在']);
        }
        $reply->delete();
        return json(['code' => 0, 'msg' => '已删除']);
    }
}

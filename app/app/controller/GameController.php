<?php
namespace app\controller;

use app\model\Game;
use support\Request;

class GameController
{
    public function index(Request $request)
    {
        $games = Game::where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->get();

        return json(['code' => 0, 'data' => $games]);
    }

    public function store(Request $request)
    {
        $name = trim($request->post('name', ''));
        if (!$name) {
            return json(['code' => 1, 'msg' => '请输入游戏名称']);
        }

        $data = [
            'name' => $name,
            'description' => $request->post('description', ''),
            'url' => $request->post('url', ''),
            'sort_order' => (int)$request->post('sort_order', 0),
            'status' => (int)$request->post('status', 1),
        ];

        if ($request->file('cover')) {
            $file = $request->file('cover');
            $filename = uniqid() . '.' . $file->getUploadExtension();
            $file->move(public_path() . '/upload/games/' . $filename);
            $data['cover'] = '/upload/games/' . $filename;
        }

        $game = Game::create($data);
        return json(['code' => 0, 'msg' => '创建成功', 'data' => $game]);
    }

    public function update(Request $request, $id)
    {
        $game = Game::find($id);
        if (!$game) {
            return json(['code' => 1, 'msg' => '游戏不存在']);
        }

        $data = [];
        foreach (['name', 'description', 'url', 'sort_order', 'status'] as $field) {
            if ($request->post($field) !== null) {
                $data[$field] = $request->post($field);
            }
        }

        if ($request->file('cover')) {
            $file = $request->file('cover');
            $filename = uniqid() . '.' . $file->getUploadExtension();
            $file->move(public_path() . '/upload/games/' . $filename);
            $data['cover'] = '/upload/games/' . $filename;
        }

        $game->update($data);
        return json(['code' => 0, 'msg' => '更新成功', 'data' => $game->fresh()]);
    }

    public function destroy(Request $request, $id)
    {
        $game = Game::find($id);
        if (!$game) {
            return json(['code' => 1, 'msg' => '游戏不存在']);
        }
        $game->delete();
        return json(['code' => 0, 'msg' => '已删除']);
    }
}

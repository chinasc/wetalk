<?php
namespace app\controller;

use app\model\Album;
use app\model\AlbumImage;
use support\Request;

class AlbumController
{
    public function index(Request $request)
    {
        $albums = Album::withCount('images')->orderBy('sort_order')->orderBy('id', 'desc')->get();
        return json(['code' => 0, 'data' => $albums]);
    }

    public function show(Request $request, $id)
    {
        $album = Album::with('images')->find($id);
        if (!$album) {
            return json(['code' => 1, 'msg' => '相册不存在']);
        }
        return json(['code' => 0, 'data' => $album]);
    }

    public function store(Request $request)
    {
        $name = trim($request->post('name', ''));
        if (!$name) {
            return json(['code' => 1, 'msg' => '请输入相册名称']);
        }

        $data = ['name' => $name, 'sort_order' => (int)$request->post('sort_order', 0)];

        if ($request->file('cover')) {
            $file = $request->file('cover');
            $filename = uniqid() . '.' . $file->getUploadExtension();
            $file->move(public_path() . '/upload/albums/' . $filename);
            $data['cover'] = '/upload/albums/' . $filename;
        }

        $album = Album::create($data);
        return json(['code' => 0, 'msg' => '创建成功', 'data' => $album]);
    }

    public function update(Request $request, $id)
    {
        $album = Album::find($id);
        if (!$album) {
            return json(['code' => 1, 'msg' => '相册不存在']);
        }

        $data = [];
        if ($name = trim($request->post('name', ''))) {
            $data['name'] = $name;
        }
        if ($request->post('sort_order') !== null) {
            $data['sort_order'] = (int)$request->post('sort_order');
        }
        if ($request->file('cover')) {
            $file = $request->file('cover');
            $filename = uniqid() . '.' . $file->getUploadExtension();
            $file->move(public_path() . '/upload/albums/' . $filename);
            $data['cover'] = '/upload/albums/' . $filename;
        }

        $album->update($data);
        return json(['code' => 0, 'msg' => '更新成功', 'data' => $album->fresh()]);
    }

    public function destroy(Request $request, $id)
    {
        $album = Album::find($id);
        if (!$album) {
            return json(['code' => 1, 'msg' => '相册不存在']);
        }
        foreach ($album->images as $img) {
            @unlink(public_path() . $img->image_path);
            if ($img->thumbnail_path) {
                @unlink(public_path() . $img->thumbnail_path);
            }
        }
        $album->images()->delete();
        $album->delete();
        return json(['code' => 0, 'msg' => '已删除']);
    }

    public function uploadImage(Request $request, $id)
    {
        $album = Album::find($id);
        if (!$album) {
            return json(['code' => 1, 'msg' => '相册不存在']);
        }

        if (!$request->file('image')) {
            return json(['code' => 1, 'msg' => '请选择图片']);
        }

        $file = $request->file('image');
        $filename = uniqid() . '.' . $file->getUploadExtension();
        $file->move(public_path() . '/upload/albums/images/' . $filename);
        $imagePath = '/upload/albums/images/' . $filename;

        $remark = trim($request->post('remark', ''));
        $sortOrder = (int)$request->post('sort_order', 0);

        $image = AlbumImage::create([
            'album_id' => $album->id,
            'image_path' => $imagePath,
            'thumbnail_path' => '',
            'remark' => $remark,
            'sort_order' => $sortOrder,
        ]);

        if (!$album->cover) {
            $album->cover = $imagePath;
            $album->save();
        }

        return json(['code' => 0, 'msg' => '上传成功', 'data' => $image]);
    }

    public function updateImage(Request $request, $imageId)
    {
        $image = AlbumImage::find($imageId);
        if (!$image) {
            return json(['code' => 1, 'msg' => '图片不存在']);
        }

        $data = [];
        if ($request->post('remark') !== null) {
            $data['remark'] = $request->post('remark');
        }
        if ($request->post('sort_order') !== null) {
            $data['sort_order'] = (int)$request->post('sort_order');
        }
        $image->update($data);

        return json(['code' => 0, 'msg' => '更新成功', 'data' => $image->fresh()]);
    }

    public function deleteImage(Request $request, $imageId)
    {
        $image = AlbumImage::find($imageId);
        if (!$image) {
            return json(['code' => 1, 'msg' => '图片不存在']);
        }
        @unlink(public_path() . $image->image_path);
        if ($image->thumbnail_path) {
            @unlink(public_path() . $image->thumbnail_path);
        }
        $image->delete();
        return json(['code' => 0, 'msg' => '已删除']);
    }
}

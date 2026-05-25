<?php
namespace app\model;

use Illuminate\Database\Eloquent\Model;

class AlbumImage extends Model
{
    protected $table = 'album_images';
    public $timestamps = false;
    protected $fillable = ['album_id', 'image_path', 'thumbnail_path', 'remark', 'sort_order'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return $this->image_path;
    }

    public function album()
    {
        return $this->belongsTo(Album::class);
    }
}

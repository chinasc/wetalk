<?php
namespace app\model;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    protected $table = 'albums';
    protected $fillable = ['name', 'cover', 'sort_order'];

    public function images()
    {
        return $this->hasMany(AlbumImage::class)->orderBy('sort_order');
    }
}

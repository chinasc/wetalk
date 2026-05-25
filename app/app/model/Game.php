<?php
namespace app\model;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'games';
    public $timestamps = false;
    protected $fillable = ['name', 'cover', 'description', 'url', 'sort_order', 'status', 'created_at'];
}

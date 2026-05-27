<?php
namespace app\model;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $table = 'user_devices';
    protected $fillable = ['user_id', 'device_token'];
    public $timestamps = false;
}

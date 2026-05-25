<?php
namespace app\model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['username', 'password', 'nickname', 'avatar', 'role', 'status', 'verify_code', 'verify_code_used', 'password_set'];
    protected $hidden = ['password'];

    public function isOnline(): bool
    {
        return (bool)\support\Redis::get('user_online:' . $this->id);
    }
}

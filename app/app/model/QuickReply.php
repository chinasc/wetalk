<?php
namespace app\model;

use Illuminate\Database\Eloquent\Model;

class QuickReply extends Model
{
    protected $table = 'quick_replies';
    protected $fillable = ['title', 'content', 'sort_order'];
}

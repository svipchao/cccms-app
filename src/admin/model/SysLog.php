<?php
declare(strict_types=1);

namespace app\admin\model;

use think\model\relation\HasOne;
use cccms\Model;

class SysLog extends Model
{
    protected $hidden = ['user'];

    public function user(): HasOne
    {
        return $this->hasOne(SysUser::class, 'id', 'user_id')->bind([
            'nickname',
            'username'
        ]);
    }

    public function searchUserAttr($query, $value)
    {
        $query->hasWhere('user', function ($query) use ($value) {
            $query->where('nickname|username', 'like', "%" . $value . "%");
        });
    }
}
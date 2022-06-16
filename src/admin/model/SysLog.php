<?php
declare(strict_types=1);

namespace app\admin\model;

use think\model\relation\{HasOne, HasMany};
use cccms\Model;
use cccms\services\AuthService;

class SysLog extends Model
{
    protected $hidden = ['user'];

    public function user(): HasOne
    {
        return $this->hasOne(SysUser::class, 'id', 'user_id')
            ->bind(['nickname', 'username']);
    }

    // 关联权限记录表
    public function relationAuth(): HasMany
    {
        return $this->hasMany(SysAuth::class, 'user_id', 'user_id');
    }

    public function searchUserAttr($query, $value)
    {
        // 管理员可以查看任何用户
        $query->when(!AuthService::instance()->isAdmin(), function ($query) {
            $query->hasWhere('relationAuth', [
                ['group_id', 'in', AuthService::instance()->getUserGroups(true, false, true)]
            ])->whereOr('id', AuthService::instance()->getUserInfo('id'));
        });
        $query->hasWhere('user', function ($query) use ($value) {
            $query->where('nickname|username', 'like', "%" . $value . "%");
        });
    }
}
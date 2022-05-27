<?php
declare(strict_types=1);

namespace app\admin\model;

use think\facade\Cache;
use think\model\relation\belongsToMany;
use cccms\Model;

class SysGroup extends Model
{
    protected $hidden = ['pivot'];

    public static function onBeforeWrite($model)
    {
        Cache::delete('SysGroups');
    }

    public function roles(): belongsToMany
    {
        return $this->belongsToMany(SysRole::class, SysGroupRole::class, 'role_id', 'group_id');
    }
}
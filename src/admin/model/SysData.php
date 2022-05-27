<?php
declare (strict_types=1);

namespace app\admin\model;

use think\facade\Cache;
use think\model\relation\HasOne;
use cccms\Model;

class SysData extends Model
{
    protected $hidden = ['role'];

    public static function onBeforeWrite($model)
    {
        Cache::delete('SysData');
    }

    public function role(): HasOne
    {
        return $this->hasOne(SysRole::class, 'id', 'role_id')->bind([
            'role_name',
        ]);
    }
}

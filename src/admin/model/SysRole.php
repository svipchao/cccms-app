<?php
declare(strict_types=1);

namespace app\admin\model;

use cccms\Model;
use cccms\services\AuthService;
use think\facade\Cache;
use think\model\relation\HasMany;

class SysRole extends Model
{
    protected $hidden = ['pivot'];

    public static function onBeforeWrite($model)
    {
        Cache::delete('SysRoles');
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(SysRoleNode::class, 'role_id', 'id');
    }

    public function setRoleIdAttr($value, $data): int
    {
        if (!in_array($value, AuthService::instance()->getUserRoles(true))) {
            _result(['code' => 403, 'msg' => '未拥有该角色'], _getEnCode());
        }
        if (in_array($value, AuthService::instance()->getRoleChildren((int)$data['id'], true))) {
            _result(['code' => 403, 'msg' => '不能选择自己的子角色'], _getEnCode());
        }
        return (int)$value;
    }
}
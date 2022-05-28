<?php
declare(strict_types=1);

namespace app\admin\model;

use think\facade\Cache;
use cccms\extend\ArrExtend;
use cccms\services\AuthService;
use think\model\relation\belongsToMany;
use cccms\Model;

class SysGroup extends Model
{
    protected $hidden = ['pivot'];

    // 写入后
    public static function onAfterWrite($model)
    {
        Cache::delete('SysGroups');
        if (isset($model['roles'])) {
            if (is_string($model['roles'])) {
                $model['roles'] = explode(',', $model['roles']);
            }
            $model->nodes()->delete();
            $model->nodes()->saveAll($model['roles']);
        }
    }

    // 删除前
    public static function onBeforeDelete($model)
    {
        if (!in_array($model['id'], AuthService::instance()->getUserGroups(true))) {
            _result(['code' => 403, 'msg' => '未拥有该组织'], _getEnCode());
        }
        if (count(AuthService::instance()->getGroupChildren((int)$model['id'], true)) > 1) {
            _result(['code' => 403, 'msg' => '存在子级组织，禁止删除'], _getEnCode());
        }
    }

    // 删除后
    public static function onAfterDelete($model)
    {
        $model->roles()->detach();
    }

    public function roles(): belongsToMany
    {
        return $this->belongsToMany(SysRole::class, SysGroupRole::class, 'role_id', 'group_id')
            ->wherePivot('role_id', 'in', AuthService::instance()->getUserRoles(true));
    }

    public function loginRoles(): belongsToMany
    {
        return $this->belongsToMany(SysRole::class, SysGroupRole::class, 'role_id', 'group_id');
    }

    public function setRoleIdAttr($value, $data): int
    {
        if (AuthService::instance()->isAdmin()) {
            return (int)$value;
        }
        if (!in_array($value, AuthService::instance()->getUserGroups(true))) {
            _result(['code' => 403, 'msg' => '未拥有该组织'], _getEnCode());
        }
        if (in_array($value, AuthService::instance()->getGroupChildren((int)$data['id'], true))) {
            _result(['code' => 403, 'msg' => '不能选择自己的子组织'], _getEnCode());
        }
        return (int)$value;
    }
}
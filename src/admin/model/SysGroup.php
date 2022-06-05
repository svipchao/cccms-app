<?php
declare(strict_types=1);

namespace app\admin\model;

use think\facade\Cache;
use think\model\relation\belongsToMany;
use cccms\Model;
use cccms\services\AuthService;

class SysGroup extends Model
{
    protected $hidden = ['pivot'];

    // 写入后
    public static function onAfterWrite($model)
    {
        Cache::delete('SysGroups');
        if (!empty($model['role_ids'])) {
            if (is_string($model['role_ids'])) {
                $model['role_ids'] = explode(',', $model['role_ids']);
            }
            $model->roles()->detach();
            $model->roles()->saveAll($model['role_ids']);
        }
    }

    // 删除前
    public static function onBeforeDelete($model)
    {
        if (!in_array($model['id'], AuthService::instance()->getUserGroups(true))) {
            _result(['code' => 403, 'msg' => '未拥有该组织'], _getEnCode());
        }
        if (!empty(AuthService::instance()->getGroupChildren((int)$model['id'], false))) {
            _result(['code' => 403, 'msg' => '存在子级组织，禁止删除'], _getEnCode());
        }
    }

    // 删除后
    public static function onAfterDelete($model)
    {
        // 删除组织角色关联数据
        $model->roles()->detach();
        // 删除组织用户关联数据
        $model->users()->detach();
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

    // 关联用户
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(SysUser::class, SysUserGroup::class, 'user_id', 'group_id');
    }

    public function setGroupIdAttr($value, $data): int
    {
        if (empty($value) && AuthService::instance()->isAdmin()) return 0;
        if (!in_array($value, AuthService::instance()->getUserGroups(true))) {
            _result(['code' => 403, 'msg' => '未拥有该组织'], _getEnCode());
        }
        if (isset($data['id'])) {
            if (in_array($value, AuthService::instance()->getGroupChildren((int)$data['id'], false))) {
                _result(['code' => 403, 'msg' => '不能选择自己的子组织'], _getEnCode());
            }
        }
        return (int)$value;
    }
}
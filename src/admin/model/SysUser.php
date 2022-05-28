<?php

declare(strict_types=1);

namespace app\admin\model;

use think\model\relation\BelongsToMany;
use cccms\Model;
use cccms\services\AuthService;

class SysUser extends Model
{
    protected $append = ['type_text'];

    public static function onBeforeWrite($model)
    {
        if (!isset($model['id'])) {
            $model['token'] = md5(mt_rand(0, time()) . time());
        }
    }

    // 写入后
    public static function onAfterWrite($model)
    {
        if (isset($model['groupIds'])) {
            // 删除组织关联权限节点表数据
            $model->append([])->groups()->detach();
            $model->append([])->groups()->attach($model['groupIds']);
        }
    }

    // 删除前
    public static function onBeforeDelete($model)
    {
        if ($model['id'] === _getAccessToken('id')) {
            _result(['code' => 403, 'msg' => '禁止删除自己的账户'], _getEnCode());
        }
    }

    // 删除后
    public static function onAfterDelete($model)
    {
        $model->groups()->detach();
    }

    // 关联组织
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(SysGroup::class, SysUserGroup::class, 'group_id', 'user_id')
            ->wherePivot('group_id', 'in', AuthService::instance()->getUserGroups(true));
    }

    // 关联组织
    public function loginGroups(): BelongsToMany
    {
        return $this->belongsToMany(SysGroup::class, SysUserGroup::class, 'group_id', 'user_id');
    }

    // 获取当前用户拥有的组织下的所有用户
    public function getCurrentUserGroupUser(): array
    {
        if (AuthService::instance()->isAuth('admin/group/index')) {
            $groupIds = AuthService::instance()->getUserGroups(true);
            return SysUserGroup::where('group_id', 'in', $groupIds)->column('user_id');
        } else {
            return [AuthService::instance()->getUserInfo('id')];
        }
    }

    // 设置密码
    public function setPassWordAttr($value, $data)
    {
        if (empty($value)) {
            unset($data['password']);
            return $this->data($data, true);
        }
        return md5($value);
    }

    // 获取密码
    public function getPassWordAttr(): string
    {
        return '';
    }

    // 获取用户类型
    public function getTypeTextAttr($value, $data): string
    {
        return config('cccms.user.types')[$data['type']];
    }
}

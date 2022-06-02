<?php
declare(strict_types=1);

namespace app\admin\model;

use think\model\relation\{hasMany, BelongsToMany};
use cccms\Model;
use cccms\services\AuthService;

class SysUser extends Model
{
    protected $append = ['type_text'];

    // 写入前
    public static function onBeforeWrite($model)
    {
        if (empty($model['group_ids'])) {
            _result(['code' => 403, 'msg' => '请为用户指定一个组织'], _getEnCode());
        }
        if (!isset($model['id'])) {
            $model['token'] = md5(mt_rand(0, time()) . time());
        }
    }

    // 写入后
    public static function onAfterWrite($model)
    {
        if (isset($model['group_ids'])) {
            if (is_string($model['group_ids'])) {
                $model['group_ids'] = explode(',', $model['group_ids']);
            }
            // 删除组织关联权限节点表数据
            $model->append([])->groups()->detach();
            $model->append([])->groups()->attach($model['group_ids']);
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

    // 关联用户组织中间表
    public function userGroups(): hasMany
    {
        return $this->hasMany(SysUserGroup::class, 'user_id', 'id');
    }

    // 关联组织
    public function loginGroups(): BelongsToMany
    {
        return $this->belongsToMany(SysGroup::class, SysUserGroup::class, 'group_id', 'user_id');
    }

    // 用户搜索器
    public function searchUserAttr($query, $value, $data)
    {
        $query->where('nickname|username', 'like', '%' . $value . '%');
    }

    // 组织用户搜索器
    public function searchGroupIdAttr($query, $value, $data)
    {
        if (empty($value) && !AuthService::instance()->isAdmin()) {
            $value = implode(',', AuthService::instance()->getUserGroups(true));
        } else {
            if (is_string($value)) {
                $value = explode(',', $value);
            }
            $value = implode(',', array_intersect(AuthService::instance()->getUserGroups(true), $value ?: []));
        }
        if (!empty($value)) {
            $query->hasWhere('userGroups', function ($query) use ($value) {
                $query->where('group_id', 'in', $value);
            });
        }
    }

    // 用户类型搜索器
    public function searchTypeAttr($query, $value, $data)
    {
        if ($value != null) {
            $query->where('type', '=', $value);
        }
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
    public function getTypeTextAttr($value, $data)
    {
        return isset($data['type']) ? config('cccms.user.types')[$data['type']] ?? '未知' : false;
    }
}

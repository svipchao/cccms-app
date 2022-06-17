<?php
declare(strict_types=1);

namespace app\admin\model;

use think\model\relation\{HasMany, BelongsToMany};
use cccms\Model;
use cccms\services\AuthService;

class SysUser extends Model
{
    // 写入后
    public static function onAfterWrite($model)
    {
        if (isset($model['group_ids'])) {
            if (is_string($model['group_ids'])) {
                $model['group_ids'] = explode(',', $model['group_ids']);
            }
            // 删除组织关联权限节点表数据
            $model->groups()->detach($model->groups()->column('id'));
            $model->groups()->attach($model['group_ids']);
        }
    }

    // 删除前
    public static function onBeforeDelete($model)
    {
        if ($model['id'] === AuthService::instance()->getUserInfo('id')) {
            _result(['code' => 403, 'msg' => '禁止删除自己的账户'], _getEnCode());
        }
    }

    // 删除后
    public static function onAfterDelete($model)
    {
        $model->groups()->detach($model->groups()->column('id'));
    }

    // 关联组织
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(SysGroup::class, SysAuth::class, 'group_id', 'user_id');
    }

    // 关联权限记录表
    public function relationAuth(): HasMany
    {
        return $this->hasMany(SysAuth::class, 'user_id', 'id');
    }

    public function searchUserAttr($query, $value)
    {
        // 管理员可以查看任何用户
        $query->when(!AuthService::instance()->isAdmin(), function ($query) {
            $query->hasWhere('relationAuth', [
                ['group_id', 'in', AuthService::instance()->getUserGroups(true, false, true)]
            ])->whereOr('id', AuthService::instance()->getUserInfo('id'));
        });
        $query->where('nickname|username', 'like', '%' . $value . '%');
    }

    public function searchGroupIdAttr($query, $value)
    {
        $query->hasWhere('relationAuth', function ($query) use ($value) {
            $query->where('group_id', 'in', $value);
        });
    }

    public function searchTypeAttr($query, $value)
    {
        $query->where('type', '=', $value);
    }

    public function setTokenAttr($value): string
    {
        return md5(mt_rand(0, time()) . time());
    }

    public function setPassWordAttr($value, $data)
    {
        if (empty($value)) {
            unset($data['password']);
            return $this->data($data, true);
        }
        return md5($value);
    }

    public function setStatusAttr($value, $data)
    {
        if ($data['id'] == 1) $value = 1;
        if ($data['id'] == AuthService::instance()->getUserInfo('id')) {
            _result(['code' => 403, 'msg' => '不能禁止自己的账户'], _getEnCode());
        }
        return $value;
    }

    public function getPassWordAttr(): string
    {
        return '';
    }

    public function getTypeTextAttr($value, $data)
    {
        return isset($data['type']) ? config('cccms.user.types')[$data['type']] ?? '未知' : false;
    }
}

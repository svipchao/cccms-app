<?php
declare(strict_types=1);

namespace app\admin\model;

use think\facade\Cache;
use think\model\relation\HasMany;
use cccms\Model;
use cccms\extend\ArrExtend;
use cccms\services\AuthService;
use think\model\relation\BelongsToMany;

class SysRole extends Model
{
    protected $hidden = ['pivot'];

    // 写入后
    public static function onAfterWrite($model)
    {
        Cache::delete('SysRoles');
        if (isset($model['nodes'])) {
            if (is_string($model['nodes'])) {
                $model['nodes'] = explode(',', $model['nodes']);
            }
            $model->nodes()->delete();
            $model->nodes()->saveAll(ArrExtend::createTwoArray($model['nodes'], 'node'));
        }
    }

    // 删除前
    public static function onBeforeDelete($model)
    {
        if (!in_array($model['id'], AuthService::instance()->getUserRoles(true))) {
            _result(['code' => 403, 'msg' => '未拥有该角色'], _getEnCode());
        }
        if (!empty(AuthService::instance()->getRoleChildren((int)$model['id'], false))) {
            _result(['code' => 403, 'msg' => '存在子级角色，禁止删除'], _getEnCode());
        }
    }

    // 删除后
    public static function onAfterDelete($model)
    {
        $model->nodes()->delete();
    }

    public function groups(): belongsToMany
    {
        return $this->belongsToMany(SysGroup::class, SysGroupRole::class, 'group_id', 'role_id')
            ->wherePivot('group_id', 'in', AuthService::instance()->getUserGroups(true));
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(SysRoleNode::class, 'role_id', 'id')
            ->where('role_id', 'in', AuthService::instance()->getUserRoles(true));
    }

    public function loginNodes(): HasMany
    {
        return $this->hasMany(SysRoleNode::class, 'role_id', 'id');
    }

    public function setRoleIdAttr($value, $data): int
    {
        if (empty($value) && AuthService::instance()->isAdmin()) return 0;
        if (!in_array($value, AuthService::instance()->getUserRoles(true))) {
            _result(['code' => 403, 'msg' => '未拥有该角色'], _getEnCode());
        }
        if (isset($data['id'])) {
            if (in_array($value, AuthService::instance()->getRoleChildren((int)$data['id'], false))) {
                _result(['code' => 403, 'msg' => '不能选择自己的子角色'], _getEnCode());
            }
        }
        return (int)$value;
    }
}
<?php
declare(strict_types=1);

namespace app\admin\model;

use think\facade\Cache;
use think\model\relation\HasMany;
use cccms\Model;
use cccms\extend\ArrExtend;
use cccms\services\AuthService;

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
        if (count(AuthService::instance()->getRoleChildren((int)$model['id'], true)) > 1) {
            _result(['code' => 403, 'msg' => '存在子级角色，禁止删除'], _getEnCode());
        }
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(SysRoleNode::class, 'role_id', 'id');
    }

    public function setRoleIdAttr($value, $data): int
    {
        if (AuthService::instance()->isAdmin()) {
            return (int)$value;
        }
        if (!in_array($value, AuthService::instance()->getUserRoles(true))) {
            _result(['code' => 403, 'msg' => '未拥有该角色'], _getEnCode());
        }
        if (in_array($value, AuthService::instance()->getRoleChildren((int)$data['id'], true))) {
            _result(['code' => 403, 'msg' => '不能选择自己的子角色'], _getEnCode());
        }
        return (int)$value;
    }
}
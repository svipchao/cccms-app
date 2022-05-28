<?php
declare(strict_types=1);

namespace app\admin\model;

use cccms\Model;
use cccms\services\TypesService;
use think\facade\Cache;
use think\model\relation\HasOne;

class SysConfig extends Model
{
    public static function onBeforeWrite($model)
    {
        Cache::delete('SysConfigs');
    }

    public function type(): HasOne
    {
        return $this->hasOne(SysTypes::class, 'id', 'type_id')->bind([
            'type_name' => 'name'
        ]);
    }

    public function setTypeIdAttr($value): int
    {
        $types = TypesService::instance()->getTypes(0, 'id');
        if (!isset($types[$value]) && $types[$value]['type'] != 2) {
            _result(['code' => 403, 'msg' => '类型错误'], _getEnCode());
        }
        return (int)$value;
    }
}
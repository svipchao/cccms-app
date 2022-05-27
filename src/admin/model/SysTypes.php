<?php
declare(strict_types=1);

namespace app\admin\model;

use think\facade\Cache;
use cccms\Model;

class SysTypes extends Model
{
    protected $append = ['type_text'];

    public static function onBeforeWrite($model)
    {
        Cache::delete('SysTypes');
    }

    public function getTypeTextAttr($value, $data): string
    {
        return ['未知', '菜单', '配置', '路由', '附件'][$data['type']] ?? '未知';
    }
}
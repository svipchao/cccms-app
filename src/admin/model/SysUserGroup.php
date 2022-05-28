<?php
declare(strict_types=1);

namespace app\admin\model;

use cccms\services\AuthService;
use think\model\Pivot;

class SysUserGroup extends Pivot
{
    public function setGroupIdAttr($value)
    {
        if (in_array($value, AuthService::instance()->getUserGroups(true))) {
            return $value;
        } else {
            return false;
        }
    }
}
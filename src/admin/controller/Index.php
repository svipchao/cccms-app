<?php
declare(strict_types=1);

namespace app\admin\controller;

use cccms\Base;
use cccms\services\AuthService;

/**
 * 首页
 * @sort 999
 */
class Index extends Base
{
    /**
     * 首页
     * @auth false
     * @login false
     * @encode view
     * @methods GET
     */
    public function index(): string
    {
        $res = AuthService::instance()->setUserInfo(['id' => 1]);
        halt($res);
    }
}

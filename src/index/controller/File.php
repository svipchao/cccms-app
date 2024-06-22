<?php
declare(strict_types=1);

namespace app\index\controller;

use cccms\{Base, Storage};

/**
 * 附件
 * @sort 999
 */
class File extends Base
{
    /**
     * 附件
     * @auth false
     * @login false
     * @encode view
     * @methods GET
     */
    public function file(): void
    {
        Storage::instance()->query();
    }
}

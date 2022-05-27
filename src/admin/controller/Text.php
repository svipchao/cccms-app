<?php

declare(strict_types=1);

namespace app\admin\controller;

use cccms\Base;

/**
 * 首页
 * @sort 999
 */
class Text extends Base
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
    return '1232';
  }
}

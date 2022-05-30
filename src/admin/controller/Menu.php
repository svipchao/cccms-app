<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\SysMenu;
use cccms\Base;
use cccms\extend\ArrExtend;
use cccms\services\{AuthService, TypesService};

/**
 * 菜单管理
 * @sort 999
 */
class Menu extends Base
{
    public function init()
    {
        $this->model = SysMenu::mk();
    }

    /**
     * 添加菜单
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods POST
     */
    public function create()
    {
        $params = _validate('post', 'sys_menu|type_id,name,url|true');
        // 判断类别是否属于菜单
        if ($this->model->create($params)) {
            _result(['code' => 200, 'msg' => '添加成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '添加失败'], _getEnCode());
        }
    }

    /**
     * 删除菜单
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods DELETE
     */
    public function delete()
    {
        if ($this->model->_delete($this->request->delete('id/d', 0))) {
            _result(['code' => 200, 'msg' => '删除成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '删除失败'], _getEnCode());
        }
    }

    /**
     * 修改菜单
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods PUT
     */
    public function update()
    {
        $params = _validate('put', 'sys_menu|id|true');
        if ($this->model->update($params)) {
            _result(['code' => 200, 'msg' => '更新成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '更新失败'], _getEnCode());
        }
    }

    /**
     * 菜单列表
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods GET
     */
    public function index()
    {
        $data = $this->model->with('type')->withSearch(['type_id'], [
            'type_id' => $this->request->get('type_id/d', 0)
        ])->_list();
        _result([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'fields' => AuthService::instance()->fields('sys_menu'),
                'types' => TypesService::instance()->getTypes(1),
                'data' => ArrExtend::toTreeList($data, 'id', 'menu_id')
            ]
        ], _getEnCode());
    }
}
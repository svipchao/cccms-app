<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\SysRoute;
use cccms\Base;
use cccms\services\{AuthService, TypesService};

/**
 * 路由管理
 * @sort 999
 */
class Route extends Base
{
    public function init()
    {
        $this->model = SysRoute::mk();
    }

    /**
     * 添加路由
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods POST
     */
    public function create()
    {
        $params = _validate('post', 'sys_route|type_id,alias,url,name|true');
        if ($this->model->create($params)) {
            _result(['code' => 200, 'msg' => '添加成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '添加失败'], _getEnCode());
        }
    }

    /**
     * 删除路由
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
     * 修改路由
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods PUT
     */
    public function update()
    {
        $params = _validate('put', 'sys_route|id|true');
        if ($this->model->update($params)) {
            _result(['code' => 200, 'msg' => '更新成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '更新失败'], _getEnCode());
        }
    }

    /**
     * 路由列表
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods GET
     */
    public function index()
    {
        $data = $this->model->with('type')->withSearch(['type_id'], [
            'type_id' => $this->request->get('type_id/d', 0)
        ])->_page($this->request->get(['page' => 1, 'limit' => 10]));
        _result([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'fields' => AuthService::instance()->fields('sys_route'),
                'types' => TypesService::instance()->getTypes(3),
                'total' => $data['total'],
                'data' => $data['data']
            ]
        ], _getEnCode());
    }
}
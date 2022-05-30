<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\SysTypes;
use cccms\Base;
use cccms\services\AuthService;

/**
 * 类别管理
 * @sort 999
 */
class Types extends Base
{
    public function init()
    {
        $this->model = SysTypes::mk();
    }

    /**
     * 添加类别
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods POST
     */
    public function create()
    {
        $params = _validate('post', 'sys_types|type,name,alias,sort');
        $this->model->create($params);
        _result(['code' => 200, 'msg' => '添加成功'], _getEnCode());
    }

    /**
     * 删除类别
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
     * 修改类别
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods PUT
     */
    public function update()
    {
        $params = _validate('put', 'sys_types|id,type,name,alias,sort');
        if ($this->model->update($params)) {
            _result(['code' => 200, 'msg' => '更新成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '更新失败'], _getEnCode());
        }
    }

    /**
     * 类别列表
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods GET
     */
    public function index()
    {
        $params = $this->request->get([
            'limit' => 10,
            'page' => 1,
            'type' => 0
        ]);
        $data = $this->model->withSearch(['type'], [
            'type' => $params['type']
        ])->_page($params);
        _result([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'fields' => AuthService::instance()->fields('sys_types'),
                'total' => $data['total'],
                'data' => $data['data'],
                'type' => config('cccms.types.type'),
            ]
        ], _getEnCode());
    }
}
<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\SysData;
use cccms\Base;
use cccms\services\{AuthService, InitService};

/**
 * 数据权限
 * @sort 999
 */
class Data extends Base
{
    public function init()
    {
        $this->model = SysData::mk();
    }

    /**
     * 添加权限
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods POST
     */
    public function create()
    {
        $params = _validate('post', 'sys_data|role_id,table,field|true');
        if ($this->model->create($params)) {
            _result(['code' => 200, 'msg' => '添加成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '添加失败'], _getEnCode());
        }
    }

    /**
     * 删除权限
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods DELETE
     */
    public function delete()
    {
        $params = _validate('delete', 'sys_data|id,table,role_id,field');
        if ($this->model->_delete(['id' => $params['id']])) {
            _result(['code' => 200, 'msg' => '删除成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '删除失败'], _getEnCode());
        }
    }

    /**
     * 修改权限
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods PUT
     */
    public function update()
    {
        $params = _validate('put', 'sys_data|id,table,role_id,field|true');
        if ($this->model->update($params)) {
            _result(['code' => 200, 'msg' => '添加成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '添加失败'], _getEnCode());
        }
    }

    /**
     * 查看权限
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods GET
     */
    public function index()
    {
        $params = $this->app->request->get([
            'limit' => 15,
            'page' => 1,
            'role_id' => null,
            'table' => null
        ]);
        $tableInfo = InitService::instance()->getTables();
        $data = $this->model->with('role')->withSearch(['role_id', 'table'], [
            'role_id' => $params['role_id'],
            'table' => $params['table']
        ])->_page($params, false, function ($item) use ($tableInfo) {
            $tableInfo = $tableInfo[$item['table']];
            $item['table_name'] = $tableInfo['table_name'] ?? '未知';
            $item['field_name'] = explode('|', $tableInfo['fields'][$item['field']] ?? '|未知')[1];
            return $item;
        });
        _result([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'total' => $data['total'],
                'roles' => AuthService::instance()->getUserRoles(),
                'table' => $tableInfo,
                'data' => $data['data'],
            ]
        ]);
    }
}
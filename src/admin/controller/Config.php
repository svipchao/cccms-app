<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\SysConfig;
use cccms\Base;
use cccms\services\{AuthService, TypesService};

/**
 * 配置管理
 * @sort 999
 */
class Config extends Base
{
    public function init()
    {
        $this->model = SysConfig::mk();
    }

    /**
     * 添加配置
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods POST
     */
    public function create()
    {
        $this->model->create(_validate('post', 'sys_config|type_id,key,val|desc,false'));
        _result(['code' => 200, 'msg' => '添加成功'], _getEnCode());
    }

    /**
     * 删除配置
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods DELETE
     */
    public function delete()
    {
        $this->model->_delete($this->request->delete('id/d', 0));
        _result(['code' => 200, 'msg' => '删除成功'], _getEnCode());
    }

    /**
     * 修改配置
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods PUT
     */
    public function update()
    {
        $data = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'value' => $item['value'],
            ];
        }, $this->request->put());
        if ($this->model->saveAll($data)) {
            _result(['code' => 200, 'msg' => '更新成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '更新失败'], _getEnCode());
        }
    }

    /**
     * 配置列表
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods GET
     */
    public function index()
    {
        $data = $this->model->_withSearch('type_id', [
            'type_id' => $this->request->get('type_id/d', 0)
        ])->_list(null, function ($data) {
            $data = $data->toArray();
            return array_map(function ($item) {
                $item['configure'] = json_decode($item['configure'], true);
                if (empty($item['configure'])) {
                    return true;
                } else {
                    $item = array_merge($item['configure'], $item);
                    unset($item['configure']);
                }
                if ($item['type'] === 'input-number') {
                    $item['value'] = (int)$item['value'];
                }
                if ($item['type'] === 'multiple-select') {
                    $item['value'] = explode(',', strtoupper($item['value']));
                }
                if ($item['type'] === 'switch') {
                    $item['value'] = (int)$item['value'];
                }
                return $item;
            }, $data);
        });
        _result(['code' => 200, 'msg' => 'success', 'data' => [
            'fields' => AuthService::instance()->fields('sys_config'),
            'types' => TypesService::instance()->getTypes(2),
            'data' => $data
        ]], _getEnCode());
    }
}

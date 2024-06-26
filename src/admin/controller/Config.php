<?php
declare(strict_types=1);

namespace app\admin\controller;

use cccms\Base;
use cccms\model\SysConfig;
use cccms\services\{AuthService, ConfigService};

/**
 * 配置管理
 * @sort 994
 */
class Config extends Base
{
    public function init(): void
    {
        $this->model = SysConfig::mk();
    }

    /**
     * 添加配置
     * @auth true
     * @login true
     * @encode json
     * @methods POST
     */
    public function create(): void
    {
        // $this->model->create(_validate('post.sys_config', 'type_id,key,val|desc'));
        _result(['code' => 200, 'msg' => '添加成功'], _getEnCode());
    }

    /**
     * 删除配置
     * @auth true
     * @login true
     * @encode json
     * @methods DELETE
     */
    public function delete(): void
    {
        // $this->model->_delete($this->request->delete('id/d', 0));
        _result(['code' => 200, 'msg' => '删除成功'], _getEnCode());
    }

    /**
     * 修改配置
     * @auth true
     * @login true
     * @encode json
     * @methods PUT
     */
    public function update(): void
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
     * @encode json
     * @methods GET
     */
    public function index(): void
    {
        $data = $this->model->_withSearch('cate_name', [
            'cate_name' => $this->request->get('cate_name', 'site')
        ])->_list(null, function ($data) {
            $data = $data->toArray();
            return array_map(function ($item) {
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
            'cate' => ConfigService::instance()->getConfigCate(),
            'data' => $data
        ]], _getEnCode());
    }
}

<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\SysRole;
use cccms\Base;
use cccms\extend\ArrExtend;
use cccms\services\{NodeService, AuthService};

/**
 * 角色管理
 * @sort 999
 */
class Role extends Base
{
    public function init()
    {
        $this->model = SysRole::mk();
    }

    /**
     * 添加角色
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods POST
     */
    public function create()
    {
        $params = _validate('post', 'sys_role|role_name|role_id,role_desc,nodes');
        $this->model->create($params);
        _result(['code' => 200, 'msg' => '添加成功'], _getEnCode());
    }

    /**
     * 删除角色
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
            _result(['code' => 403, 'msg' => '删除失败，数据不存在'], _getEnCode());
        }
    }

    /**
     * 修改角色
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods PUT
     */
    public function update()
    {
        $params = _validate('put', 'sys_role|id|role_name,role_id,role_desc,status,nodes');
        $this->model->update($params);
        _result(['code' => 200, 'msg' => '更新成功'], _getEnCode());
    }

    /**
     * 角色列表
     * @auth false
     * @login false
     * @encode json|jsonp|xml
     * @methods GET
     */
    public function index()
    {
        $roles = $this->model->with(['groups', 'nodes'])->_list(null, function ($item) {
            $item['nodes'] = array_column($item['nodes'], 'node');
            $item['group_ids'] = array_column($item['groups'], 'id');
            return $item;
        });
        _result([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'fields' => AuthService::instance()->fields('sys_role'),
                'data' => ArrExtend::toTreeList($roles, 'id', 'role_id')
            ]
        ], _getEnCode());
    }

    /**
     * 节点授权
     * @auth  true
     * @login true
     * @encode json|jsonp|xml
     * @methods GET
     */
    public function auth()
    {
        $role_id = $this->request->get('role_id/d', 0);
        // 全部节点
        $allNodes = NodeService::instance()->getNodesInfo();
        if ($role_id == 0 && AuthService::instance()->isAdmin()) {
            $nodes = array_keys($allNodes);
        } else {
            $nodes = $this->model->_read($role_id)->nodes()->column('node');
        }
        // 框架节点
        $frameNodes = NodeService::instance()->getFrameNodes();
        $nodes = array_intersect_key($allNodes, array_flip($nodes));
        foreach ($nodes as &$val) {
            // 移除无用数据
            unset($val['parentTitle'], $val['encode'], $val['methods'], $val['appName'], $val['auth'], $val['login'], $val['sort']);
        }
        // 将节点框架合并进权限节点中
        $nodes = array_merge($nodes, NodeService::instance()->setFrameNodes($nodes, $frameNodes));
        _result([
            'code' => 200,
            'msg' => 'success',
            'data' => ArrExtend::toTreeArray($nodes, 'currentNode', 'parentNode')
        ], _getEnCode());
    }
}
<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\SysGroup;
use cccms\Base;
use cccms\extend\ArrExtend;
use cccms\services\AuthService;

/**
 * 组织管理
 * @sort 999
 */
class Group extends Base
{
    public function init()
    {
        $this->model = SysGroup::mk();
    }

    /**
     * 添加组织
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods POST
     */
    public function create()
    {
        $this->model->create(_validate('post', 'sys_group|group_name|role_ids,user_ids,true'));
        _result(['code' => 200, 'msg' => '添加成功'], _getEnCode());
    }

    /**
     * 删除组织
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods DELETE
     */
    public function delete()
    {
        $this->model->_delete($this->request->delete('id/d', 0),function($query){
            // 删除关联数据
            $query->roles()->detach($query->roles()->column('id'));
            $query->users()->detach($query->users()->column('id'));
            return $query;
        });
        _result(['code' => 200, 'msg' => '删除成功'], _getEnCode());
    }

    /**
     * 修改组织
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods PUT
     */
    public function update()
    {
        $this->model->update(_validate('put', 'sys_group|id|role_ids,user_ids,true'));
        _result(['code' => 200, 'msg' => '更新成功'], _getEnCode());
    }

    /**
     * 组织列表
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods GET
     */
    public function index()
    {
        $groups = $this->model->with('roles')->where([
            ['id', 'in', AuthService::instance()->getUserGroups(true)]
        ])->_list(null, function ($item) {
            $item['role_ids'] = array_column($item['roles'], 'id');
            return $item;
        });
        _result(['code' => 200, 'msg' => 'success', 'data' => [
            'fields' => AuthService::instance()->fields('sys_group'),
            'roles' => AuthService::instance()->getUserRoles(),
            'data' => ArrExtend::toTreeList($groups, 'id', 'group_id')
        ]], _getEnCode());
    }
}

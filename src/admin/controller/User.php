<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\SysUser;
use cccms\Base;
use cccms\extend\{ArrExtend, JwtExtend};
use cccms\services\{AuthService, MenuService};
use app\admin\model\SysAuth;

/**
 * 用户管理
 * @sort 999
 */
class User extends Base
{
    public function init()
    {
        $this->model = SysUser::mk();
    }

    /**
     * 添加用户
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods POST
     */
    public function create()
    {
        $this->model->create(_validate('post', 'sys_user|nickname,username,password|group_ids,true'));
        _result(['code' => 200, 'msg' => '添加成功'], _getEnCode());
    }

    /**
     * 删除用户
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
     * 更新用户
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods PUT
     */
    public function update()
    {
        $this->model->update(_validate('put', 'sys_user|id|group_ids,true', [
            'password|密码' => 'alphaNum|length:5,32',
            'token|Token' => 'alphaNum|length:32'
        ]));
        _result(['code' => 200, 'msg' => '更新成功'], _getEnCode());
    }

    /**
     * 用户列表
     * @auth true
     * @login true
     * @encode json|jsonp|xml
     * @methods GET
     */
    public function index()
    {
        $params = _validate('get', ['sys_user', '', [
            'page' => 1,
            'limit' => 10,
            'group_id' => null,
            'type' => null,
            'user' => null,
        ]]);
        $users = $this->model->with('groups')->append(['type_text'])->_withSearch('user,group_id,type', [
            'group_id' => $params['group_id'],
            'type' => $params['type'],
            'user' => $params['user'],
        ])->auth()->_page($params, false, function ($item) {
            $item['group_ids'] = array_column($item['groups'], 'id');
            return $item;
        });
        _result([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'fields' => AuthService::instance()->fields('sys_user'),
                'types' => config('cccms.user.types'),
                'groups' => ArrExtend::toTreeList(AuthService::instance()->getUserGroups(), 'id', 'group_id'),
                'total' => $users['total'] ?? 0,
                'data' => $users['data'] ?? []
            ]
        ], _getEnCode());
    }

    /**
     * 用户登陆
     * @auth  false
     * @login false
     * @encode json
     * @methods POST
     */
    public function login()
    {
        $accessToken = $this->app->request->header('accessToken', '');
        if (empty($accessToken)) {
            $params = $this->request->post(['username' => '', 'password' => '']);
            $userInfo = SysUser::mk()->field('id,nickname,username,avatar,token')->_read([
                ['username', '=', $params['username']],
                ['password', '=', md5($params['password'])],
                ['status', '=', 1]
            ]);
        } else {
            $userInfo = AuthService::instance()->getUserInfo();
        }
        $expTime = time() + config('session.expire');
        $accessToken = JwtExtend::getToken(array_merge($userInfo, ['logintime' => time(), 'exp' => $expTime]));
        $userInfo['nodes'] = SysAuth::mk()->getUserNodes($userInfo['id']);
        $userInfo['menus'] = MenuService::instance()->getTypesMenus($userInfo['nodes']);
        _result(['code' => 200, 'msg' => '登录成功', 'data' => array_merge($userInfo, [
            'accessToken' => $accessToken,
            'loginExpire' => $expTime
        ])], _getEnCode());
    }
}
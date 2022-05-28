<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\model\SysUser;
use cccms\Base;
use cccms\extend\{ArrExtend, JwtExtend};
use cccms\services\{AuthService, MenuService};

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
        $params = _validate('post', 'SysUser|nickname,username,password|groupIds');
        $this->model->create($params);
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
        if ($this->model->_delete($this->request->delete('id/d', 0))) {
            _result(['code' => 200, 'msg' => '删除成功'], _getEnCode());
        } else {
            _result(['code' => 403, 'msg' => '删除失败，数据不存在'], _getEnCode());
        }
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
        $params = _validate('put', 'SysUser|id|groupIds,nickname,username');
        $this->model->update($params);
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
        $params = $this->request->get([
            'group_id' => 0,
            'type' => null,
            'nickname' => '',
            'username' => '',
            'limit' => 10,
            'page' => 1
        ]);
        $where = [
            ['nickname', 'like', '%' . $params['nickname'] . '%'],
            ['username', 'like', '%' . $params['username'] . '%'],
        ];
        if (is_numeric($params['type'])) {
            $where[] = ['type', '=', $params['type']];
        }
        $users = $this->model->where($where)->with(['groups'])->_page($params);
        foreach ($users['data'] as &$user) {
            $user['groupIds'] = array_column($user['groups'], 'id');
        }
        _result([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'fields' => AuthService::instance()->fields('sys_user'),
                'types' => config('cccms.user.types'),
                'groups' => ArrExtend::toTreeList(AuthService::instance()->getUserGroups(), 'id', 'group_id'),
                'total' => $users['total'],
                'data' => $users['data']
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
            $userInfo = AuthService::instance()->setUserInfo([
                ['username', '=', $params['username']],
                ['password', '=', md5($params['password'])],
                ['status', '=', 1]
            ]);
        } else {
            $accessToken = JwtExtend::verifyToken($accessToken);
            if (!$accessToken) {
                _result(['code' => 401, 'msg' => 'Token已失效，请重新登陆'], _getEnCode());
            }
            $userInfo = AuthService::instance()->setUserInfo([
                ['id', '=', $accessToken['id']],
                ['token', '=', $accessToken['token']],
                ['status', '<>', 0]
            ]);
        }
        $userInfo['menus'] = MenuService::instance()->getTypesMenus($userInfo['nodes']);
        $expTime = time() + config('session.expire');
        $accessToken = JwtExtend::getToken([
            'id' => $userInfo['id'],
            'token' => $userInfo['token'],
            'logintime' => time(),
            'exp' => $expTime,
        ]);
        _result(['code' => 200, 'msg' => '登录成功', 'data' => array_merge($userInfo, [
            'accessToken' => $accessToken,
            'loginExpire' => $expTime
        ])], _getEnCode());
    }
}
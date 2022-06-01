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
        $this->model->create(_validate('post', [
            'sys_user',
            'nickname,username,password',
            'group_ids',
        ]));
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
        $this->model->update(_validate('put', [
            'sys_user',
            'id',
            'group_ids,nickname,username,password',
//            'group_ids,nickname,username,password,avatar,intro,status,type',
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
            'group_id' => null,
            'type' => null,
            'nickname' => '',
            'username' => '',
            'limit' => 10,
            'page' => 1
        ]]);
        $users = $this->model->with('groups')->withSearch(['nickname', 'username', 'group_id', 'type'], [
            'nickname' => $params['nickname'],
            'username' => $params['username'],
            'group_id' => $params['group_id'],
            'type' => $params['type'],
        ])->_page($params, false, function ($item) {
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
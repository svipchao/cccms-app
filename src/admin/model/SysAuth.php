<?php
declare(strict_types=1);

namespace app\admin\model;

use cccms\Model;
use cccms\services\NodeService;

class SysAuth extends Model
{
    protected $globalScope = [];

    protected function getAuths(): array
    {
        return $this->_list();
    }

    protected function isAdmin($user_id = null): bool
    {
        return ($user_id ?: _getAccessToken('id')) == 1;
    }

    protected function getUserAuths($user_id = null): array
    {
        $user_id = $user_id ?: _getAccessToken('id');
        if (empty($user_id)) return array_pad([], 3, []);
        $auths = $this->getAuths();
        $group_ids = $role_ids = [];
        foreach ($auths as $auth) {
            if ($auth['user_id'] == $user_id) {
                $group_ids[] = $auth['group_id'] ?: null;
                $role_ids[] = $auth['role_id'] ?: null;
            }
        }
        return [$auths, array_unique(array_filter($group_ids)), array_unique(array_filter($role_ids))];
    }

    // 用户拥有的组织ID集合不包含子级
    public function getUserGroups($user_id = null): array
    {
        if ($this->isAdmin($user_id)) return SysGroup::mk()->getAllGroups();
        [$auths, $group_ids, $role_ids] = $this->getUserAuths($user_id);
        foreach ($auths as $auth) {
            if (in_array($auth['role_id'], $role_ids)) {
                $group_ids[] = $auth['group_id'] ?: null;
            }
        }
        // 查找数据库 剔除状态禁用
        return SysGroup::mk()->where([
            ['id', 'in', array_values(array_unique(array_filter($group_ids)))],
            ['status', '=', 1],
        ])->_list();
    }

    // 用户拥有的角色ID集合
    public function getUserRoles($user_id = null): array
    {
        if ($this->isAdmin($user_id)) return SysRole::mk()->getAllRoles();
        [$auths, $group_ids, $role_ids] = $this->getUserAuths($user_id);
        foreach ($auths as $auth) {
            if (in_array($auth['group_id'], $group_ids)) {
                $role_ids[] = $auth['role_id'] ?: null;
            }
        }
        // 查找数据库 剔除状态禁用
        return SysRole::mk()->where([
            ['id', 'in', array_values(array_unique(array_filter($role_ids)))],
            ['status', '=', 1],
        ])->_list();
    }

    // 用户拥有的权限节点集合
    public function getUserNodes($user_id = null): array
    {
        if ($this->isAdmin($user_id)) return NodeService::instance()->getNodes();
        $nodes = SysRole::mk()->where('id', 'in', $this->getUserRoles($user_id))->cache()->column('nodes');
        return array_filter(array_keys(array_flip(explode(',', implode(',', $nodes)))));
    }
}

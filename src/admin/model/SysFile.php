<?php
declare(strict_types=1);

namespace app\admin\model;

use think\model\relation\{HasOne, belongsTo};
use cccms\Model;
use cccms\services\TypesService;

class SysFile extends Model
{
    protected $hidden = ['type', 'user'];

    protected $append = ['file_link'];

    public function type(): HasOne
    {
        return $this->hasOne(SysTypes::class, 'id', 'type_id')->bind([
            'type_name' => 'name',
            'type_alias' => 'alias'
        ]);
    }

    public function user(): hasOne
    {
        return $this->hasOne(SysUser::class, 'id', 'user_id')->bind([
            'nickname',
            'username'
        ]);
    }

    public function searchUserAttr($query, $value, $data)
    {
        $query->hasWhere('user', function ($query) use ($value) {
            $query->where('nickname|username', 'like', "%" . $value . "%");
        });
    }

    // 类别搜索器
    public function searchTypeIdAttr($query, $value, $data)
    {
        $types = TypesService::instance()->getTypes(4, 'id');
        if (empty($types)) {
            $value = 0;
        } elseif (!isset($types[$value])) {
            $value = array_shift($types)['id'] ?? 0;
        }
        $query->where('type_id', '=', $value);
    }

    public function setTypeIdAttr($value): int
    {
        $types = TypesService::instance()->getTypes(4, 'id');
        if (!isset($types[$value])) {
            _result(['code' => 403, 'msg' => '类型错误'], _getEnCode());
        }
        return (int)$value;
    }

    public function getFileSizeAttr($value): string
    {
        return _format_bytes($value);
    }

    public function getFileLinkAttr($value, $data): string
    {
        return request()->domain() . '/file/' . ($data['file_code'] ?? '404');
    }
}
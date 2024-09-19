<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

use app\api\model\Cards as CardsModel;

class Likes extends Model
{

    // 设置当前模型对应的完整数据表名称
    protected $table = 'good';

    //开启软删除
    // use SoftDelete;
    // protected $deleteTime = 'deleted_at';

    //自动时间戳
    // protected $autoWriteTimestamp = 'datetime';
    // protected $createTime = 'created_at';
    // protected $updateTime = 'updated_at';

    // 设置字段信息
    protected $schema = [
        'id' => 'int',
        'aid' => 'int',
        'pid' => 'int',
        'uid' => 'int',
        'ip' => 'string',
        'time' => 'string',
    ];

    // 默认排除字段
    // protected static $withoutField = [
    //     'deleted_at',
    //     'created_at',
    //     'updated_at'
    // ];

    public function card()
    {
        return $this->hasOne(CardsModel::class, 'id', 'pid');
    }
}
<?php

/**
 * 在数据列表中搜索
 * @access public
 * @param array $list 数据列表
 * @param mixed $condition 查询条件
 * 支持 array('name'=>$value) 或者 name=$value
 * @return array
 */
function list_search($list,$condition) {
    if($list){
        if(is_string($condition))
            parse_str($condition,$condition);
        // 返回的结果集合
        $resultSet = [];
        foreach ($list as $key=>$data){
            $find   =   false;
            foreach ($condition as $field=>$value){
                if(isset($data[$field])) {
                    if(0 === strpos($value,'/')) {
                        $find   =   preg_match($value,$data[$field]);
                    }elseif($data[$field]==$value){
                        $find = true;
                    }
                }
            }
            if($find)
                $resultSet[]     =   &$list[$key];
        }
        return $resultSet;
    }else{
        return [];
    }
}

function error($msg, $code = 1){
	return result(['code'=>$code, 'msg'=>$msg]);
}

function success($msg = '', $data = []){
    return result(['code'=>0, 'msg'=>$msg, 'data'=>$data]);
}

function result($data){
    exit(json_encode($data, JSON_UNESACPED_UNICODE));
}
$post = $_POST;

if(empty($post)){
    error('参数缺少');
}

// $chesses = $post['chesses']??'';
$chesses = [
	[
		'hero_info'  => [
			'heroId'=>36,
			'title'=>''
		],
		'equip_info' => [],
		'player'     => 'a',
	],
	[],
];
if($chesses){
	$chesses = json_decode($chesses, 1);
	if(empty($chesses)){
		error('英雄缺失！');
	}
	$team_a = list_search($chesses, ['player'=>'a']);
	$team_b = list_search($chesses, ['player'=>'b']);
	if(empty($team_a)){
		error('请选择阵容1里的英雄');
	}
	if(empty($team_b)){
		error('请选择阵容2里的英雄');
	}
}else{
	error('英雄缺失！');
}
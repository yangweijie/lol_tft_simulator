<?php
namespace app\model;

use think\model\Collection;

class Hero{

	// 初始化
	public static function init($data){
		return new Collection($data);
	}

	public static function team_a($data){
		if(is_array($data)){
			$data = self::init($data);
		}
		return $data->where('player', '=', 'a');
	}

	public static function team_b($data){
		if(is_array($data)){
			$data = self::init($data);
		}
		return $data->where('player', '=', 'b');
	}

	public function on_stage(){
		$this->hero['inited'] = 1;
	}

	public function is_dead(){
		return $this->hero['health'] <= 0 || in_array($this->hero['pos'], ['9999', '-9999']);
	}

	public function move($hero){

	}

	public function judge($second){

	}


}
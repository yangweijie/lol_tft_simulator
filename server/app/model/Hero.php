<?php
namespace app\model;

use think\model\Collection;

class Hero{

	public $basic = [
		'life' => 0,
		// 生命
		'lifeData'=>[],
		// 护甲 
		'armor'=>0,
		// 魔抗
		'spellBlock' => 0,
		// 物攻
		'attack'=>0,
		'attackData'=>[],
		// 攻速
		'attackSpeed' => 25,
		// 暴击率
		'crit'=>25,
		// 攻击距离
		'attackRange'=>2,
		// 初始法力值
		'startMagic'=>0,
		// 法力值
		'magic' => 65,
		// 法强
		'attackMag'=>1.8,
		// 回复法力百分比
		'lifeMag' => 1.8,
		// 种族
		'races'=>[],
		// 职业
		'jobs' => [],
		'pos'  => 1,
	];

	public $inited = 0;

	public $begin = [];

	public $status = [];

	public $is_dead = 0;

	public $teammates = [];

	public $enemys = [];

	public $enemy = [];

	public $counted = [];

	// 初始化
	public static function init($data){
		return new Collection($data);
	}

	public function on_stage($team){
		// 计算种族
		// 计算 职业
		// 计算武器加成
		$this->inited = 1;
	}

	public function is_dead(){
		return $this->hero['health'] <= 0 || in_array($this->hero['pos'], ['9999', '-9999']);
	}

	public function move(){
		return 1;
	}

	public function judge($time){
		if($this->is_dead()){
			$this->is_dead = 1;
			return ;
		}
		// 寻找敌人
		if(empty($this->enemy)){
			$this->enemy = self::init($enemys)->where('is_dead'=>0)->first();
		}
		// 初始化
		if(!$this->inited){
			$this->on_stage($team);
		}
		if($this->status){
			$this->count_status($time);
			if($this->status){
				$status_names = array_column($this->status, 'name');
				if(in_array('', ) || ){
					return ;
				}
			}
		}
		// 判断技能施法
		$this->releaseSkill($time);
		$this->attack($time);
	}


}
<?php
namespace app\controller;

use app\model\Classes;
use app\model\ExamQuestions;
use app\model\Evaluation;
use app\model\Lessons;
use app\model\GeneralQuestions;
use app\model\GeneralReply;
use app\model\Student;
use app\model\Files;
use app\model\StudentExamAnswer;
use app\model\Teacher as TeacherModel;
use app\model\Transcript;
use \Firebase\JWT\JWT;

class Teacher extends Api
{
	public $un_jwt_check = [
		'reg',
		'login',
	];

	/**
	 * 登录
	 * @param  string $no  工号
	 * @param  string $pwd 密码
	 * @return json
	 */
	public function login($no, $pwd){
		$exist = TeacherModel::where('no', $no)->find();
		if(!$exist){
			$this->error('工号不存在');
		}
		if($exist['pwd'] !== md5($pwd)){
			$this->error('密码错误');
		}
		$nowtime = time();
        $token = [
            'iss' => 'http://' . DOMAIN, //签发者
            'aud' => 'http://' . DOMAIN, //jwt所面向的用户
            'iat' => $nowtime, //签发时间
            'nbf' => $nowtime + 0, //在什么时间之后该jwt才可用
            'exp' => $nowtime + 3600*24*3600, //过期时间-60min
            'data' => [
                'uid'  => $exist['id'],
                'name' => $exist['name'],
                'type' => 'teacher',
            ],
        ];
        $jwt = JWT::encode($token, config('jwt.key'));
        $this->success(SUCCESS_MSG, '', [
            'jwt'                  => $jwt,
            'info'                 => $exist,
            'exp'                  => $token['exp'],
        ]);
	}

	/**
	 * 注册
	 * @param  string $no   工号
	 * @param  string $name 姓名
	 * @param  string $pwd  密码
	 * @param  int $sex  性别
	 * @return json
	 */
	public function tearcher_add($tid, $no, $name, $pwd, $sex){
		$exist = TeacherModel::getByNo($no);
		if($exist){
			$this->error('工号重复');
		}
		$data = [
			'no'   => $no,
			'name' => $name,
			'pwd'  => md5($pwd),
			'sex'  => $sex,
		];
		$info = TeacherModel::create($data);
		$this->success(SUCCESS_MSG, null, ['info'=>$info->toArray()]);
	}

	// 编辑老师
	public function teacher_edit($tid, $id, $no, $name, $pwd, $sex){
		$info = TeacherModel::find($id);
		if(!$info){
			$this->error('教师记录缺失！');
		}
		if($exist = TeacherModel::where('no', $no)->where('id', '<>', $id)->find()){
			$this->error('工号重复');
		}
		$info->no   = $no;
		$info->name = $name;
		if($pwd){
			$info->pwd  = md5($pwd);
		}
		$info->sex  = $sex;
		$info->save();
		$this->success(SUCCESS_MSG, null, ['info'=>$info->toArray()]);
	}

	// 教师列表
	public function teachers($tid, $page, $list_rows = 10){
		$row_list = TeacherModel::paginate($list_rows)
	        ->each(function ($item, $key) {
	            $item['score']   = Evaluation::teacher_trend($item['id']);
	            return $item;
	        });
        if (is_object($row_list) && !$row_list->isEmpty()) {
            $pageinfo = [
                'total'     => $row_list->total(),
                'page'      => $row_list->currentPage(),
                'list_rows' => $row_list->listRows(),
            ];
        } else {
            $pageinfo = ['total' => 0, 'page' => $page, 'list_rows' => $row_list->listRows()];
        }
        $list = $row_list->toArray();
        $this->success(SUCCESS_MSG, '', ['list' => $list['data'], 'pageinfo' => $pageinfo]);
	}

	/**
	 * 班级
	 * @return json
	 */
	public function class($tid, $page, $list_rows = 10){
		$row_list = Classes::order('id ASC')->paginate($list_rows)
	        ->each(function ($item, $key) {
	            $item['student_num']   = $item['student_num'];
	            $item['total_score']   = $item['total_score'];
	            $item['average_score'] = $item['average_score'];
	            return $item;
	        });
        if (is_object($row_list) && !$row_list->isEmpty()) {
            $pageinfo = [
                'total'     => $row_list->total(),
                'page'      => $row_list->currentPage(),
                'list_rows' => $row_list->listRows(),
            ];
        } else {
            $pageinfo = ['total' => 0, 'page' => $page, 'list_rows' => $row_list->listRows()];
        }
        $list = $row_list->toArray();
        $this->success(SUCCESS_MSG, '', ['list' => $list['data'], 'pageinfo' => $pageinfo]);
	}

	/**
	 * 添加班级
	 * @param  string $name 名称
	 * @return json
	 */
	public function class_add($tid, $name){
		$exist = Classes::getByName($name);
		if($exist){
			$this->error('名称重复');
		}
		$info = Classes::create(['name'=>$name, 'tid'=>$tid]);
		$this->success(SUCCESS_MSG, null, ['info'=>$info->toArray()]);
	}

	/**
	 * 班级更改名称
	 * @param  int $id   班级id
	 * @param  string $name 新名称
	 * @return json
	 */
	public function class_edit($tid, $id, $name){
		$info = Classes::find($id);
		if(!$info){
			$this->error('班级不存在');
		}
		$exist = Classes::where('name', $name)->where('id', '<>', $id)->find();
		if($exist){
			$this->error('名称重复');
		}
		$info->name = $name;
		$info->save();
		$this->success(SUCCESS_MSG, null, ['info'=>$info->toArray()]);
	}

	/**
	 * 班级得分趋势图
	 * @param  int $cid 班级id
	 * @return json
	 */
	public function class_score_trend($tid, $cid){
		// 平均分
		$tab1 = Transcript::average_score_class($cid);

		// 平均准确率
		$tab2 = Transcript::average_accuracy_class($cid);

		// 平均自评
		$tab3 = Evaluation::average_evaluation_class($cid);
		$this->success(SUCCESS_MSG, null, ['statics'=>[$tab1, $tab2, $tab3]]);
	}

	/**
	 * 班级学生列表
	 * @param  int $cid 班级id
	 * @return json
	 */
	public function class_students($tid, $cid){
		$list = Student::where('cid', $cid)->append(['average_score', 'average_accuracy_class', 'average_evaluation'])->select();
		$this->success(SUCCESS_MSG, null, ['info'=>$list->toArray()]);
	}

	// 删除班级
	public function class_delete($tid, $cid){
		Classes::where('tid', $tid)->where('id', $cid)->delete();
		$this->success(SUCCESS_MSG);
	}

	/**
	 * 学生
	 * @param  int $tid 老师id
	 * @return string
	 */
	public function students($tid, $no = '', $name = '', $cid = 0, $page = 1, $list_rows = 10){
		$map  = [];
		if($no){
			$map[] = ['no', 'like', "%{$no}%"];
		}
		if($name){
			$map[] = ['name', 'like', "%{$name}%"];
		}
		if($cid){
			$map[] = ['cid', '=', $cid];
		}
		$row_list = Student::where($map)->paginate($list_rows)
	        ->each(function ($item, $key) {
	            $item['total_score']      = $item['total_score'];
	            $item['average_accuracye'] = $item['average_accuracye'];
	            return $item;
	        });
        if (is_object($row_list) && !$row_list->isEmpty()) {
            $pageinfo = [
                'total'     => $row_list->total(),
                'page'      => $row_list->currentPage(),
                'list_rows' => $row_list->listRows(),
            ];
        } else {
            $pageinfo = ['total' => 0, 'page' => $page, 'list_rows' => $row_list->listRows()];
        }
        $list = $row_list->toArray();
        $this->success(SUCCESS_MSG, '', ['list' => $list['data'], 'pageinfo' => $pageinfo]);
	}

	// 添加学生
	public function student_add($tid, $no, $name, $pwd, $cid, $sex){
		if($exist = Student::where('cid', $cid)->where('no', $no)->find()){
			$this->error('学号重复');
		}
		$info = Student::create([
			'no'   => $no,
			'name' => $name,
			'pwd'  => md5($pwd),
			'sex'  => $sex,
			'cid'  => $cid,
		]);
		$this->success(SUCCESS_MSG, null, ['info'=>$info->toArray()]);
	}

	// 编辑学生
	public function student_edit($tid, $id, $no, $name, $pwd, $cid, $sex){
		$info = Student::where('cid', $cid)->where('id', $id)->find();
		if(!$info){
			$this->error('学生记录缺失！');
		}
		if($exist = Student::where('cid', $cid)->where('no', $no)->where('id', '<>', $id)->find()){
			$this->error('学号重复');
		}
		$info->no   = $no;
		$info->name = $name;
		if($pwd){
			$info->pwd  = md5($pwd);
		}
		$info->sex  = $sex;
		$info->cid  = $cid;
		$info->save();
		$this->success(SUCCESS_MSG, null, ['info'=>$info->toArray()]);
	}

	// 删除学生
	public function student_delete($tid, $sid){
		Student::where('cid', 'in', Classes::where('tid', $tid)->column('id'))->where('id', $sid)->delete();
		$this->success(SUCCESS_MSG);
	}

	// 学生得分趋势图
	public function student_trend($tid, $sid){
		$tab1 = Student::accuracye_trend($sid);
		$tab2 = Evaluation::student_trend($sid);
		$this->success(SUCCESS_MSG, null, ['statics'=>['trend'=>$tab1, 'evaluation'=>$tab2]]);
	}

	// 评分趋势
	public function evaluation($tid){
		$trend       = Evaluation::teacher_trend($tid);
		$evaluations = Evaluation::with('lessons')
			->where('type', 1)
			->where('tid', $tid)
			->where('remark', '<>', '')
			->order('lid DESC')
			->select();
		$this->success(SUCCESS_MSG, null, ['trend'=>$trend, 'evaluations'=>$evaluations]);
	}

	// 上传
	public function upload($tid){
		return $this->up();
	}

	// 我的课堂
	public function lessons($tid, $page, $list_rows = 10){
		$row_list = Lessons::where('tid', $tid)->paginate($list_rows)
        ->each(function ($item, $key) {
            $item['question_num']  = $item['question_num'];
            $item['class_num']     = $item['class_num'];
            $item['average_score'] = $item['average_score'];
            return $item;
        });
		if (is_object($row_list) && !$row_list->isEmpty()) {
            $pageinfo = [
                'total'     => $row_list->total(),
                'page'      => $row_list->currentPage(),
                'list_rows' => $row_list->listRows(),
            ];
        } else {
            $pageinfo = ['total' => 0, 'page' => $page, 'list_rows' => $row_list->listRows()];
        }
        $list = $row_list->toArray();
        $this->success(SUCCESS_MSG, '', ['list' => $list['data'], 'pageinfo' => $pageinfo]);
	}

	// 交卷情况
	public function lesson_transcript($tid, $lid, $cid = 0, $name = '', $page, $list_rows = 10){
		$map = [];
		$map[] = ['lid', '=', $lid];
        if ($cid) {
        	$map[] = ['cid', '=', $cid];
        }
        if ($name) {
        	$sids = Student::whereLike('name', "%{$name}%")->column('id')?:[];
        	$map[] = ['sid', 'in', $sids];
        }
        $row_list = Transcript::with(['student','classes'])->where($map)->order('id DESC')->paginate($list_rows)
        ->each(function ($item, $key) {
            $item['student_no']   = $item['student']['no'];
            $item['student_name'] = $item['student']['name'];
            $item['class_name']   = $item['classes']['name'];
            return $item;
        });
        if (is_object($row_list) && !$row_list->isEmpty()) {
            $pageinfo = [
                'total'     => $row_list->total(),
                'page'      => $row_list->currentPage(),
                'list_rows' => $row_list->listRows(),
            ];
        } else {
            $pageinfo = ['total' => 0, 'page' => $page, 'list_rows' => $row_list->listRows()];
        }
        $list = $row_list->toArray();
        $this->success(SUCCESS_MSG, '', ['list' => $list['data'], 'pageinfo' => $pageinfo]);
	}

	// 作业详情
	public function transcript_detail($tid, $id){
		$ret = Transcript::detail($id);
		$this->success(SUCCESS_MSG, '', [
			'lesson'  => $ret['lesson'],
			'answers' => $ret['answers'],
			'info'    => $ret['info']
		]);
	}

	// 课堂得分
	public function lesson_score($tid, $lid){
		$chart1     = Lessons::transcript_score_area($lid);
		$chart2     = Evaluation::student_average_evaluation($lid);
		$class_info = Lessons::class_info($lid);

		$this->success(SUCCESS_MSG, '', ['charts' => ['transcript'=>$chart1, 'evaluation'=>$chart2], 'class_info'=>$class_info]);
	}

	/**
	 * 创建课程
	 * @param  int $tid            老师id
	 * @param  string $title          主题
	 * @param  string $content        简介
	 * @param  int $is_last        是否最后一节课
	 * @param  string $files          文件id 逗号分割
	 * @param  json $exam_questions 序列化数组
	 * @return json
	 */
	public function lesson_add($tid, $title, $content, $is_last, $files, $nodes, $exam_questions = '', $answers = ''){
		$info = Lessons::create([
			'tid'     => $tid,
			'title'   => $title,
			'content' => $content,
			'is_last' => $is_last,
			'files'   => $files,
			'nodes'   => json_decode($nodes, 1)?:[],
			'answers' => json_decode($answers, 1)?:[],
		]);
		$lid      = $info->id;
		$question = json_decode($exam_questions, 1);
		if($question){
			foreach ($question as $qa) {
				ExamQuestions::create([
					'tid'        => $tid,
					'lid'        => $lid,
					'type'       => $qa['type'],
					'title'      => $qa['title'],
					'node_id'    => $qa['node_id'],
					'is_multi'   => $qa['is_multi'],
					'option'     => $qa['option'],
					'score'      => array_sum( array_column($qa['option'], 'score')),
					'limit_time' => $qa['limit_time'],
					'files'      => $qa['files'],
				]);
			}
		}
		$this->success(SUCCESS_MSG, null, ['id'=>$lid]);
	}

	/**
	 * 创建课程
	 * @param  int $tid            老师id
	 * @param  int $id 课堂id
	 * @param  string $title          主题
	 * @param  string $content        简介
	 * @param  int $is_last        是否最后一节课
	 * @param  string $files          文件id 逗号分割
	 * @param  json $exam_questions 序列化数组
	 * @return json
	 */
	public function lesson_edit($tid, $id, $title, $content, $is_last, $files, $nodes, $exam_questions = '', $answers = ''){
		$info = Lessons::find($id);
		$lid  = $id;
		Lessons::where('id', $id)->update([
			'title'   => $title,
			'content' => $content,
			'is_last' => $is_last,
			'files'   => $files,
			'nodes'   => $nodes?:'[]',
			'answers' => $answers?:'[]',
		]);
		$question = json_decode($exam_questions, 1);
		if($question){
			$now = datetime();
			foreach ($question as $qa) {
				if($qa['id']){
					unset($qa['no']);
					$qa['create_time'] = $now;
					$qa['score'] = array_sum( array_column($qa['option'], 'score'));
					ExamQuestions::where('id', $qa['id'])->update(
						$qa
					);
				}else{
					ExamQuestions::create([
						'tid'        => $tid,
						'lid'        => $lid,
						'type'       => $qa['type'],
						'title'      => $qa['title'],
						'node_id'    => $qa['node_id'],
						'is_multi'   => $qa['is_multi'],
						'option'     => $qa['option'],
						'score'      => array_sum( array_column($qa['option'], 'score')),
						'limit_time' => $qa['limit_time'],
						'files'      => $qa['files'],
					]);
				}
			}
		}
		$this->success(SUCCESS_MSG, null, ['id'=>$lid]);
	}

	// 讲解考卷
	public function lesson_view($tid, $lid){
		$lesson = Lessons::find($lid)->append(['file_list']);
		if(!$lesson){
			$this->error('课堂缺失');
		}
		if($lesson['answers']){
			$answers_arr = [];
			foreach ($lesson['answers'] as $key=>$value) {
				$files = Files::where('id', 'in', $value['files'])->select()?:[];
				// $lesson['answers'][$key]['file_list'] = $file_list;
				$answers_arr[] = array_merge($value, ['files'=>$files]);
			}
			$lesson['answers'] = $answers_arr;
		}
		$question = ExamQuestions::where('lid', $lid)->order('id ASC')->select()->append(['no', 'file_list', 'next_id', 'before_id']);
		if($question){
			$this->success(SUCCESS_MSG, null, ['lesson'=>$lesson, 'question'=>$question]);
		}else{
			$this->error('题目不存在');
		}
	}

	// 答题情况
	public function lesson_answer($tid, $lid, $cid){
		$handed_class = Transcript::where('lid', $lid)->column('cid')?:[];
		$classes      = Classes::where('id', 'in', $handed_class)->field('id,name')->select();
		$map          = ['lid'=>$lid];
		if($cid){
			$map['cid'] = $cid;
		}
		$static = [
			'num'               => Transcript::where($map)->count(),
			'class_student_num' => Student::where('cid', $cid)->count(),
		];
		$static['average_score'] = $static['num']? round(Transcript::where($map)->sum('total_score') / $static['num'], 2): 0 ;
		$exam_questions = ExamQuestions::where('lid', $lid)->order('id ASC')->select();
		if($exam_questions){
			foreach ($exam_questions as $key=>&$qa) {
				$qa['index']          = $key+1;
				// $qa['accuracy']       = $qa['type'] == 4? 0: ($qa['answered']? round($qa['answered_right'] / $qa['answered'], 2) * 100: 0 );
				// $qa['jumped_num']     = ExamQuestions::jumped_num($tid, $qa['id'], $cid);
			}
		}
		$this->success(SUCCESS_MSG, null, [
			'classes'        => $classes,
			'static'         => $static,
			'exam_questions' => $exam_questions
		]);
	}

	// 获取学生答题情况
	public function lesson_student_answer($tid, $sid, $lid){
		$questions = ExamQuestions::where('lid', $lid)->order('id ASC')->select()->append(['file_list', 'no']);
		if($questions){
			foreach ($questions as $key=>&$qa) {
				$qa['origin_answer'] = $qa['answer'];
				$qa['answer']        = ExamQuestions::student_answer($sid, $lid, $qa['id'])?:$this->empty_obj;
			}
		}else{
			$this->error('课堂作业未准备好');
		}
		$this->success(SUCCESS_MSG, '', [
			'question' => $questions,
		]);
	}

	// 答题详情
	public function lesson_answer_detail($tid, $qid, $cid = 0){
		$map = ['tid'=>$tid, 'qid'=>$qid];
		if($cid){
			$map['cid'] = $cid;
		}
		$answers = StudentExamAnswer::with('student')->where($map)->order('id ASC')->select();
		$this->success(SUCCESS_MSG, null, ['answers'=>$answers]);
	}

	// 设置主观题得分
	public function set_answer_score($tid, $answer_id, $score){
		$info = StudentExamAnswer::find($answer_id);
		if($info['type'] !== 4){
			$this->error('只能设置主观题分数');
		}
		if($info['right'] == -1){
			$info->score = $score;
			$info->right = $score >0? 1:0;
			$info->save();
			$this->success(SUCCESS_MSG);
		}else{
			$this->error('已经设置过分数');
		}
	}

	// 尚未答题学生
	public function un_answer_question_students($tid, $lid, $cid = 0){
		if(!$cid){
			$student_ids = Student::column('id');
		}else{
			$student_ids = Student::where('cid', $cid)->column('id')?:[];
		}
		$map                   = $cid? ['lid'=>$lid, 'cid'=>$cid] : ['lid'=>$lid];
		$closed_student_ids    = Transcript::where($map)->column('sid')?:[];
		$un_answer_student_ids = array_diff($student_ids, $closed_student_ids);
		$students              = $un_answer_student_ids? Student::where('id', 'in', $un_answer_student_ids)->select():[];
		$this->success(SUCCESS_MSG, '', ['list'=>$students]);
	}

	// 答疑解惑
	public function general_questions($tid, $type = '', $keyword = '', $page = 1, $list_rows = 10){
		$map = [['tid', '=', $tid]];
		if($keyword){
			if(stripos($keyword, '班') !== false){
				$cid = Classes::where('name', 'like', "%{$keyword}%")->column('id')?:[];
				$map[] = ['cid', 'in', $cid];
			}else if(is_numeric($keyword)){
				$sids = Student::where('no', 'like', "%{$keyword}%")->column('id')?:[];
				$map[] = ['sid','in', $sids];
			}else{
				$sids = Student::where('name', 'like', "%{$keyword}%")->column('id')?:[];
				$map[] = ['sid','in', $sids];
			}
		}
		if($type){
			if($type == '未答复'){
				$replyed_ids = GeneralReply::column('qid')?:[];
				$map[]   = ['id', 'not in', $replyed_ids];
			}else{
				$replyed_ids = GeneralReply::column('qid')?:[];
				$map[]   = ['id', 'in', $replyed_ids];
			}
		}
		$row_list = GeneralQuestions::with('class')->where($map)
		->paginate($list_rows)
        ->each(function ($item, $key) {
        	$student = $item['student'];
            $item['student_name']        = $student['name'];
            $item['student_total_score'] = $student['total_score'];
            $item['reply_id']            = $item['reply_id'];
            return $item;
        });
        if (is_object($row_list) && !$row_list->isEmpty()) {
            $pageinfo = [
                'total'     => $row_list->total(),
                'page'      => $row_list->currentPage(),
                'list_rows' => $row_list->listRows(),
            ];
        } else {
            $pageinfo = ['total' => 0, 'page' => $page, 'list_rows' => $row_list->listRows()];
        }
        $list = $row_list->toArray();
        $this->success(SUCCESS_MSG, '', ['list' => $list['data'], 'pageinfo' => $pageinfo]);
	}

	// 答疑解惑 详情
	public function general_questions_detail($tid, $qid){
		$info = GeneralQuestions::find($qid);
		if($info){
			$info['file_list'] = $info['file_list'];
			$info['reply']     = GeneralReply::where('qid', $qid)->append(['file_list'])->find()?:$this->empty_obj;

			$this->success(SUCCESS_MSG, '', ['info'=>$info]);
		}else{
			$this->error('问题缺失');
		}
	}

	// 答复 答疑
	public function general_questions_reply($tid, $qid, $content, $files = ''){
		$info = GeneralQuestions::find($qid);
		if($info){
			$reply = GeneralReply::where('qid', $qid)->find();
			if($reply){
				$this->error('已经答复过');
			}
			$info = GeneralReply::create([
				'qid'     => $qid,
				'content' => $content,
				'files'   => $files,
			]);
			$this->success(SUCCESS_MSG, '', ['info'=>$info]);
		}else{
			$this->error('问题缺失');
		}
	}

	// 复制课堂
	public function copy_lesson_and_questions($tid, $lid, $new_tid){
		$lesson = Lessons::find($lid);
		if(!$lesson){
			$this->error('课堂缺失');
		}
		$info = Lessons::create([
			'tid'     => $new_tid,
			'title'   => $lesson['title'],
			'content' => $lesson['content'],
			'is_last' => $lesson['is_last'],
			'files'   => $lesson['files'],
			'nodes'   => $lesson['nodes'],
			'answers' => $lesson['answers'],
		]);
		$new_lid       = $info->id;
		$questions = ExamQuestions::where('lid', $lid)->withoutField(['id', 'create_time'])->select();
		if($questions){
			foreach ($questions as $qa) {
				$qa['lid'] = $new_lid;
				ExamQuestions::create($qa->toArray());
 			}
		}
		$this->success(SUCCESS_MSG, null, ['id'=>$new_lid]);
	}

}
<?php
namespace app\controller;

use app\model\Classes;
use app\model\ExamQuestions;
use app\model\Evaluation;
use app\model\Lessons;
use app\model\StudentExamAnswer;
use app\model\GeneralQuestions;
use app\model\GeneralReply;
use app\model\Teacher;
use app\model\Transcript;
use app\model\Files;
use app\model\Student as StudentModel;
use think\facade\Db;
use \Firebase\JWT\JWT;


class Student extends Api
{
	public $un_jwt_check = [
		'reg',
		'login',
		'class',
	];

	// 班级列表
	public function class(){
		$list = Classes::order('name ASC')->select();
		$this->success(SUCCESS_MSG, null, ['list'=>$list->toArray()]);
	}

	// 老师列表
	public function teacher($sid){
		$list = Teacher::field(['id','name', 'no'])->order('id ASC')->select();
		$this->success(SUCCESS_MSG, null, ['list'=>$list->toArray()]);
	}

	/**
	 * 注册
	 * @param  string $no   工号
	 * @param  string $name 姓名
	 * @param  string $pwd  密码
	 * @param  int $sex  性别
	 * @return json
	 */
	public function reg($no, $name, $pwd, $cid, $sex){
		$exist = StudentModel::getByNo($no);
		if($exist){
			$this->error('学号重复');
		}
		$data = [
			'no'   => $no,
			'name' => $name,
			'pwd'  => md5($pwd),
			'sex'  => $sex,
			'cid'  => $cid,
		];
		$info = StudentModel::create($data);
		$this->success(SUCCESS_MSG, null, ['info'=>$info->toArray()]);
	}

	/**
	 * 登录
	 * @param  string $no  学号
	 * @param  string $pwd 密码
	 * @return json
	 */
	public function login($no, $pwd){
		$exist = StudentModel::where('no', $no)->find();
		if(!$exist){
			$this->error('学号不存在');
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
			'exp' => $nowtime + 3600*24*365, //过期时间-60min
			'data' => [
				'uid'  => $exist['id'],
				'name' => $exist['name'],
				'type' => 'student',
			],
		];
		$jwt = JWT::encode($token, config('jwt.key'));
		$this->success(SUCCESS_MSG, '', [
			'jwt'                  => $jwt,
			'info'                 => $exist,
			'exp'                  => $token['exp'],
		]);
	}

	// 上传
	public function upload($sid){
		return $this->up();
	}

	// 用户中心
	public function userinfo($sid, $period = 0){
		$scores      = Transcript::where('sid', $sid)->limit($period)->order('id DESC')->select()->append(['lessons.title']);
		$evaluations = Evaluation::student_trend($sid);
		$userinfo    = StudentModel::find($sid);
		$this->success(SUCCESS_MSG, '', [
			'info'        => $userinfo,
			'scores'      => $scores,
			'evaluations' => $evaluations,
		]);
	}

	// 更新个人信息
	public function userinfo_edit($sid, $name, $pwd, $cid, $sex){
		$info = StudentModel::find($sid);
		if(!$info){
			$this->error('学生不存在');
		}
		$data = [
			'name' => $name,
			'pwd'  => md5($pwd),
			'sex'  => $sex,
			'cid'  => $cid,
		];
		if(empty($pwd)){
			unset($data['pwd']);
		}
		StudentModel::where('id', $sid)->update($data);
		$this->success(SUCCESS_MSG);
	}

	// 进入课堂
	public function enter_lesson($sid, $lid){
		$lesson     = Lessons::find($lid);
		if($lesson){
			$lesson->append(['file_list']);
		}
		$transcript = Transcript::where('sid', $sid)->where('lid', $lid)->find()?:$this->empty_obj;
		if($lesson){
			if($lesson['answers']){
				$answers_arr = [];
				foreach ($lesson['answers'] as $key=>$value) {
					$files = Files::where('id', 'in', $value['files'])->select()?:[];
					// $lesson['answers'][$key]['file_list'] = $file_list;
					$answers_arr[] = array_merge($value, ['files'=>$files]);
				}
				$lesson['answers'] = $answers_arr;
			}
			$this->success(SUCCESS_MSG, '', [
				'info'        => $lesson,
				'transcript'  => $transcript,
				'eva_teacher' => Evaluation::where('type', 1)->where('sid', $sid)->where('lid', $lid)->find()?:$this->empty_obj,
				'eva_self'    =>Evaluation::where('type', 0)->where('sid', $sid)->where('lid', $lid)->find()?:$this->empty_obj
			]);
		}
		$this->error('课堂不存在');
	}

	// 获取考题
	public function exam_questions($sid, $lid){
		$questions = ExamQuestions::where('lid', $lid)->order('id ASC')->select()->append(['file_list', 'no']);
		if($questions){
			foreach ($questions as $key=>&$qa) {
				// $qa['origin_answer'] = $qa['answer'];
				$qa['answer']        = ExamQuestions::student_answer($sid, $lid, $qa['id'])?:$this->empty_obj;
			}
		}else{
			$this->error('课堂作业未准备好');
		}
		$this->success(SUCCESS_MSG, '', [
			'question' => $questions,
		]);
	}

	// 提交答案
	public function submit_answers($sid, $lid, $cid, $answers, $teacher_evaluation, $self_evaluation){
		$lesson  = Lessons::find($lid);
		$answers = json_decode($answers, 1)?:[];
		if(!$answers){
			$this->error('答案格式错误');
		}
		$teacher_evaluation = json_decode($teacher_evaluation, 1)?:[];
		if(!$teacher_evaluation){
			$this->error('老师评价格式错误');
		}
		$self_evaluation = json_decode($self_evaluation, 1)?:[];
		if(!$self_evaluation){
			$this->error('学生评价格式错误');
		}
		$qids      = array_column($answers, 'qid')?:[];
		$questions = ExamQuestions::where('id', 'in', $qids)->field('id')->select()?:[];
		if(!$questions){
			$this->error('问题缺失');
		}else if(count($questions) != count($qids)){
			$this->error('部分问题缺失');
		}
		$aids = [];
		$tid = Lessons::where('id', $lid)->value('tid')?:0;
		foreach ($answers as $answer) {
			$qid     = $answer['qid'];
			$content = $answer['content'];
			$ret     = StudentExamAnswer::create([
				'sid'     => $sid,
				'lid'     => $lid,
				'qid'     => $qid,
				'cid'     => $cid,
				'tid'     => $tid,
				'type'    => 3,
				'content' => $content,
			]);
			$aids[] = $ret->id;
			$cal    = StudentExamAnswer::score($qid, $content);
			StudentExamAnswer::where('id', $ret->id)->update($cal);
		}
		// 师评
		$info = Evaluation::create([
			'type'    => 1,
			'sid'     => $sid,
			'tid'     => $tid,
			'lid'     => $lid,
			'content' => $teacher_evaluation['content'],
			'remark'  => $teacher_evaluation['remark'],
		]);
		// 自评
		$info = Evaluation::create([
			'type'    => 0,
			'sid'     => $sid,
			'tid'     => $tid,
			'lid'     => $lid,
			'content' => $self_evaluation['content'],
			'remark'  => $self_evaluation['remark'],
		]);
		$ret = Transcript::finish_exam($lesson, $sid, $qids, $aids, $self_evaluation['content']);
		$this->success(SUCCESS_MSG, '', [
			'gold'     => $ret['gold'],
			'accuracy' => $ret['accuracy'],
		]);
	}

	// 提问
	public function general_question_add($sid, $tid, $title, $content, $files=''){
		$student = StudentModel::find($sid);
		if(!$student){
			$this->error('学生不存在');
		}
		$info = GeneralQuestions::create(
			[
				'sid'     => $sid,
				'tid'     => $tid,
				'cid'     => $student['cid'],
				'title'   => $title,
				'content' => $content,
				'files'   => $files,
				'is_read' => 0,
			]
		);
		$this->success(SUCCESS_MSG, '', [
			'info' => $info,
		]);
	}

	// 删除提问
	public function general_question_delete($sid, $qid){
		$reply = GeneralReply::where('qid', $qid)->find();
		if($reply){
			$this->error('已答复的不能删除');
		}
		GeneralQuestions::where('id', $qid)->where('sid', $sid)->delete();
		$this->success(SUCCESS_MSG);
	}

	// 我的提问
	public function general_questions($sid, $is_read = '', $is_reply = '', $tid=0, $between_time = '', $page = 1, $list_rows = 10){
		// $ret = Db::view('GeneralQuestions', ['id', 'title', 'is_read', 'create_time'=>'question_time'])
	 //    ->view('GeneralReply', ['create_time'=>'reply_time', 'id'=>'reply_id'], 'GeneralQuestions.id=GeneralReply.qid', 'LEFT')
	 //    ->where('GeneralReply.id', 'exp', 'is not null')
	 //    ->select();
	 //    $this->success(SUCCESS_MSG, null, ['list'=>$ret, 'sql'=>Db::getLastSql()]);
		$map = [['sid', '=', $sid]];
		if($is_read !== ''){
			$map[] = ['q.is_read', '=', $is_read];
		}
		if($tid){
			$map[] = ['tid', '=', $tid];
		}
		if($is_reply !== ''){
			$map[] = ['r.id','exp', Db::raw($is_reply == 1?'IS NOT NULL':'IS NULL')];
		}
		if($between_time){
			$between_times = explode(',', $between_time);
			if(count($between_times) <2){
				$this->error('时间请传入开始和结束时间');
			}
			$map[] = ['q.create_time', 'between time', $between_time];
		}

		$row_list = GeneralQuestions::alias('q')
			->join('general_reply r', 'q.id = r.qid', 'LEFT')
			->where($map)
			->field(['q.id', 'q.title', 'q.is_read', 'q.create_time'=>'question_time', 'q.tid', 'r.create_time'=>'reply_time', 'r.id'=>'reply_id'])
			->paginate($list_rows)
	        ->each(function ($item, $key) {
	            $item['teacher_name'] = Teacher::where('id', $item['tid'])->cache(1)->value('name');
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

	// 查看提问 和 回答
	public function general_questions_view($sid, $qid, $rid = 0){
		$question = GeneralQuestions::find($qid)->append(['file_list']);
		if(!$question){
			$this->error('提问缺失');
		}
		$reply    = GeneralReply::find($rid);
		if($reply){
			$reply->append(['file_list']);
		}
		if($reply && $question['is_read'] == 0){
			$question->is_read = 1;
			$question->save();
			$reply->is_read = 1;
			$reply->save();
		}
		$this->success(SUCCESS_MSG, '', [
			'question' => $question,
			'reply'    => $reply,
		]);
	}

	// 获取最后一题得分
	public function get_last_question_score($sid, $qid){
		$answer = StudentExamAnswer::where('sid', $sid)->where('qid', $qid)->find()?:$this->empty_obj;
		$this->success(SUCCESS_MSG, '', [
			'answer' => $answer,
		]);
	}

	// 我的作业
	public function transcripts($sid, $page = 1, $list_rows = 10){
		$row_list = Transcript::where('sid', $sid)->append(['teacher.name', 'lesson_question_num','lessons.title'])
			->paginate($list_rows)
	        ->each(function ($item, $key) {
	            $item['teacher_name'] = Teacher::where('id', $item['tid'])->cache(1)->value('name');
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
	public function transcript_detail($sid, $id){
		$ret = Transcript::detail($id);
		$this->success(SUCCESS_MSG, '', [
			'lesson'  => $ret['lesson'],
			'answers' => $ret['answers'],
			'info'    => $ret['info'],
		]);
	}
}
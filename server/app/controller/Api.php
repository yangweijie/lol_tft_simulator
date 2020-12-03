<?php
namespace app\controller;
use GuzzleHttp\Client AS Glient;
use app\BaseController;
use app\model\Teacher;
use app\model\Student;
use app\model\Files;
use think\App;
use think\Debug;
use think\Db;
use think\File;
use think\Hook;
use think\Image;
use think\Session;
use think\Cookie;
use think\Request;
use think\Response;
use \Firebase\JWT\JWT;

// 客户控制器
class Api extends BaseController
{
	use \app\traits\controller\ApiJump;

	public $user_type;
	public $where_map = [];
	public $arr       = [];
	public $empty_obj = null;



	protected $login_types = [
		'student',
		'teacher',
	];

	public $un_jwt_check = [];

	public function __construct(App $app)
	{
		config('default_return_type', 'json');

		!defined('PIZAPIERROR') && define('PIZAPIERROR', 'pizapierror');
		// !defined('UID') && define('UID', 1);
		!defined('SUCCESS_MSG') && define('SUCCESS_MSG', '');
		if(!defined('DOMAIN')){
			define('DOMAIN', 'api.pizhigu.com');
		}
		$this->start_debug();
		debug('api_begin');
		parent::__construct($app);
		$this->jwt_check();
	}


	private function start_debug()
	{
		config('log.force_client_ids', ['client_id_yangweijie']);
	}

	public function jwt_check(){
		$this->empty_obj = new \stdClass();
		$request         = $this->request;
		$header          = $request->header();
		// dump($header);
		// die;
		$controller_name = $request->controller();
		// if(in_array(strtolower($controller_name), ['battle'])){
		// 	$this->un_jwt_check = array_merge($this->un_jwt_check, ['refresh_token']);
		// 	// ptrace($this->un_jwt_check);
		// 	if(false == in_array($request->action(), $this->un_jwt_check) && !isset($_REQUEST['openid'])){
		// 		$jwt = isset($header['http-x-token']) ? $header['http-x-token'] : '';
		// 		if (empty($jwt)) {
		// 			// ptrace($header);
		// 			ptrace('jwt 为空'.PHP_EOL.$jwt);
		// 			$this->error(401);
		// 		}
		// 		try {
		// 			JWT::$leeway = 3600;
		// 			$decoded = JWT::decode($jwt, config('jwt.key'), ['HS256']);
		// 			$arr = (array) $decoded;
		// 			$this->arr = (array)$arr['data'];
		// 		} catch(\Exception $e) {
		// 			ptrace('jwt 失效'.PHP_EOL.$jwt);
		// 			ptrace($e->getMessage());
		// 			$this->error(401);
		// 		}
		// 		$this->jwt = $jwt;
		// 		if(strtolower($controller_name) == 'teacher'){
		// 			$teacher_id = input('tid', 0);
		// 			if($teacher_id !== false){
		// 				if($this->arr['uid'] != $teacher_id){
		// 					ptrace("arr[uid]:{$this->arr['uid']}, teacher_id:{$teacher_id}");
		// 					$this->error(402);
		// 					return;
		// 				}else{
		// 					if(!Teacher::find($teacher_id)){
		// 						$this->error(403);
		// 						return;
		// 					}
		// 					if(!defined('UID')){
		// 						define('UID', $teacher_id);
		// 					}
		// 				}
		// 			}
		// 		}
		// 		if(strtolower($controller_name) == 'student'){
		// 			$student_id = input('sid', 0);
		// 			if($student_id !== false){
		// 				if($this->arr['uid'] != $student_id){
		// 					ptrace("arr[uid]:{$this->arr['uid']}, student_id:{$student_id}");
		// 					$this->error(402);
		// 				}else{
		// 					if(!Student::find($student_id)){
		// 						$this->error(403);
		// 					}
		// 					if(!defined('UID')){
		// 						define('UID', $student_id);
		// 					}
		// 				}
		// 			}
		// 		}
		// 		if(!is_online()){
		// 			return ;
		// 		}
		// 		if ($arr['exp'] < time()) {
		// 			$this->error(403);
		// 		}
		// 	}else{
		// 		if(strtolower($controller_name) == 'admin'){
		// 			if(!defined('UID')){
		// 				define('UID', 1);
		// 			}
		// 		}
		// 	}
		// }else{
			if(strtolower($controller_name) == 'admin'){
				if(!defined('UID')){
					define('UID', 1);
				}
			}
		// }
	}

	public function refresh_token($token){
		$error = '';
		try {
			$decoded = JWT::decode($token, config('jwt.key'), ['HS256']);
			$decoded = json_decode(json_encode($decoded), 1);
			// ptrace($decoded);
			if(isset($decoded['data'])){
				$data = $decoded['data'];
				if(in_array($data['type'], $this->login_types)){
					switch ($data['type']) {
						case 'teacher':
							$exist = Teacher::find($data['uid']);
							break;
						case 'student':
							$exist = Student::find($data['uid']);
							break;
						case 'admin':
							$exist = User::find($data['uid']);
							break;
						default:
							$exist = 0;
							// code...
							break;
					}
					if(!$exist){
						goto error;
					}
					$nowtime = time();
					$token = [
						'iss'  => 'http://'.DOMAIN, //签发者
						'aud'  => 'http://'.DOMAIN, //jwt所面向的用户
						'iat'  => $nowtime, //签发时间
						'nbf'  => $nowtime + 0, //在什么时间之后该jwt才可用
						'exp'  => $nowtime + 3600, //过期时间-60min
						'data' => $data,
					];
					$jwt = JWT::encode($token, config('jwt.key'));
					// ptrace($jwt);
				}else{
					goto error;
				}
			}else{
				goto error;
			}
		} catch (\Exception $e) {
			error:
			$error = '原token失效，请重新登录';
		}
		if($error){
			$this->error($error);
		}else{
			$this->success(SUCCESS_MSG, '', ['token'=>$jwt, 'exp'=>$token['exp']]);
		}
	}

	// 上传
	public function up(){
		$file     = $this->request->file('file');
		$savename = \think\facade\Filesystem::putFile( 'files', $file, 'md5');
		$info = Files::create([
			'name' => $file->getOriginalName(),
			'path' => 'storage/'.$savename,
		]);
		$this->success(SUCCESS_MSG, '', ['info'=>$info]);
	}
}

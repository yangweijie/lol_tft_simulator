<?php
// 应用公共文件

if (!function_exists('debug')) {
    /**
     * 记录时间（微秒）和内存使用情况
     * @param string            $start 开始标签
     * @param string            $end 结束标签
     * @param integer|string    $dec 小数位 如果是m 表示统计内存占用
     * @return mixed
     */
    function debug($start, $end = '', $dec = 6)
    {
        static $info = [];
        static $mem = [];
        if ('' == $end) {
            $info[$start] = is_float($end) ? $end : microtime(true);
        } else {
            if ('time' != $end) {
                $mem['mem'][$start]  = is_float($end) ? $end : memory_get_usage();
                $mem['peak'][$start] = memory_get_peak_usage();
            }
            if($dec == 'm'){
                if (!isset($mem['mem'][$end])) {
                    $mem['mem'][$end] = memory_get_usage();
                }

                $size = $mem['mem'][$end] - $mem['mem'][$start];
                $a    = ['B', 'KB', 'MB', 'GB', 'TB'];
                $pos  = 0;

                while ($size >= 1024) {
                    $size /= 1024;
                    $pos++;
                }

                return round($size, $dec) . " " . $a[$pos];
            }else{
                if (!isset($info[$end])) {
                    $info[$end] = microtime(true);
                }

                return number_format(($info[$end] - $info[$start]), $dec);
            }
        }
    }
}

if(!function_exists('datetime')){
    // 方便生成当前日期函数
    function datetime($str = 'now', $formart = 'Y-m-d H:i:s') {
        return @date($formart, strtotime($str));
    }
}

function is_online(){
    return 1;
}

if(!function_exists('ptrace')){
    function ptrace($msg, $type = 'log')
    {
        trace($msg);
        return false;
        $text         = is_string($msg) ? $msg : '`' . print_r($msg, true) . '`';
        $env          = is_online()? '线上':'测试';
        $in           = PHP_SAPI === 'cli'? 'cli': 'ip:'.get_client_ip(0,true);
        $request_time = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $url          = PHP_SAPI === 'cli'? \think\Request::instance()->url(true): \think\Request::instance()->url(true);
        $md5 = md5(http_build_query([
            $env,$in,$request_time,$url,$type
        ]));
        if($exist = \app\dingding\model\Ptrace::where('md5', $md5)->find()){
            $content        = $exist['content'];
            $content[]      = $text;
            $exist->content = $content;
            $exist->save();
        }else{
            \app\dingding\model\Ptrace::create([
                'md5'          => $md5,
                'env'          => $env,
                'inn'          => $in,
                'request_time' => $request_time,
                'url'          => $url,
                'type'         => $type,
                'content'      => [$text]
            ]);
        }
        // config('log.type', $log_type);
        return true;
    }
}
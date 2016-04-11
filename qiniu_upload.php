<?php
/* 加载七牛上传组件 */
function classLoader($class)
{
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . '/' . $path . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('classLoader');

require_once  __DIR__ . '/Qiniu/functions.php';

// 引入鉴权类
use Qiniu\Auth;

// 引入上传类
use Qiniu\Storage\UploadManager;

// 本地文件地址参数 文件地址或地址数组
if(isset($qiniu_file_path) && ($qiniu_file_path != "" || (is_array($qiniu_file_path) && !empty($qiniu_file_path)))){
	// 需要填写你的 Access Key 和 Secret Key
	$accessKey = 'P8SW3jzO9NLpRgPU48wA8mK3MFBvudiFIPbHusEY';
	$secretKey = 'brrr6sueEC9MJMGsd2vVshJFJWVvw570YFgo_ymD';

	// 构建鉴权对象
	$auth = new Auth($accessKey, $secretKey);

	// 要上传的空间
	$bucket = 'theron';

	// 生成上传 Token
	$token = $auth->uploadToken($bucket);

	if(is_array($qiniu_file_path)){
		// 初始化 UploadManager 对象并进行文件的上传。
		$uploadMgr = new UploadManager();

		foreach ($qiniu_file_path as $key => $qiniu_file_path_item) {
			// 要上传文件的本地路径
			$qiniu_local_file_path = '../'.$qiniu_file_path_item;

			// 上传到七牛后保存的文件名
			#$qiniu_file_path_item = 'my-php-logo.png';
			if(strpos($qiniu_file_path_item, '../') === 0){
				$qiniu_file_path_item = str_replace('../', '', $qiniu_file_path_item);
			}

			// 调用 UploadManager 的 putFile 方法进行文件的上传。
			list($ret, $err) = $uploadMgr->putFile($token, $qiniu_file_path_item, $qiniu_local_file_path);
		}
	}else{
		// 要上传文件的本地路径
		$qiniu_local_file_path = '../'.$qiniu_file_path;

		// 上传到七牛后保存的文件名
		#$qiniu_file_path = 'my-php-logo.png';
		if(strpos($qiniu_file_path, '../') === 0){
			$qiniu_file_path = str_replace('../', '', $qiniu_file_path);
		}

		// 初始化 UploadManager 对象并进行文件的上传。
		$uploadMgr = new UploadManager();

		// 调用 UploadManager 的 putFile 方法进行文件的上传。
		list($ret, $err) = $uploadMgr->putFile($token, $qiniu_file_path, $qiniu_local_file_path);
		/*echo "\n====> putFile result: \n";
		if ($err !== null) {
		    var_dump($err);
		} else {
		    var_dump($ret);
		}*/
	}
}
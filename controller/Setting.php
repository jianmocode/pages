<?php
use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;


class SettingController extends \Xpmse\Loader\Controller {
	
	function __construct() {
	}

	function index() {

		$data['message'] = '
		请在 系统 > 微信 > 公众平台 中完成公众号绑定
		<a href="'. App::URI('baas-admin', 'conf', 'index').'"> 立即设置</a>';
		
		App::render($data,'web','index');
		
		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			'js/plugins/jquery-tags-input/jquery.tagsinput.min.js',
			 		"js/plugins/dropzonejs/dropzone.min.js",
			 		"js/plugins/cropper/cropper.min.js",
		    		'js/plugins/jquery-ui/jquery-ui.min.js',
	        		'js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js',
				],
			'css'=>[
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css"
	 		],
			'crumb' => [
	            "设置" => APP::R('Setting','index'),
	            "选项" =>'',
	        ]
		];
	}



	function faker() {
		$this->loadcates();
		echo json_encode(['code'=>0, 'message'=>'数据生成完毕']);
	}


	private function loadcates( $json_file=null ) {
		if ( empty($json_file) ) {
			$json_file = __DIR__ . "/../test/res/category.json";
		}

		if ( !file_exists($json_file) ) {
			throw new Excp("类型文件不存在 ($json_file)",404 );
		}

		$json_text = file_get_contents($json_file);
		$cates = Utils::json_decode($json_text);

		$cate = new \Xpmsns\Pages\Model\Category;
		$cate->runSQL("truncate table {{table}}");

		foreach ($cates as $c ) {
			$c['status'] = 'on';
			$cate->create( $c );
		}

	}

}
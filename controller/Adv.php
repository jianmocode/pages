<?php
/**
 * Class AdvController
 * 广告控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-03-28 20:14:36
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Media;

class AdvController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 广告列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\Pages\Model\Adv;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "广告列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'adv','search.index');

		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/select2/i18n/zh-CN.js",
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			'js/plugins/jquery-tags-input/jquery.tagsinput.min.js',
		    		'js/plugins/jquery-ui/jquery-ui.min.js',
	        		'js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js',
				],
			'css'=>[
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css"
	 		],
			'crumb' => [
	            "广告" => APP::R('adv','index'),
	            "广告管理" =>'',
	        ]
		];
	}


	/**
	 * 广告详情表单
	 */
	function detail() {

		$adv_id = trim($_GET['adv_id']);
		$action_name = '新建广告';
		$inst = new \Xpmsns\Pages\Model\Adv;
		
		if ( !empty($adv_id) ) {
			$rs = $inst->getByAdvId($adv_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['adv_id'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'adv_id'=>$adv_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'adv','form');

		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/select2/i18n/zh-CN.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js",
		    		"js/plugins/summernote/summernote.min.js",
                    "js/plugins/summernote/lang/summernote-zh-CN.js",
                    "js/plugins/codemirror/lib/codemirror.js",
                    "js/plugins/codemirror/addon/search/searchcursor.js",
                    "js/plugins/codemirror/addon/search/search.js",
                    "js/plugins/codemirror/addon/dialog/dialog.js",
                    "js/plugins/codemirror/addon/edit/matchbrackets.js",
                    "js/plugins/codemirror/addon/edit/closebrackets.js",
                    "js/plugins/codemirror/addon/comment/comment.js",
                    "js/plugins/codemirror/addon/wrap/hardwrap.js",
                    "js/plugins/codemirror/addon/fold/foldcode.js",
                    "js/plugins/codemirror/addon/fold/brace-fold.js",
                    "js/plugins/codemirror/mode/javascript/javascript.js",
                    "js/plugins/codemirror/mode/shell/shell.js",
                    "js/plugins/codemirror/mode/sql/sql.js",
                    "js/plugins/codemirror/mode/python/python.js",
                    "js/plugins/codemirror/mode/go/go.js",
                    "js/plugins/codemirror/mode/php/php.js",
                    "js/plugins/codemirror/mode/htmlmixed/htmlmixed.js",
                    "js/plugins/codemirror/mode/xml/xml.js",
                    "js/plugins/codemirror/mode/css/css.js",
                    "js/plugins/codemirror/mode/sass/sass.js",
                    "js/plugins/codemirror/mode/vue/vue.js",
                    "js/plugins/codemirror/mode/textile/textile.js",
                    "js/plugins/codemirror/mode/clike/clike.js",
                    "js/plugins/codemirror/mode/markdown/markdown.js",
                    "js/plugins/codemirror/keymap/sublime.js",
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css",
	 			"js/plugins/summernote/summernote.css",
                "js/plugins/summernote/summernote-bs3.min.css",
                "js/plugins/codemirror/lib/codemirror.css",
                "js/plugins/codemirror/addon/fold/foldgutter.css",
                "js/plugins/codemirror/addon/dialog/dialog.css",
                "js/plugins/codemirror/theme/monokai.css",
	 		],

			'crumb' => [
	            "广告" => APP::R('adv','index'),
	            "广告管理" =>APP::R('adv','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/adv/index'
	 		]
		];

	}



	/**
	 * 保存广告
	 * @return
	 */
	function save() {
        $data = $_POST;
        Utils::JsonFromInput( $data );
		$inst = new \Xpmsns\Pages\Model\Adv;
		$rs = $inst->saveByAdvId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除广告
	 * @return [type] [description]
	 */
	function remove(){
		$adv_id = $_POST['adv_id'];
		$inst = new \Xpmsns\Pages\Model\Adv;
		$adv_ids =$inst->remove( $adv_id, "adv_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$adv_ids'=>$adv_ids]]);
	}

	/**
	 * 复制广告
	 * @return
	 */
	function duplicate(){
		$adv_id = $_GET['adv_id'];
		$inst = new \Xpmsns\Pages\Model\Adv;
		$rs = $inst->getByAdvId( $adv_id );
		$action_name =  $rs['adv_id'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['adv_id']);
		unset($rs['adv_slug']);

		// 复制图片
		if ( is_array($rs['images'])) {

			$resp = [];
			foreach ($rs['images'] as $idx=>$fs ) {

				if ( empty($fs['local']) ) {
					continue;
				}
				$resp[] = $inst->uploadImagesByAdvId( $adv_id, $fs['local'], $idx, true);
			}

			$rs['images'] = $resp;
		}

		if ( is_array($rs['cover']) &&  !empty($rs['cover']['local'])) {
			$rs['cover'] = $inst->uploadCoverByAdvId( $adv_id, $rs['cover']['local'], true);
		}
		if ( is_array($rs['terms']) &&  !empty($rs['terms']['local'])) {
			$rs['terms'] = $inst->uploadTermsByAdvId( $adv_id, $rs['terms']['local'], true);
		}

		$data = [
			'action_name' =>  $action_name,
			'adv_id'=>$adv_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'adv','form');

		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/select2/i18n/zh-CN.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js",
		    		"js/plugins/summernote/summernote.min.js",
                    "js/plugins/summernote/lang/summernote-zh-CN.js",
                    "js/plugins/codemirror/lib/codemirror.js",
                    "js/plugins/codemirror/addon/search/searchcursor.js",
                    "js/plugins/codemirror/addon/search/search.js",
                    "js/plugins/codemirror/addon/dialog/dialog.js",
                    "js/plugins/codemirror/addon/edit/matchbrackets.js",
                    "js/plugins/codemirror/addon/edit/closebrackets.js",
                    "js/plugins/codemirror/addon/comment/comment.js",
                    "js/plugins/codemirror/addon/wrap/hardwrap.js",
                    "js/plugins/codemirror/addon/fold/foldcode.js",
                    "js/plugins/codemirror/addon/fold/brace-fold.js",
                    "js/plugins/codemirror/mode/javascript/javascript.js",
                    "js/plugins/codemirror/mode/shell/shell.js",
                    "js/plugins/codemirror/mode/sql/sql.js",
                    "js/plugins/codemirror/mode/python/python.js",
                    "js/plugins/codemirror/mode/go/go.js",
                    "js/plugins/codemirror/mode/php/php.js",
                    "js/plugins/codemirror/mode/htmlmixed/htmlmixed.js",
                    "js/plugins/codemirror/mode/xml/xml.js",
                    "js/plugins/codemirror/mode/css/css.js",
                    "js/plugins/codemirror/mode/sass/sass.js",
                    "js/plugins/codemirror/mode/vue/vue.js",
                    "js/plugins/codemirror/mode/textile/textile.js",
                    "js/plugins/codemirror/mode/clike/clike.js",
                    "js/plugins/codemirror/mode/markdown/markdown.js",
                    "js/plugins/codemirror/keymap/sublime.js",
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css",
	 			"js/plugins/summernote/summernote.css",
                "js/plugins/summernote/summernote-bs3.min.css",
                "js/plugins/codemirror/lib/codemirror.css",
                "js/plugins/codemirror/addon/fold/foldgutter.css",
                "js/plugins/codemirror/addon/dialog/dialog.css",
                "js/plugins/codemirror/theme/monokai.css",
	 		],

			'crumb' => [
	            "广告" => APP::R('adv','index'),
	            "广告管理" =>APP::R('adv','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/pages/adv/index'
	 		]
		];
	}



}
<?php
/**
 * Class Userevent 
 * 报名数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-03-02 22:59:59
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\Pages\Model;
            
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Mina\Cache\Redis as Cache;
use \Xpmse\Loader\App as App;
use \Xpmse\Job;


class Userevent extends Model {




    /**
     * 数据缓存对象
     */
    protected $cache = null;

	/**
	 * 报名数据模型【3】
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_pages_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_pages_'],$param));
        $this->table('userevent'); // 数据表名称 xpmsns_pages_userevent
         // + Redis缓存
        $this->cache = new Cache([
            "prefix" => "xpmsns_pages_userevent:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);


       
	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN


    /**
     * 重载SaveBy
     */
    public function saveBy( $uniqueKey,  $data,  $keys=null , $select=["*"]) {
        if ( !empty($data["user_id"]) &&  !empty($data["event_id"]) ) {
            $data["event_user_id"] = "DB::RAW(CONCAT(`event_id`,'_', `user_id`))";           
        }
        return parent::saveBy( $uniqueKey,  $data,  $keys , $select );
    }


	/**
	 * 重载Remove
	 * @return [type] [description]
	 */
	function remove( $data_key, $uni_key="_id", $mark_only=true ){ 
		
		if ( $mark_only === true ) {

			$time = date('Y-m-d H:i:s');
			$_id = $this->getVar("_id", "WHERE {$uni_key}=? LIMIT 1", [$data_key]);
			$row = $this->update( $_id, [
				"deleted_at"=>$time, 
				"event_user_id"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `event_user_id`))"
			]);

			if ( $row['deleted_at'] == $time ) {	
				return true;
			}
			return false;
		}

		return parent::remove($data_key, $uni_key, $mark_only);
    }
    
    // @KEEP END


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 报名ID
		$this->putColumn( 'userevent_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 用户ID
		$this->putColumn( 'user_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 活动ID
		$this->putColumn( 'event_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 唯一ID
		$this->putColumn( 'event_user_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 报名时间
		$this->putColumn( 'signin_at', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 签到时间
		$this->putColumn( 'checkin_at', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {
     
		$fileFields = []; 

        // 处理图片和文件字段 
        $this->__fileFields( $rs, $fileFields );

		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"signin" => [
		  			"value" => "signin",
		  			"name" => "已报名",
		  			"style" => "primary"
		  		],
		  		"paid" => [
		  			"value" => "paid",
		  			"name" => "已付款",
		  			"style" => "success"
		  		],
		  		"checkin" => [
		  			"value" => "checkin",
		  			"name" => "已签到",
		  			"style" => "danger"
		  		],
		  		"cancel" => [
		  			"value" => "cancel",
		  			"name" => "已取消",
		  			"style" => "muted"
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按报名ID查询一条报名记录
	 * @param string $userevent_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["userevent_id"],  // 报名ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["event_id"],  // 活动ID 
	 *                $rs["event_event_id"], // event.event_id
	 *          	  $rs["event_user_id"],  // 唯一ID 
	 *          	  $rs["signin_at"],  // 报名时间 
	 *          	  $rs["checkin_at"],  // 签到时间 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
	 *                $rs["user_mobile"], // user.mobile
	 *                $rs["user_mobile_nation"], // user.mobile_nation
	 *                $rs["user_mobile_full"], // user.mobile_full
	 *                $rs["user_email"], // user.email
	 *                $rs["user_contact_name"], // user.contact_name
	 *                $rs["user_contact_tel"], // user.contact_tel
	 *                $rs["user_title"], // user.title
	 *                $rs["user_company"], // user.company
	 *                $rs["user_zip"], // user.zip
	 *                $rs["user_address"], // user.address
	 *                $rs["user_remark"], // user.remark
	 *                $rs["user_tag"], // user.tag
	 *                $rs["user_user_verified"], // user.user_verified
	 *                $rs["user_name_verified"], // user.name_verified
	 *                $rs["user_verify"], // user.verify
	 *                $rs["user_verify_data"], // user.verify_data
	 *                $rs["user_mobile_verified"], // user.mobile_verified
	 *                $rs["user_email_verified"], // user.email_verified
	 *                $rs["user_extra"], // user.extra
	 *                $rs["user_password"], // user.password
	 *                $rs["user_pay_password"], // user.pay_password
	 *                $rs["user_status"], // user.status
	 *                $rs["user_inviter"], // user.inviter
	 *                $rs["user_follower_cnt"], // user.follower_cnt
	 *                $rs["user_following_cnt"], // user.following_cnt
	 *                $rs["user_name_message"], // user.name_message
	 *                $rs["user_verify_message"], // user.verify_message
	 *                $rs["user_client_token"], // user.client_token
	 *                $rs["user_user_name"], // user.user_name
	 *                $rs["user_article_cnt"], // user.article_cnt
	 *                $rs["user_question_cnt"], // user.question_cnt
	 *                $rs["user_answer_cnt"], // user.answer_cnt
	 *                $rs["user_favorite_cnt"], // user.favorite_cnt
	 *                $rs["user_coin"], // user.coin
	 *                $rs["user_balance"], // user.balance
	 *                $rs["user_priority"], // user.priority
	 *                $rs["event_created_at"], // event.created_at
	 *                $rs["event_updated_at"], // event.updated_at
	 *                $rs["event_slug"], // event.slug
	 *                $rs["event_name"], // event.name
	 *                $rs["event_link"], // event.link
	 *                $rs["event_categories"], // event.categories
	 *                $rs["event_type"], // event.type
	 *                $rs["event_tags"], // event.tags
	 *                $rs["event_summary"], // event.summary
	 *                $rs["event_cover"], // event.cover
	 *                $rs["event_images"], // event.images
	 *                $rs["event_begin"], // event.begin
	 *                $rs["event_end"], // event.end
	 *                $rs["event_area"], // event.area
	 *                $rs["event_prov"], // event.prov
	 *                $rs["event_city"], // event.city
	 *                $rs["event_town"], // event.town
	 *                $rs["event_location"], // event.location
	 *                $rs["event_price"], // event.price
	 *                $rs["event_hosts"], // event.hosts
	 *                $rs["event_organizers"], // event.organizers
	 *                $rs["event_sponsors"], // event.sponsors
	 *                $rs["event_medias"], // event.medias
	 *                $rs["event_speakers"], // event.speakers
	 *                $rs["event_content"], // event.content
	 *                $rs["event_publish_time"], // event.publish_time
	 *                $rs["event_view_cnt"], // event.view_cnt
	 *                $rs["event_like_cnt"], // event.like_cnt
	 *                $rs["event_dislike_cnt"], // event.dislike_cnt
	 *                $rs["event_comment_cnt"], // event.comment_cnt
	 *                $rs["event_status"], // event.status
	 *                $rs["event_title"], // event.title
	 *                $rs["event_process_setting"], // event.process_setting
	 *                $rs["event_process"], // event.process
	 *                $rs["event_bonus"], // event.bonus
	 *                $rs["event_prize"], // event.prize
	 *                $rs["event_desktop"], // event.desktop
	 *                $rs["event_mobile"], // event.mobile
	 *                $rs["event_wxapp"], // event.wxapp
	 *                $rs["event_app"], // event.app
	 *                $rs["event_agree_cnt"], // event.agree_cnt
	 *                $rs["event_quota"], // event.quota
	 *                $rs["event_user_cnt"], // event.user_cnt
	 */
	public function getByUsereventId( $userevent_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "userevent.userevent_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_userevent as userevent", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "userevent.user_id"); // 连接user
 		$qb->leftJoin("xpmsns_pages_event as event", "event.event_id", "=", "userevent.event_id"); // 连接event
		$qb->where('userevent.userevent_id', '=', $userevent_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

  
  
		return $rs;
	}

		

	/**
	 * 按报名ID查询一组报名记录
	 * @param array   $userevent_ids 唯一主键数组 ["$userevent_id1","$userevent_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 报名记录MAP {"userevent_id1":{"key":"value",...}...}
	 */
	public function getInByUsereventId($userevent_ids, $select=["userevent.event_id","event.slug","event.title","userevent.user_id","user.name","user.nickname","user.mobile","userevent.signin_at","userevent.checkin_at","userevent.status"], $order=["userevent.checkin_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "userevent.userevent_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_userevent as userevent", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "userevent.user_id"); // 连接user
 		$qb->leftJoin("xpmsns_pages_event as event", "event.event_id", "=", "userevent.event_id"); // 连接event
		$qb->whereIn('userevent.userevent_id', $userevent_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

  		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['userevent_id']] = $rs;
			
  		}

  

		return $map;
	}


	/**
	 * 按报名ID保存报名记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByUsereventId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "userevent.userevent_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("userevent_id", $data, ["userevent_id", "event_user_id"], ['_id', 'userevent_id']);
		return $this->getByUsereventId( $rs['userevent_id'], $select );
	}
	
	/**
	 * 按唯一ID查询一条报名记录
	 * @param string $event_user_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["userevent_id"],  // 报名ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["event_id"],  // 活动ID 
	 *                $rs["event_event_id"], // event.event_id
	 *          	  $rs["event_user_id"],  // 唯一ID 
	 *          	  $rs["signin_at"],  // 报名时间 
	 *          	  $rs["checkin_at"],  // 签到时间 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
	 *                $rs["user_mobile"], // user.mobile
	 *                $rs["user_mobile_nation"], // user.mobile_nation
	 *                $rs["user_mobile_full"], // user.mobile_full
	 *                $rs["user_email"], // user.email
	 *                $rs["user_contact_name"], // user.contact_name
	 *                $rs["user_contact_tel"], // user.contact_tel
	 *                $rs["user_title"], // user.title
	 *                $rs["user_company"], // user.company
	 *                $rs["user_zip"], // user.zip
	 *                $rs["user_address"], // user.address
	 *                $rs["user_remark"], // user.remark
	 *                $rs["user_tag"], // user.tag
	 *                $rs["user_user_verified"], // user.user_verified
	 *                $rs["user_name_verified"], // user.name_verified
	 *                $rs["user_verify"], // user.verify
	 *                $rs["user_verify_data"], // user.verify_data
	 *                $rs["user_mobile_verified"], // user.mobile_verified
	 *                $rs["user_email_verified"], // user.email_verified
	 *                $rs["user_extra"], // user.extra
	 *                $rs["user_password"], // user.password
	 *                $rs["user_pay_password"], // user.pay_password
	 *                $rs["user_status"], // user.status
	 *                $rs["user_inviter"], // user.inviter
	 *                $rs["user_follower_cnt"], // user.follower_cnt
	 *                $rs["user_following_cnt"], // user.following_cnt
	 *                $rs["user_name_message"], // user.name_message
	 *                $rs["user_verify_message"], // user.verify_message
	 *                $rs["user_client_token"], // user.client_token
	 *                $rs["user_user_name"], // user.user_name
	 *                $rs["user_article_cnt"], // user.article_cnt
	 *                $rs["user_question_cnt"], // user.question_cnt
	 *                $rs["user_answer_cnt"], // user.answer_cnt
	 *                $rs["user_favorite_cnt"], // user.favorite_cnt
	 *                $rs["user_coin"], // user.coin
	 *                $rs["user_balance"], // user.balance
	 *                $rs["user_priority"], // user.priority
	 *                $rs["event_created_at"], // event.created_at
	 *                $rs["event_updated_at"], // event.updated_at
	 *                $rs["event_slug"], // event.slug
	 *                $rs["event_name"], // event.name
	 *                $rs["event_link"], // event.link
	 *                $rs["event_categories"], // event.categories
	 *                $rs["event_type"], // event.type
	 *                $rs["event_tags"], // event.tags
	 *                $rs["event_summary"], // event.summary
	 *                $rs["event_cover"], // event.cover
	 *                $rs["event_images"], // event.images
	 *                $rs["event_begin"], // event.begin
	 *                $rs["event_end"], // event.end
	 *                $rs["event_area"], // event.area
	 *                $rs["event_prov"], // event.prov
	 *                $rs["event_city"], // event.city
	 *                $rs["event_town"], // event.town
	 *                $rs["event_location"], // event.location
	 *                $rs["event_price"], // event.price
	 *                $rs["event_hosts"], // event.hosts
	 *                $rs["event_organizers"], // event.organizers
	 *                $rs["event_sponsors"], // event.sponsors
	 *                $rs["event_medias"], // event.medias
	 *                $rs["event_speakers"], // event.speakers
	 *                $rs["event_content"], // event.content
	 *                $rs["event_publish_time"], // event.publish_time
	 *                $rs["event_view_cnt"], // event.view_cnt
	 *                $rs["event_like_cnt"], // event.like_cnt
	 *                $rs["event_dislike_cnt"], // event.dislike_cnt
	 *                $rs["event_comment_cnt"], // event.comment_cnt
	 *                $rs["event_status"], // event.status
	 *                $rs["event_title"], // event.title
	 *                $rs["event_process_setting"], // event.process_setting
	 *                $rs["event_process"], // event.process
	 *                $rs["event_bonus"], // event.bonus
	 *                $rs["event_prize"], // event.prize
	 *                $rs["event_desktop"], // event.desktop
	 *                $rs["event_mobile"], // event.mobile
	 *                $rs["event_wxapp"], // event.wxapp
	 *                $rs["event_app"], // event.app
	 *                $rs["event_agree_cnt"], // event.agree_cnt
	 *                $rs["event_quota"], // event.quota
	 *                $rs["event_user_cnt"], // event.user_cnt
	 */
	public function getByEventUserId( $event_user_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "userevent.userevent_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_userevent as userevent", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "userevent.user_id"); // 连接user
 		$qb->leftJoin("xpmsns_pages_event as event", "event.event_id", "=", "userevent.event_id"); // 连接event
		$qb->where('userevent.event_user_id', '=', $event_user_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

  
  
		return $rs;
	}

	

	/**
	 * 按唯一ID查询一组报名记录
	 * @param array   $event_user_ids 唯一主键数组 ["$event_user_id1","$event_user_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 报名记录MAP {"event_user_id1":{"key":"value",...}...}
	 */
	public function getInByEventUserId($event_user_ids, $select=["userevent.event_id","event.slug","event.title","userevent.user_id","user.name","user.nickname","user.mobile","userevent.signin_at","userevent.checkin_at","userevent.status"], $order=["userevent.checkin_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "userevent.userevent_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_userevent as userevent", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "userevent.user_id"); // 连接user
 		$qb->leftJoin("xpmsns_pages_event as event", "event.event_id", "=", "userevent.event_id"); // 连接event
		$qb->whereIn('userevent.event_user_id', $event_user_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

  		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['event_user_id']] = $rs;
			
  		}

  

		return $map;
	}


	/**
	 * 按唯一ID保存报名记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByEventUserId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "userevent.userevent_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("event_user_id", $data, ["userevent_id", "event_user_id"], ['_id', 'userevent_id']);
		return $this->getByUsereventId( $rs['userevent_id'], $select );
	}


	/**
	 * 添加报名记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["userevent_id"]) ) { 
			$data["userevent_id"] = $this->genId();
        }
        
        // @KEEP BEGIN
        if ( !empty($data["user_id"]) &&  !empty($data["event_id"]) ) {
            $data["event_user_id"] = "DB::RAW(CONCAT(`event_id`,'_', `user_id`))";
        }
        // @KEEP END

		return parent::create( $data );
	}


	/**
	 * 查询前排报名记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 报名记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["userevent.event_id","event.slug","event.title","userevent.user_id","user.name","user.nickname","user.mobile","userevent.signin_at","userevent.checkin_at","userevent.status"], $order=["userevent.checkin_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "userevent.userevent_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_userevent as userevent", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "userevent.user_id"); // 连接user
 		$qb->leftJoin("xpmsns_pages_event as event", "event.event_id", "=", "userevent.event_id"); // 连接event


		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


  		foreach ($data as & $rs ) {
			$this->format($rs);
			
  		}

  
		return $data;
	
	}


	/**
	 * 按条件检索报名记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["userevent.event_id","event.slug","event.title","userevent.user_id","user.name","user.nickname","user.mobile","userevent.signin_at","userevent.checkin_at","userevent.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["userevent_id"] 按报名ID查询 ( = )
	 *			      $query["user_id"] 按用户ID查询 ( = )
	 *			      $query["event_id"] 按活动ID查询 ( = )
	 *			      $query["user_mobile"] 按查询 ( = )
	 *			      $query["user_name"] 按查询 ( LIKE )
	 *			      $query["user_nickname"] 按查询 ( LIKE )
	 *			      $query["event_title"] 按查询 ( LIKE )
	 *			      $query["event_begin"] 按查询 ( = )
	 *			      $query["event_end"] 按查询 ( = )
	 *			      $query["signin_at"] 按报名时间查询 ( > )
	 *			      $query["checkin_at"] 按签到时间查询 ( > )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["orderby_checkin_at_desc"]  按name=checkin_at DESC 排序
	 *			      $query["orderby_signin_at_desc"]  按name=signin_at DESC 排序
	 *			      $query["orderby_event_publish_time_desc"]  按model=%5CXpmsns%5CPages%5CModel%5CEvent&name=publish_time&table=event&prefix=xpmsns_pages_&alias=event&type=leftJoin DESC 排序
	 *			      $query["orderby_event_begin_desc"]  按model=%5CXpmsns%5CPages%5CModel%5CEvent&name=begin&table=event&prefix=xpmsns_pages_&alias=event&type=leftJoin DESC 排序
	 *			      $query["orderby_event_end_desc"]  按model=%5CXpmsns%5CPages%5CModel%5CEvent&name=end&table=event&prefix=xpmsns_pages_&alias=event&type=leftJoin DESC 排序
	 *           
	 * @return array 报名记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["userevent_id"],  // 报名ID 
	 *               	["user_id"],  // 用户ID 
	 *               	["user_user_id"], // user.user_id
	 *               	["event_id"],  // 活动ID 
	 *               	["event_event_id"], // event.event_id
	 *               	["event_user_id"],  // 唯一ID 
	 *               	["signin_at"],  // 报名时间 
	 *               	["checkin_at"],  // 签到时间 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["user_created_at"], // user.created_at
	 *               	["user_updated_at"], // user.updated_at
	 *               	["user_group_id"], // user.group_id
	 *               	["user_name"], // user.name
	 *               	["user_idno"], // user.idno
	 *               	["user_idtype"], // user.idtype
	 *               	["user_iddoc"], // user.iddoc
	 *               	["user_nickname"], // user.nickname
	 *               	["user_sex"], // user.sex
	 *               	["user_city"], // user.city
	 *               	["user_province"], // user.province
	 *               	["user_country"], // user.country
	 *               	["user_headimgurl"], // user.headimgurl
	 *               	["user_language"], // user.language
	 *               	["user_birthday"], // user.birthday
	 *               	["user_bio"], // user.bio
	 *               	["user_bgimgurl"], // user.bgimgurl
	 *               	["user_mobile"], // user.mobile
	 *               	["user_mobile_nation"], // user.mobile_nation
	 *               	["user_mobile_full"], // user.mobile_full
	 *               	["user_email"], // user.email
	 *               	["user_contact_name"], // user.contact_name
	 *               	["user_contact_tel"], // user.contact_tel
	 *               	["user_title"], // user.title
	 *               	["user_company"], // user.company
	 *               	["user_zip"], // user.zip
	 *               	["user_address"], // user.address
	 *               	["user_remark"], // user.remark
	 *               	["user_tag"], // user.tag
	 *               	["user_user_verified"], // user.user_verified
	 *               	["user_name_verified"], // user.name_verified
	 *               	["user_verify"], // user.verify
	 *               	["user_verify_data"], // user.verify_data
	 *               	["user_mobile_verified"], // user.mobile_verified
	 *               	["user_email_verified"], // user.email_verified
	 *               	["user_extra"], // user.extra
	 *               	["user_password"], // user.password
	 *               	["user_pay_password"], // user.pay_password
	 *               	["user_status"], // user.status
	 *               	["user_inviter"], // user.inviter
	 *               	["user_follower_cnt"], // user.follower_cnt
	 *               	["user_following_cnt"], // user.following_cnt
	 *               	["user_name_message"], // user.name_message
	 *               	["user_verify_message"], // user.verify_message
	 *               	["user_client_token"], // user.client_token
	 *               	["user_user_name"], // user.user_name
	 *               	["user_article_cnt"], // user.article_cnt
	 *               	["user_question_cnt"], // user.question_cnt
	 *               	["user_answer_cnt"], // user.answer_cnt
	 *               	["user_favorite_cnt"], // user.favorite_cnt
	 *               	["user_coin"], // user.coin
	 *               	["user_balance"], // user.balance
	 *               	["user_priority"], // user.priority
	 *               	["event_created_at"], // event.created_at
	 *               	["event_updated_at"], // event.updated_at
	 *               	["event_slug"], // event.slug
	 *               	["event_name"], // event.name
	 *               	["event_link"], // event.link
	 *               	["event_categories"], // event.categories
	 *               	["event_type"], // event.type
	 *               	["event_tags"], // event.tags
	 *               	["event_summary"], // event.summary
	 *               	["event_cover"], // event.cover
	 *               	["event_images"], // event.images
	 *               	["event_begin"], // event.begin
	 *               	["event_end"], // event.end
	 *               	["event_area"], // event.area
	 *               	["event_prov"], // event.prov
	 *               	["event_city"], // event.city
	 *               	["event_town"], // event.town
	 *               	["event_location"], // event.location
	 *               	["event_price"], // event.price
	 *               	["event_hosts"], // event.hosts
	 *               	["event_organizers"], // event.organizers
	 *               	["event_sponsors"], // event.sponsors
	 *               	["event_medias"], // event.medias
	 *               	["event_speakers"], // event.speakers
	 *               	["event_content"], // event.content
	 *               	["event_publish_time"], // event.publish_time
	 *               	["event_view_cnt"], // event.view_cnt
	 *               	["event_like_cnt"], // event.like_cnt
	 *               	["event_dislike_cnt"], // event.dislike_cnt
	 *               	["event_comment_cnt"], // event.comment_cnt
	 *               	["event_status"], // event.status
	 *               	["event_title"], // event.title
	 *               	["event_process_setting"], // event.process_setting
	 *               	["event_process"], // event.process
	 *               	["event_bonus"], // event.bonus
	 *               	["event_prize"], // event.prize
	 *               	["event_desktop"], // event.desktop
	 *               	["event_mobile"], // event.mobile
	 *               	["event_wxapp"], // event.wxapp
	 *               	["event_app"], // event.app
	 *               	["event_agree_cnt"], // event.agree_cnt
	 *               	["event_quota"], // event.quota
	 *               	["event_user_cnt"], // event.user_cnt
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["userevent.event_id","event.slug","event.title","userevent.user_id","user.name","user.nickname","user.mobile","userevent.signin_at","userevent.checkin_at","userevent.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "userevent.userevent_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_pages_userevent as userevent", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "userevent.user_id"); // 连接user
 		$qb->leftJoin("xpmsns_pages_event as event", "event.event_id", "=", "userevent.event_id"); // 连接event

		// 按关键词查找
		if ( array_key_exists("keywords", $query) && !empty($query["keywords"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("userevent.userevent_id", "like", "%{$query['keywords']}%");
				$qb->orWhere("userevent.user_id","like", "%{$query['keywords']}%");
				$qb->orWhere("userevent.event_id","like", "%{$query['keywords']}%");
				$qb->orWhere("userevent.event_user_id","like", "%{$query['keywords']}%");
				$qb->orWhere("user.user_name","like", "%{$query['keywords']}%");
				$qb->orWhere("user.mobile_full","like", "%{$query['keywords']}%");
				$qb->orWhere("user.name","like", "%{$query['keywords']}%");
				$qb->orWhere("user.nickname","like", "%{$query['keywords']}%");
				$qb->orWhere("event.slug","like", "%{$query['keywords']}%");
				$qb->orWhere("event.title","like", "%{$query['keywords']}%");
			});
		}


		// 按报名ID查询 (=)  
		if ( array_key_exists("userevent_id", $query) &&!empty($query['userevent_id']) ) {
			$qb->where("userevent.userevent_id", '=', "{$query['userevent_id']}" );
		}
		  
		// 按用户ID查询 (=)  
		if ( array_key_exists("user_id", $query) &&!empty($query['user_id']) ) {
			$qb->where("userevent.user_id", '=', "{$query['user_id']}" );
		}
		  
		// 按活动ID查询 (=)  
		if ( array_key_exists("event_id", $query) &&!empty($query['event_id']) ) {
			$qb->where("userevent.event_id", '=', "{$query['event_id']}" );
		}
		  
		// 按查询 (=)  
		if ( array_key_exists("user_mobile", $query) &&!empty($query['user_mobile']) ) {
			$qb->where("user.mobile_full", '=', "{$query['user_mobile']}" );
		}
		  
		// 按查询 (LIKE)  
		if ( array_key_exists("user_name", $query) &&!empty($query['user_name']) ) {
			$qb->where("user.name", 'like', "%{$query['user_name']}%" );
		}
		  
		// 按查询 (LIKE)  
		if ( array_key_exists("user_nickname", $query) &&!empty($query['user_nickname']) ) {
			$qb->where("user.nickname", 'like', "%{$query['user_nickname']}%" );
		}
		  
		// 按查询 (LIKE)  
		if ( array_key_exists("event_title", $query) &&!empty($query['event_title']) ) {
			$qb->where("event.title", 'like', "%{$query['event_title']}%" );
		}
		  
		// 按查询 (=)  
		if ( array_key_exists("event_begin", $query) &&!empty($query['event_begin']) ) {
			$qb->where("event.begin", '=', "{$query['event_begin']}" );
		}
		  
		// 按查询 (=)  
		if ( array_key_exists("event_end", $query) &&!empty($query['event_end']) ) {
			$qb->where("event.end", '=', "{$query['event_end']}" );
		}
		  
		// 按报名时间查询 (>)  
		if ( array_key_exists("signin_at", $query) &&!empty($query['signin_at']) ) {
			$qb->where("userevent.signin_at", '>', "{$query['signin_at']}" );
		}
		  
		// 按签到时间查询 (>)  
		if ( array_key_exists("checkin_at", $query) &&!empty($query['checkin_at']) ) {
			$qb->where("userevent.checkin_at", '>', "{$query['checkin_at']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("userevent.status", '=', "{$query['status']}" );
		}
		  

		// 按name=checkin_at DESC 排序
		if ( array_key_exists("orderby_checkin_at_desc", $query) &&!empty($query['orderby_checkin_at_desc']) ) {
			$qb->orderBy("userevent.checkin_at", "desc");
		}

		// 按name=signin_at DESC 排序
		if ( array_key_exists("orderby_signin_at_desc", $query) &&!empty($query['orderby_signin_at_desc']) ) {
			$qb->orderBy("userevent.signin_at", "desc");
		}

		// 按model=%5CXpmsns%5CPages%5CModel%5CEvent&name=publish_time&table=event&prefix=xpmsns_pages_&alias=event&type=leftJoin DESC 排序
		if ( array_key_exists("orderby_event_publish_time_desc", $query) &&!empty($query['orderby_event_publish_time_desc']) ) {
			$qb->orderBy("event.publish_time", "desc");
		}

		// 按model=%5CXpmsns%5CPages%5CModel%5CEvent&name=begin&table=event&prefix=xpmsns_pages_&alias=event&type=leftJoin DESC 排序
		if ( array_key_exists("orderby_event_begin_desc", $query) &&!empty($query['orderby_event_begin_desc']) ) {
			$qb->orderBy("event.begin", "desc");
		}

		// 按model=%5CXpmsns%5CPages%5CModel%5CEvent&name=end&table=event&prefix=xpmsns_pages_&alias=event&type=leftJoin DESC 排序
		if ( array_key_exists("orderby_event_end_desc", $query) &&!empty($query['orderby_event_end_desc']) ) {
			$qb->orderBy("event.end", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$userevents = $qb->select( $select )->pgArray($perpage, ['userevent._id'], 'page', $page);

  		foreach ($userevents['data'] as & $rs ) {
			$this->format($rs);
			
  		}

  	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$userevents['_sql'] = $qb->getSql();
			$userevents['query'] = $query;
		}

		return $userevents;
	}

	/**
	 * 格式化读取字段
	 * @param  array $select 选中字段
	 * @return array $inWhere 读取字段
	 */
	public function formatSelect( & $select ) {
		// 过滤 inWhere 查询字段
		$inwhereSelect = []; $linkSelect = [];
		foreach ($select as $idx=>$fd ) {
			
			// 添加本表前缀
			if ( !strpos( $fd, ".")  ) {
				$select[$idx] = "userevent." .$select[$idx];
				continue;
			}
			
			//  连接user (user as user )
			if ( trim($fd) == "user.*" || trim($fd) == "user.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\User\\Model\\User", 'getFields') ) {
					$fields = \Xpmsns\User\Model\User::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "user.{$field} as user_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "userevent.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "user." ) === 0 ) {
				$as = str_replace('user.', 'user_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "user.") === 0 ) {
				$as = str_replace('user.', 'user_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			
			//  连接event (event as event )
			if ( trim($fd) == "event.*" || trim($fd) == "event.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\Pages\\Model\\Event", 'getFields') ) {
					$fields = \Xpmsns\Pages\Model\Event::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "event.{$field} as event_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "userevent.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "event." ) === 0 ) {
				$as = str_replace('event.', 'event_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "event.") === 0 ) {
				$as = str_replace('event.', 'event_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

		}

		// filter 查询字段
		foreach ($inwhereSelect as & $iws ) {
			if ( is_array($iws) ) {
				$iws = array_unique(array_filter($iws));
			}
		}

		$select = array_unique(array_merge($linkSelect, $select));
		return $inwhereSelect;
	}

	/**
	 * 返回所有字段
	 * @return array 字段清单
	 */
	public static function getFields() {
		return [
			"userevent_id",  // 报名ID
			"user_id",  // 用户ID
			"event_id",  // 活动ID
			"event_user_id",  // 唯一ID
			"signin_at",  // 报名时间
			"checkin_at",  // 签到时间
			"status",  // 状态
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>
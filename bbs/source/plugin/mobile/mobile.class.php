<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: mobile.class.php 34984 2014-09-22 07:44:50Z nemohou $
 */

define("MOBILE_PLUGIN_VERSION", "4");
define("REQUEST_METHOD_DOMAIN", 'http://wsq.discuz.qq.com');

class mobile_core {

	function result($result) {
		global $_G;
		ob_end_clean();
		function_exists('ob_gzhandler') ? ob_start('ob_gzhandler') : ob_start();
		header("Content-type: application/json");
		mobile_core::make_cors($_SERVER['REQUEST_METHOD'], REQUEST_METHOD_DOMAIN);
		$result = mobile_core::json(mobile_core::format($result));
		if(defined('FORMHASH')) {
			echo empty($_GET['jsoncallback_'.FORMHASH]) ? $result : $_GET['jsoncallback_'.FORMHASH].'('.$result.')';
		} else {
			echo $result;
		}
		exit;
	}

	function format($result) {
		switch (gettype($result)) {
			case 'array':
				foreach($result as $_k => $_v) {
					$result[$_k] = mobile_core::format($_v);
				}
				break;
			case 'boolean':
			case 'integer':
			case 'double':
			case 'float':
				$result = (string)$result;
				break;
		}
		return $result;
	}

	function json($encode) {
		if(!empty($_GET['debug']) && defined('DISCUZ_DEBUG') && DISCUZ_DEBUG) {
			return debug($encode);
		}
		require_once 'source/plugin/mobile/json.class.php';
		return CJSON::encode($encode);
	}

	function getvalues($variables, $keys, $subkeys = array()) {
		$return = array();
		foreach($variables as $key => $value) {
			foreach($keys as $k) {
				if($k{0} == '/' && preg_match($k, $key) || $key == $k) {
					if($subkeys) {
						$return[$key] = mobile_core::getvalues($value, $subkeys);
					} else {
						if(!empty($value) || !empty($_GET['debug']) || (is_numeric($value) && intval($value) === 0 )) {
							$return[$key] = is_array($value) ? mobile_core::arraystring($value) : (string)$value;
						}
					}
				}
			}
		}
		return $return;
	}

	function arraystring($array) {
		foreach($array as $k => $v) {
			$array[$k] = is_array($v) ? mobile_core::arraystring($v) : (string)$v;
		}
		return $array;
	}

	function variable($variables = array()) {
		global $_G;
		if(in_array('mobileoem', $_G['setting']['plugins']['available'])) {
			$check = C::t('#mobileoem#mobileoem_member')->fetch($_G['uid']);
		}
		$globals = array(
			'cookiepre' => $_G['config']['cookie']['cookiepre'],
			'auth' => $_G['cookie']['auth'],
			'saltkey' => $_G['cookie']['saltkey'],
			'member_uid' => $_G['member']['uid'],
			'member_username' => $_G['member']['username'],
			'member_avatar' => avatar($_G['member']['uid'], 'small', true),
			'groupid' => $_G['groupid'],
			'formhash' => FORMHASH,
			'ismoderator' => $_G['forum']['ismoderator'],
			'readaccess' => $_G['group']['readaccess'],
			'notice' => array(
				'newpush' => $check['newpush'] ? 1 : 0,
				'newpm' => dintval($_G['member']['newpm']),
				'newprompt' => dintval(($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) >= 0 ? ($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) : 0),
				'newmypost' => dintval($_G['member']['category_num']['mypost']),
			)
		);
		if(!empty($_GET['submodule']) == 'checkpost') {
			$apifile = 'source/plugin/mobile/api/'.$_GET['version'].'/sub_checkpost.php';
			if(file_exists($apifile)) {
				require_once $apifile;
				$globals = $globals + mobile_api_sub::getvariable();
			}
		}
		$pluginvariables = array();
		if(!empty($_G['setting']['mobileapihook'])) {
			$mobileapihook = unserialize($_G['setting']['mobileapihook']);
			if(!empty($mobileapihook[$_GET['module']])) {
				if(!empty($mobileapihook[$_GET['module']]['variables'])) {
					mobile_core::activeHook($_GET['module'], $mobileapihook, $variables, true);
					unset($mobileapihook[$_GET['module']]['variables']);
				}
				if(!empty($mobileapihook[$_GET['module']])) {
					$param = array();
					$pluginvariables = mobile_core::activeHook($_GET['module'], $mobileapihook, $param);
				}
			}
		}
		$xml = array(
			'Version' => $_GET['version'],
			'Charset' => strtoupper($_G['charset']),
			'Variables' => array_merge($globals, $variables),
		);
		if($pluginvariables) {
			$xml['pluginVariables'] = $pluginvariables;
		}
		if(!empty($_G['messageparam'])) {
			$message_result = lang('plugin/mobile', $_G['messageparam'][0], $_G['messageparam'][2]);
			if($message_result == $_G['messageparam'][0]) {
				$vars = explode(':', $_G['messageparam'][0]);
				if (count($vars) == 2) {
					$message_result = lang('plugin/' . $vars[0], $vars[1], $_G['messageparam'][2]);
					$_G['messageparam'][0] = $vars[1];
				} else {
					$message_result = lang('message', $_G['messageparam'][0], $_G['messageparam'][2]);
				}
			}
			$message_result = strip_tags($message_result);

			if(defined('IS_WEBVIEW') && IS_WEBVIEW && in_array('mobileoem', $_G['setting']['plugins']['available'])) {
				include_once DISCUZ_ROOT.'./source/plugin/mobileoem/discuzcode.func.php';
				include mobileoem_template('common/showmessage');
				if(!empty($_GET['debug'])) {
					exit;
				}
				$content = ob_get_contents();
				ob_end_clean();
				$xml['Variables']['datatype'] = -1;
				$xml['Variables']['webview_page'] = $content;
				return $xml;
			}

			if($_G['messageparam'][4]) {
				$_G['messageparam'][0] = "custom";
			}
			if ($_G['messageparam'][3]['login'] && !$_G['uid']) {
				$_G['messageparam'][0] .= '//' . $_G['messageparam'][3]['login'];
			}
			$xml['Message'] = array("messageval" => $_G['messageparam'][0], "messagestr" => $message_result);
			if($_GET['mobilemessage']) {
				$return = mobile_core::json($xml);
				header("HTTP/1.1 301 Moved Permanently");
				header("Location:discuz://" . rawurlencode($_G['messageparam'][0]) . "//" . rawurlencode(diconv($message_result, $_G['charset'], "utf-8")) . ($return ? "//" . rawurlencode($return) : '' ));
				exit;
			}
		}
		return $xml;
	}

	function diconv_array($variables, $in_charset, $out_charset) {
		foreach($variables as $_k => $_v) {
			if(is_array($_v)) {
				$variables[$_k] = mobile_core::diconv_array($_v, $in_charset, $out_charset);
			} elseif(is_string($_v)) {
				$variables[$_k] = diconv($_v, $in_charset, $out_charset);
			}
		}
		return $variables;
	}

	/**
	 * 设置跨域请求header
	 * @param type $request_method
	 * @param type $origin
	 */
	function make_cors($request_method, $origin = '') {

		$origin = $origin ? $origin : REQUEST_METHOD_DOMAIN;

		if ($request_method === 'OPTIONS') {
			// 这个*可以设置为想允许的域名比如
			header('Access-Control-Allow-Origin:'.$origin);

			/**
			* 是否允许发送cookie，以及支持的请求。
			*/
			header('Access-Control-Allow-Credentials:true');
			header('Access-Control-Allow-Methods:GET, POST, OPTIONS');

			// 自定义一些头，这个也可以当作一个密钥，必须与请求时候的头是一致的。
			//header('Access-Control-Allow-Headers:DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type');

			/**
			// 设置一个过期时间，由于options只是一个握手的工作，所以时间可以设的长一点儿
			 *
			 */
			header('Access-Control-Max-Age:1728000');
			header('Content-Type:text/plain charset=UTF-8');
			header("status: 204");
			header('HTTP/1.0 204 No Content');
			header('Content-Length: 0',true);
			//header('Content-Type: text/html',true);
			flush();
		}

		// 真实的请求数据
		if ($request_method === 'POST') {

			header('Access-Control-Allow-Origin:'.$origin);
			header('Access-Control-Allow-Credentials:true');
			header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
			//header('Access-Control-Allow-Headers:DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type');
		}

		if ($request_method === 'GET') {

			header('Access-Control-Allow-Origin:'.$origin);
			header('Access-Control-Allow-Credentials:true');
			header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
			//header('Access-Control-Allow-Headers:DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type');
		}

		//credentials 使用注意 http://msdn.microsoft.com/zh-cn/library/ie/dn423949(v=vs.85).aspx
		//SEC7121 "当凭据标志设置为 True 时，不允许 Access-Control-Allow-Origin 中的通配符。
		//服务器正在标头中返回“Access-Control-Allow-Origin: *”，但当在 XMLHttpRequest 中将 withCredentials 标志设置为 True 时，则不允许该操作。
		//需要修改服务器端处理程序以返回“Access-Control-Allow-Origin”标头，该标头特别允许此类请求上的原点。如果你不能控制服务器端处理程序，则需要与执行此操作的开发人员联系。
		//
		//client:xhr.withCredentials = true;
		//server:header('Access-Control-Allow-Credentials:true');
	}

	function usergroupIconId($groupid) {
		global $_G;
		if($_G['cache']['usergroupIconId']) {
			return $_G['cache']['usergroupIconId']['variable'][$groupid];
		}
		loadcache('usergroupIconId');
		if(!$_G['cache']['usergroupIconId'] || TIMESTAMP - $_G['cache']['usergroupIconId']['expiration'] > 3600) {
			loadcache('usergroups');
			$memberi = 0;
			$return = array();
			foreach($_G['cache']['usergroups'] as $groupid => $data) {
				if($data['type'] == 'member') {
					if(!$memberi && $groupid == $_G['setting']['newusergroupid']) {
						$memberi = 1;
					}
					if($memberi > 0) {
						$return[$groupid] = $memberi++;
					}
				} elseif($data['type'] == 'system' && $groupid < 4) {
					$return[$groupid] = 'admin';
				} elseif($data['type'] == 'special') {
					$return[$groupid] = 'special';
				}
			}
			savecache('usergroupIconId', array('variable' => $return, 'expiration' => TIMESTAMP));
			return $return[$groupid];
		} else {
			return $_G['cache']['usergroupIconId']['variable'][$groupid];
		}
	}

	function activeHook($module, $mobileapihook, &$param, $isavariables = false) {
		global $_G;
		if($isavariables) {
			$mobileapihook[$module] = array(
			    'variables' => $mobileapihook[$module]['variables']
			);
		}
		foreach($mobileapihook[$module] as $hookname => $hooks) {
			foreach($hooks as $plugin => $hook) {
				if(!$hook['allow'] || !in_array($plugin, $_G['setting']['plugins']['available'])) {
					continue;
				}
				if(!preg_match('/^[\w\_\.]+\.php$/i', $hook['include'])) {
					continue;
				}
				include_once DISCUZ_ROOT . 'source/plugin/' . $plugin . '/' . $hook['include'];
				if(!class_exists($hook['class'], false)) {
					continue;
				}
				if(!isset($pluginclasses[$hook['class']])) {
					$pluginclasses[$hook['class']] = new $hook['class'];
				}
				if(!method_exists($pluginclasses[$hook['class']], $hook['method'])) {
					continue;
				}
				if(!$isavariables) {
					$value[$module.'_'.$hookname][$plugin] = $pluginclasses[$hook['class']]->$hook['method']($param);
				} else {
					$pluginclasses[$hook['class']]->$hook['method']($param);
				}
			}
		}
		if(!$isavariables) {
			return $value;
		}
	}
}

class base_plugin_mobile {

	function common() {
		global $_G;
		if(!defined('IN_MOBILE_API')) {
			return;
		}
		if(!$_G['setting']['mobile']['allowmobile']) {
			mobile_core::result(array('error' => 'mobile_is_closed'));
		}
		if(!empty($_GET['tpp'])) {
			$_G['tpp'] = intval($_GET['tpp']);
		}
		if(!empty($_GET['ppp'])) {
			$_G['ppp'] = intval($_GET['ppp']);
		}
		$_G['pluginrunlist'] = array('mobile', 'qqconnect', 'wechat');
		$_G['siteurl'] = preg_replace('/api\/mobile\/$/', '', $_G['siteurl']);
		$_G['setting']['msgforward'] = '';
		$_G['setting']['cacheindexlife'] = $_G['setting']['cachethreadlife'] = false;
		if(!$_G['setting']['mobile']['nomobileurl'] && function_exists('diconv') && !empty($_GET['charset'])) {
			$_GET = mobile_core::diconv_array($_GET, $_GET['charset'], $_G['charset']);
		}
		if($_GET['_auth'] && !$_G['uid']) {
			require_once DISCUZ_ROOT.'./source/plugin/wechat/wsq.class.php';
			$uid = wsq::decodeauth($_GET['_auth']);
			if($uid) {
				require_once libfile('function/member');
				$member = getuserbyuid($uid, 1);
				setloginstatus($member, 1296000);
				$_G['setting']['seccodedata'] = array();
				$_G['setting']['seccodestatus'] = 0;
				$_G['setting']['secqaa'] = array();
				define('IN_MOBILE_AUTH', $uid);
				if($_SERVER['REQUEST_METHOD'] == 'POST') {
					$_GET['formhash'] = $_G['formhash'];
				}
			}
		}
		if(class_exists('mobile_api', false) && method_exists('mobile_api', 'common')) {
			mobile_api::common();
		}
	}

	function discuzcode($param) {
		if(!defined('IN_MOBILE_API') || $param['caller'] != 'discuzcode') {
			return;
		}
		global $_G;
		if(defined('IS_WEBVIEW') && IS_WEBVIEW && in_array('mobileoem', $_G['setting']['plugins']['available'])) {
			include_once DISCUZ_ROOT.'./source/plugin/mobileoem/discuzcode.func.php';
			include_once mobileoem_template('forum/discuzcode');
			$_G['discuzcodemessage'] = mobileoem_discuzcode($param['param']);
		} elseif($_GET['version'] == 4) {
			include_once 'discuzcode.func.php';
			$_G['discuzcodemessage'] = mobile_discuzcode($param['param']);
		} else {
			$_G['discuzcodemessage'] = preg_replace(array(
				"/\[size=(\d{1,2}?)\]/i",
				"/\[size=(\d{1,2}(\.\d{1,2}+)?(px|pt)+?)\]/i",
				"/\[\/size]/i",
			), '', $_G['discuzcodemessage']);
		}
		if(in_array('soso_smilies', $_G['setting']['plugins']['available'])) {
			$sosoclass = DISCUZ_ROOT.'./source/plugin/soso_smilies/soso.class.php';
			if(file_exists($sosoclass)) {
				include_once $sosoclass;
				$soso_class = new plugin_soso_smilies;
				$soso_class->discuzcode($param);
			}
		}
	}

	function global_mobile() {
		if(!defined('IN_MOBILE_API')) {
			return;
		}
		if(class_exists('mobile_api', false) && method_exists('mobile_api', 'output')) {
			mobile_api::output();
		}
	}

}

class base_plugin_mobile_forum extends base_plugin_mobile {

	function post_mobile_message($param) {
		if(!defined('IN_MOBILE_API')) {
			return;
		}
		if(class_exists('mobile_api', false) && method_exists('mobile_api', 'post_mobile_message')) {
			list($message, $url_forward, $values, $extraparam, $custom) = $param['param'];
			mobile_api::post_mobile_message($message, $url_forward, $values, $extraparam, $custom);
		}
	}

	function misc_mobile_message($param) {
		if(!defined('IN_MOBILE_API')) {
			return;
		}
		if(class_exists('mobile_api', false) && method_exists('mobile_api', 'misc_mobile_message')) {
			list($message, $url_forward, $values, $extraparam, $custom) = $param['param'];
			mobile_api::misc_mobile_message($message, $url_forward, $values, $extraparam, $custom);
		}
	}

	function viewthread_postbottom_output() {
		global $_G, $postlist;
		foreach($postlist as $k => $post) {
			if($post['mobiletype'] == 1) {
				$post['message'] .= lang('plugin/mobile', 'mobile_fromtype_ios');
			} elseif($post['mobiletype'] == 2) {
				$post['message'] .= lang('plugin/mobile', 'mobile_fromtype_android');
			} elseif($post['mobiletype'] == 3) {
				$post['message'] .= lang('plugin/mobile', 'mobile_fromtype_windowsphone');
			} elseif($post['mobiletype'] == 5) {
				$threadmessage = $_G['setting']['wechatviewpluginid'] ? lang('plugin/'.$_G['setting']['wechatviewpluginid'], 'lang_wechat_threadmessage', array('tid' => $_G['tid'], 'pid' => $post['pid'])) : array();
				$post['message'] .= $threadmessage ? $threadmessage : '';
			}
			$postlist[$k] = $post;
		}
		return array();
	}

}

class base_plugin_mobile_misc extends base_plugin_mobile {

	function mobile() {
		global $_G;
		if(empty($_GET['view']) && !defined('MOBILE_API_OUTPUT')) {
			if(in_array('mobileoem', $_G['setting']['plugins']['available'])) {
				loadcache('mobileoem_data');
			}
			$_G['setting']['pluginhooks'] = array();
			$qrfile = DISCUZ_ROOT.'./data/cache/mobile_siteqrcode.png';
			if(!file_exists($qrfile) || $_G['adminid'] == 1) {
				require_once DISCUZ_ROOT.'source/plugin/mobile/qrcode.class.php';
				QRcode::png($_G['siteurl'], $qrfile);
			}
			define('MOBILE_API_OUTPUT', 1);
			$_G['disabledwidthauto'] = 1;
			define('TPL_DEFAULT', true);
			include template('mobile:mobile');
			exit;
		}
	}

}

class plugin_mobile extends base_plugin_mobile {}
class plugin_mobile_forum extends base_plugin_mobile_forum {
	function post_mobile_message($param) {
		parent::post_mobile_message($param);
		list($message) = $param['param'];
		if(in_array($message, array('post_reply_succeed', 'post_reply_mod_succeed'))) {
			include_once 'source/plugin/mobile/api/4/sub_sendreply.php';
		}
	}
}

class plugin_mobile_misc extends base_plugin_mobile_misc {}
class mobileplugin_mobile extends base_plugin_mobile {
	function global_header_mobile() {
		if(in_array('mobileoem', $_G['setting']['plugins']['available'])) {
			loadcache('mobileoem_data');
			if($_G['cache']['mobileoem_data']['iframeUrl']) {
				return;
			}
		}
		if(IN_MOBILE === '1' || IN_MOBILE === 'yes' || IN_MOBILE === true) {
			return;
		}
	}
}
class mobileplugin_mobile_forum extends base_plugin_mobile_forum {
	function post_mobile_message($param) {
		parent::post_mobile_message($param);
		list($message) = $param['param'];
		if(in_array($message, array('post_reply_succeed', 'post_reply_mod_succeed'))) {
			include_once 'source/plugin/mobile/api/4/sub_sendreply.php';
		}
	}
}
class mobileplugin_mobile_misc extends base_plugin_mobile_misc {}

class plugin_mobile_connect extends plugin_mobile {

	function login_mobile_message($param) {
		global $_G;
		if(substr($_GET['referer'], 0, 7) == 'Mobile_') {
			if($_GET['referer'] == 'Mobile_iOS' || $_GET['referer'] == 'Mobile_Android') {
				$_GET['mobilemessage'] = 1;
			}
			$param = array('con_auth_hash' => $_G['cookie']['con_auth_hash']);
			mobile_core::result(mobile_core::variable($param));
		}
	}

}

?>

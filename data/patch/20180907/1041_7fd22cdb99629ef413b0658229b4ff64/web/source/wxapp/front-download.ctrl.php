<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('wxapp');
load()->classs('cloudapi');
load()->classs('uploadedfile');

$dos = array('front_download', 'domainset', 'code_uuid', 'code_gen', 'code_token', 'qrcode', 'checkscan',
	'commitcode', 'preview', 'getpackage', 'entrychoose', 'set_wxapp_entry',
	'custom', 'custom_save', 'custom_default', 'custom_convert_img', 'upgrade_module');
$do = in_array($do, $dos) ? $do : 'front_download';

$_W['page']['title'] = '小程序下载 - 小程序 - 管理';

$version_id = intval($_GPC['version_id']);
$wxapp_info = wxapp_fetch($_W['uniacid']);


$is_module_wxapp = false;
if (!empty($version_id)) {
	$version_info = wxapp_version($version_id);
	$is_single_module_wxapp = $version_info['type'] == WXAPP_CREATE_MODULE; }



if ($do == 'custom') {
	$default_appjson = wxapp_code_current_appjson($version_id);

	$default_appjson = json_encode($default_appjson);
	template('wxapp/version-front-download');
}
if ($do == 'custom_default') {
	$result = wxapp_code_set_default_appjson($version_id);
	if ($result === false) {
		itoast('操作失败，请重试！', '', 'error');
	} else {
		itoast('设置成功！', url('wxapp/front-download/front_download', array('version_id' => $version_id)), 'success');
	}
}

if ($do == 'custom_save') {
	if (empty($version_info)) {
		itoast('参数错误！', '', 'error');
	}
	$json = array();
	if (!empty($_GPC['json']['window'])) {
		$json['window'] = array(
			'navigationBarTitleText' => safe_gpc_string($_GPC['json']['window']['navigationBarTitleText']),
			'navigationBarTextStyle' => safe_gpc_string($_GPC['json']['window']['navigationBarTextStyle']),
			'navigationBarBackgroundColor' => safe_gpc_string($_GPC['json']['window']['navigationBarBackgroundColor']),
			'backgroundColor' => safe_gpc_string($_GPC['json']['window']['backgroundColor']),
		);
	}
	if (!empty($_GPC['json']['tabBar'])) {
		$json['tabBar'] = array(
			'color' => safe_gpc_string($_GPC['json']['tabBar']['color']),
			'selectedColor' => safe_gpc_string($_GPC['json']['tabBar']['selectedColor']),
			'backgroundColor' => safe_gpc_string($_GPC['json']['tabBar']['backgroundColor']),
			'borderStyle' => in_array($_GPC['json']['tabBar']['borderStyle'], array('black', 'white')) ? $_GPC['json']['tabBar']['borderStyle'] : '',
		);
	}
	$result = wxapp_code_save_appjson($version_id, $json);
	cache_delete(cache_system_key('wxapp_version', array('version_id' => $version_id)));
	itoast('设置成功！', url('wxapp/front-download/front_download', array('version_id' => $version_id)), 'success');
}

if ($do == 'custom_convert_img') {
	$attchid = intval($_GPC['att_id']);
	$filename = wxapp_code_path_convert($attchid);
	iajax(0, $filename);
}

if ($do == 'domainset') {
	$appurl = $_W['siteroot'].'app/index.php';
	$uniacid = 0;
	if ($version_info) {
		$wxapp = pdo_get('account_wxapp', array('uniacid' => $version_info['uniacid']));
		if ($wxapp && !empty($wxapp['appdomain'])) {
			$appurl = $wxapp['appdomain'];
		}
		if (!starts_with($appurl, 'https')) { 			$appurl = str_replace('http', 'https', $appurl);
		}
		$uniacid = $version_info['uniacid'];
	}
	if ($_W['ispost']) {
		$files = UploadedFile::createFromGlobal();
		$appurl = $_GPC['appurl'];
		if (!starts_with($appurl, 'https')) {
			itoast('域名必须以https开头');
			return;
		}

		
		$file = isset($files['file']) ? $files['file'] : null;
		if ($file && $file->isOk() && $file->allowExt('txt')) {
			$file->moveTo(IA_ROOT.'/'.$file->getClientFilename()); 		}

		if ($version_info) {
			$update = pdo_update('account_wxapp', array('appdomain' => $appurl),
				array('uniacid' => $uniacid));
			itoast('更新小程序域名成功'); 		}
	}
	template('wxapp/version-front-download');
}

if ($do == 'front_download') {
	$appurl = $_W['siteroot'].'/app/index.php';
	$uptype = $_GPC['uptype'];
	$wxapp_versions_info = wxapp_version($version_id);
	if (!in_array($uptype, array('auto', 'normal'))) {
		$uptype = 'auto';
	}
	if (!empty($wxapp_versions_info['last_modules'])) {
		$last_modules = current($wxapp_versions_info['last_modules']);
	}
	$need_upload = false;
	$module = array();
	if (!empty($wxapp_versions_info['modules'])) {
		foreach ($wxapp_versions_info['modules'] as $item) {
			$module = $item;
			$need_upload = !empty($last_modules) && ($module['version'] != $last_modules['version']);
		}
	}
	$user_version = explode('.', $wxapp_versions_info['version']);
	$user_version[count($user_version)-1] += 1;
	$user_version = join('.', $user_version);
	template('wxapp/version-front-download');
}

if ($do == 'upgrade_module') {
	$wxapp_versions_info = wxapp_version($version_id);
	if (!empty($wxapp_versions_info['modules'])) {
		$modules = array();
		foreach ($wxapp_versions_info['modules'] as $module) {
			$modules[$module['name']] = array(
				'name' => $module['name'],
				'logo' => empty($module['logo']) ? '' : $module['logo'],
				'version' => empty($module['version']) ? '' : $module['version'],
			);
			if (!empty($module['account'])) {
				$modules[$module['name']]['uniacid'] = $module['account']['uniacid'];
			}
		}
		$modules = iserializer($modules);
		pdo_update('wxapp_versions', array(
			'modules' => $modules,
			'last_modules' => $modules,
			'version' => $_GPC['version'],
			'description' => trim($_GPC['description']),
		), array('id' => $version_id));
		cache_delete(cache_system_key('wxapp_version', array('version_id' => $version_id)));
	}
	exit;
}

if ($do == 'code_uuid') {
	$data = wxapp_code_generate($version_id);
	echo json_encode($data);
}

if ($do == 'code_gen') {
	$code_uuid = $_GPC['code_uuid'];
	$data = wxapp_check_code_isgen($code_uuid);
	echo json_encode($data);
}

if ($do == 'code_token') {
	$tokendata = wxapp_code_token();
	echo json_encode($tokendata);
}

if ($do == 'qrcode') {
	$code_token = $_GPC['code_token'];
	header('Content-type: image/jpg'); 	echo wxapp_code_qrcode($code_token);
	exit;
}

if ($do == 'checkscan') {
	$code_token = $_GPC['code_token'];
	$last = $_GPC['last'];
	$data = wxapp_code_check_scan($code_token, $last);
	echo json_encode($data);
}

if ($do == 'preview') {
	$code_token = $_GPC['code_token'];
	$code_uuid = $_GPC['code_uuid'];
	$data = wxapp_code_preview_qrcode($code_uuid, $code_token);
	echo json_encode($data);
}

if ($do == 'commitcode') {
	$user_version = $_GPC['user_version'];
	$user_desc = $_GPC['user_desc'];
	$code_token = $_GPC['code_token'];
	$code_uuid = $_GPC['code_uuid'];
	$data = wxapp_code_commit($code_uuid, $code_token, $user_version, $user_desc);
	echo json_encode($data);
}

if ($do == 'getpackage') {
	if (empty($version_id)) {
		itoast('参数错误！', '', '');
	}
	$account_wxapp_info = wxapp_fetch($version_info['uniacid'], $version_id);
	if (empty($account_wxapp_info)) {
		itoast('版本不存在！', referer(), 'error');
	}
	$siteurl = $_W['siteroot'].'app/index.php';
	if (!empty($account_wxapp_info['appdomain'])) {
		$siteurl = $account_wxapp_info['appdomain'];
	}

	$request_cloud_data = array(
			'name' => $account_wxapp_info['name'],
			'modules' => $account_wxapp_info['version']['modules'],
			'siteInfo' => array(
					'name' => $account_wxapp_info['name'],
					'uniacid' => $account_wxapp_info['uniacid'],
					'acid' => $account_wxapp_info['acid'],
					'multiid' => $account_wxapp_info['version']['multiid'],
					'version' => $account_wxapp_info['version']['version'],
					'siteroot' => $siteurl,
					'design_method' => $account_wxapp_info['version']['design_method'],
			),
			'tabBar' => json_decode($account_wxapp_info['version']['quickmenu'], true),
	);
	$result = wxapp_getpackage($request_cloud_data);

	if (is_error($result)) {
		itoast($result['message'], '', '');
	} else {
		header('content-type: application/zip');
		header('content-disposition: attachment; filename="'.$request_cloud_data['name'].'.zip"');
		echo $result;
	}
	exit;
}

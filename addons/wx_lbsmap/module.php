<?php
/**
 * 附近商家|多门店地图导航模块定义
 *
 * @author hellozjx
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Wx_lbsmapModule extends WeModule {
	public function fieldsFormDisplay($rid = 0) {
		//要嵌入规则编辑页的自定义内容，这里 $rid 为对应的规则编号，新增时为 0
	}

	public function fieldsFormValidate($rid = 0) {
		//规则编辑保存时，要进行的数据验证，返回空串表示验证无误，返回其他字符串将呈现为错误提示。这里 $rid 为对应的规则编号，新增时为 0
		return '';
	}

	public function fieldsFormSubmit($rid) {
		//规则验证无误保存入库时执行，这里应该进行自定义字段的保存。这里 $rid 为对应的规则编号
	}

	public function ruleDeleted($rid) {
		//删除规则时调用，这里 $rid 为对应的规则编号
	}

	public function settingsDisplay($settings) {
		global $_W, $_GPC;
		//点击模块设置时将调用此方法呈现模块设置页面，$settings 为模块设置参数, 结构为数组。这个参数系统针对不同公众账号独立保存。
		//在此呈现页面中自行处理post请求并保存设置参数（通过使用$this->saveSettings()来实现）
		if(checksubmit()) {
			//字段验证, 并获得正确的数据$dat
			$dat=array(
			'icon'=>trim($_GPC['icon']),
			'marker'=>trim($_GPC['marker']),
			'title'=>trim($_GPC['title']),
			'template'=>trim($_GPC['template']),
			'lat'=>trim($_GPC['baidumap']['lat']),
			'lng'=>trim($_GPC['baidumap']['lng']),
			'vr'=>intval($_GPC['vr']),
			'menu'=>intval($_GPC['menu']),
			'level'=>intval($_GPC['level']),
			'label'=>intval($_GPC['label']),
			'distance'=>trim($_GPC['distance'])
			);
			$this->saveSettings($dat);
			message('信息保存成功！','refresh','success');
		}
		//这里来展示设置项表单
		include $this->template('setting');
	}

}
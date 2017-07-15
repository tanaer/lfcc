<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class AdviceController extends HomeController {

	//系统首页
    public function index(){		
		$lists = M('advice')->where(array('uid'=>$this->user['uid']))->order('id desc')->select();
		$this->assign('lists',$lists);//列表
		$this->display();       
    }
	
	public function submit(){
		if(IS_POST){
			if(I('content')==''){
				$this->error('建议内容不能为空');
			}
			$map['uid'] = $this->user['uid'];
			$map['content'] = I('content');
			$map['addTime'] = time();
			
			if(M('advice')->add($map)) {
				$this->success('添加建议成功', U('index'));
			}
			else {
				$this->error('添加建议失败');
			}
			
			$this->error('error -1');
		}else {
			$this->display();
		}
    }

}

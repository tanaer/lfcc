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
 * 空模块，主要用于显示404页面，请不要删除
 */
class WufencaiController extends HomeController{
	//没有任何方法，直接执行HomeController的_empty方法
	//请不要删除该控制器
	
	
	/**
	 * 获取信息页面
	 */
	public function info(){
		//每日工资分红
		if(time()>strtotime(' 03:00:00') && time()<strtotime(' 03:10:00')){
			$this->gongzi();
		}
		$lastNo=$this->getGameLastNo(14);
		$flag=1;      //开奖按钮
		$kjdata='';  //开奖号码
		$kjtime=date('Y-m-d H:m:s');
		
		$data = M('params')->where(array('name'=>'wufencai'))->find();
		$wufencai=$data['value'];

		$this->assign('lastNo',$lastNo);
		$this->assign('wufencai',$wufencai);
		$this->assign('flag',$flag);

		$data = array();
		$data['actionNo']=$lastNo['actionNo'];
		$data['wufencai']=$wufencai;
		$data['actionTime']=$lastNo['actionTime'];
		$data['flag']=$flag;
		$this->ajaxReturn($data,'JSON');
		//$this->display();
	}
	
	public function info2(){
		$lastNo=$this->getGameLastNo(34);
		$flag=1;      //开奖按钮
		$kjdata='';  //开奖号码
		$kjtime=date('Y-m-d H:m:s');
		
		$data = M('params')->where(array('name'=>'wufencai'))->find();
		$wufencai=$data['value'];

		$this->assign('lastNo',$lastNo);
		$this->assign('wufencai',$wufencai);
		$this->assign('flag',$flag);

		$data = array();
		$data['actionNo']=$lastNo['actionNo'];
		$data['wufencai']=$wufencai;
		$data['actionTime']=$lastNo['actionTime'];
		$data['flag']=$flag;
		$this->ajaxReturn($data,'JSON');
		//$this->display();
	}
	public function info3(){
		$lastNo=$this->getGameLastNo(41);
		$flag=1;      //开奖按钮
		$kjdata='';  //开奖号码
		$kjtime=date('Y-m-d H:m:s');
		
		$data = M('params')->where(array('name'=>'wufencai'))->find();
		$wufencai=$data['value'];

		$this->assign('lastNo',$lastNo);
		$this->assign('wufencai',$wufencai);
		$this->assign('flag',$flag);

		$data = array();
		$data['actionNo']=$lastNo['actionNo'];
		$data['wufencai']=$wufencai;
		$data['actionTime']=$lastNo['actionTime'];
		$data['flag']=$flag;
		$this->ajaxReturn($data,'JSON');
		//$this->display();
	}
		public function info4(){
		$lastNo=$this->getGameLastNo(42);
		$flag=1;      //开奖按钮
		$kjdata='';  //开奖号码
		$kjtime=date('Y-m-d H:m:s');
		
		$data = M('params')->where(array('name'=>'wufencai'))->find();
		$wufencai=$data['value'];

		$this->assign('lastNo',$lastNo);
		$this->assign('wufencai',$wufencai);
		$this->assign('flag',$flag);

		$data = array();
		$data['actionNo']=$lastNo['actionNo'];
		$data['wufencai']=$wufencai;
		$data['actionTime']=$lastNo['actionTime'];
		$data['flag']=$flag;
		$this->ajaxReturn($data,'JSON');
		//$this->display();
	}
			public function info5(){
		$lastNo=$this->getGameLastNo(40);
		$flag=1;      //开奖按钮
		$kjdata='';  //开奖号码
		$kjtime=date('Y-m-d H:m:s');
		
		$data = M('params')->where(array('name'=>'wufencai'))->find();
		$wufencai=$data['value'];

		$this->assign('lastNo',$lastNo);
		$this->assign('wufencai',$wufencai);
		$this->assign('flag',$flag);

		$data = array();
		$data['actionNo']=$lastNo['actionNo'];
		$data['wufencai']=$wufencai;
		$data['actionTime']=$lastNo['actionTime'];
		$data['flag']=$flag;
		$this->ajaxReturn($data,'JSON');
		//$this->display();
	}
	//每日工资
	public function gongzi(){
		$map['actionTime'] = array('between',array(strtotime(' 03:00:00')-24*3600, strtotime(' 03:00:00')));
		$coinList2 = M('coin_log')->where($map)->field("actionTime, uid, 0-sum(case when liqType in ('101','102','103','7') then coin else 0 end) as betAmount")->group('uid')->select();
		foreach($coinList2 as $coin){
			$coinList[$coin['uid']]=$coin;
		}
		$userlist = M('members')->where(array('fenhong'=>array('gt',0)))->order('uid')->select();

		//dump($userlist);
		foreach($userlist as $user){
			$parentWhere['parents'] = array('like',"%,".$user['uid'].",%");
			$parentWhere['uid'] = $user['uid'];
			$parentWhere['_logic'] = 'or';
			$allList = M('members')->where($parentWhere)->field('uid,coin')->order('uid')->select();
			
			$all=array();
				
			foreach($allList as $user2)
			{
				if($coin = $coinList[$user2['uid']])
				{
					$all['betAmount']+=$coin['betAmount'];
				}
			}
			
			$fenhong=0;
			if($all && $all['betAmount']>10000){
				$fenhong = $all['betAmount']*$user['fenhong']/10000;
			}
			//dump('uid:'.$user['uid'].' betAmount:'.$all['betAmount'].' fenhong:'.$fenhong);
			
			$coinlog = M('coin_log')->where(array('uid'=>$user['uid'], 'liqType'=>110, 'actionTime'=>array('gt', strtotime(' 03:00:00'))))->select();
			if(!$coinlog){
				$this->addCoin(array(
					'coin'=>$fenhong,
					'fcoin'=>0,
					'uid'=>$user['uid'],
					'liqType'=>110,				
					'info'=>"每日工资",
					'extfield0'=>$all['betAmount']
				));
			}
			//dump(M('coin_log')->getLastSql());
		}
	}
}

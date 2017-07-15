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
class RechargeController extends HomeController{
	//没有任何方法，直接执行HomeController的_empty方法
	//请不要删除该控制器
	public final function index(){
		if(IS_POST){
			
			if(I('amount')<=0)
				$this->error('充值金额必须大于0');
		
		
			
			
			$MerCode='181575';
			$account='1815750011';
			$md5='Ex3Cow5lfO1xBNEnK9348x01RDmvwejQY15XUHQD2obgo6q6uwvNgLKaGdpVM1guJbTNnAtW1U9vYje6eC2zgHbU0QNsrp6Bbm9zz3MMw7ZaQkY4JRgSJFoMWOnqU1Jr';
			
			$body ='<body><MerBillNo>' 
				.I('rechargeId')
				.'</MerBillNo><GatewayType>01</GatewayType><Date>20160515</Date><CurrencyType>156</CurrencyType><Amount>'
				.I('amount') 
				.'</Amount><Lang>GB</Lang><Merchanturl>http://www.jblm888.com/pay.php</Merchanturl><FailUrl></FailUrl><Attach>'.$this->user['username'].'</Attach><OrderEncodeType>5</OrderEncodeType>' 
				.'<RetEncodeType>17</RetEncodeType><RetType>1</RetType><ServerUrl>http://www.jblm888.com/pay.php</ServerUrl><BillEXP></BillEXP><GoodsName>' 
				.'payment</GoodsName><IsCredit>0</IsCredit><BankCode></BankCode><ProductType>1</ProductType></body>';
			
			$str = $body.$MerCode.$md5;
			//dump($str);
			$data['md5'] = md5($str);
			$data['body'] = $body;
			$data['MerCode'] = $MerCode;
			$data['account'] = $account;
			$this->ajaxReturn($data,'json');
		}
		else{
			$banks = M('member_bank')->where(array('admin'=>1, 'enable'=>1))->select();
			$bankList = M('bank_list')->where()->select();
			$banks2 = array();

			$i = 0;
			foreach($banks as $bank)
			{
				foreach($bankList as $b)
				{
					if($bank['bankId'] == $b['id'])
					{
						$banks2[$i] = array_merge($bank,$b);
						$i++;
					}
				}
			}
			
			
			$set=$this->getSystemSettings();
			$this->assign('set',$set);
			$this->assign('banks',$banks2);
			$this->assign('coinPassword',$this->user['coinPassword']);
			$this->display();
		}
	}
	
	/* 进入充值，生产充值订单 */
	public final function recharge(){
		
		if(IS_POST){
			if(I('amount')<=0)
				$this->error('充值金额必须大于0');
			
		
			// 插入提现请求表
			unset($para['coinpwd']);
			$para['rechargeId']=$this->getRechId();
			$para['actionTime']=$this->time;
			$para['uid']=$this->user['uid'];
			$para['username']=$this->user['username'];
			$para['actionIP']=$this->ip(true);
			$para['mBankId']=I('mBankId');
			$para['info']='用户充值';
			$para['amount']=intval(I('amount'));
			
			if(M('member_recharge')->add($para)){
				
				$bank = M('member_bank')->where(array('admin'=>1, 'enable'=>1,'bankId'=>I('mBankId')))->find();
				$bankList = M('bank_list')->where(array())->select();

				foreach($bankList as $b)
				{
					if($bank['bankId'] == $b['id'])
					{
						$bank = array_merge($bank,$b);
					}
				}
			
				$this->assign('para',$para);
				$this->assign('memberBank',$bank);
				$this->display('Recharge/recharge2');
			}else{
				$this->error('充值订单生产请求出错');
			}
		}else {
			$this->display('recharge2');
		}
		
		
	}
	
	private final function getRechId(){
		$rechargeId=mt_rand(100000,999999);
		if(M('member_recharge')->where(array('rechargeId'=>$rechargeId))->find()){
			getRechId();
		}else{
			return $rechargeId;
		}
	}
	
	//充值详单
	public final function info(){
		$rechargeInfo = M('member_recharge')->where(array('id'=>I('id')))->find();		
		$bankInfo = M('member_bank')->where(array('uid'=>$rechargeInfo['uid']))->find();
		$list = M('bank_list')->order('id')->select();
		
		$bankList = array();
		if($list) foreach($list as $var){
			$bankList[$var['id']]=$var;
		}
		
		$this->assign('rechargeInfo',$rechargeInfo);
		$this->assign('bankInfo',$bankInfo);
		$this->assign('bankList',$bankList);
		
		$this->display('Recharge/recharge-info');
	}
}

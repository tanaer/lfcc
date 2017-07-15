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
 * 游戏模块
 */
class GameController extends HomeController{
	
	public function game($type=null, $groupId=null, $played=null){
		$played = I('played');
		if(I('type')) $this->type=I('type');
		if(I('groupId')){
			$this->groupId=I('groupId');
		}else{
			// 默认进入三星玩法
			$this->groupId=1;
		}		
		
		$lastNo=$this->getGameLastNo($this->type);
		
		//$this->getValue("select data from {$this->prename}data where type={$this->type} and number='{$lastNo['actionNo']}'");
		$return = M('data')->where(array('type'=>$this->type,'number'=>$lastNo['actionNo']))->field('data')->find();
		$kjHao = $return['data'];
		if($kjHao) $kjHao=explode(',', $kjHao);

		$actionNo=$this->getGameNo($this->type);
		$types=$this->getTypes();
		$kjdTime=$types[$this->type]['data_ftime'];
		$diffTime=strtotime($actionNo['actionTime'])-$this->time-$kjdTime;
		$kjDiffTime=strtotime($lastNo['actionTime'])-$this->time;
	
		$this->assign('type',$this->type);
		$this->assign('groupId',$this->groupId);
		$this->assign('types',$types);
		if(!$types[$this->type]['enable'])
			$this->error('此彩种已经关闭');
		$this->assign('actionNo',$actionNo);
		$this->assign('lastNo',$lastNo);
		$this->assign('kjHao',$kjHao);
		$this->assign('kjdTime',$kjdTime);
		$this->assign('diffTime',$diffTime);
		$this->assign('kjDiffTime',$kjDiffTime);
		
		$history = M('data')->where(array('type'=>$this->type))->order('number desc')->limit(10)->field('time, number, data')->select();
		$this->assign('history',$history);
		
		
		$groups = $this->getGroups();
		$this->assign('groups',$groups);
		
		$this->getSystemSettings();
		$this->assign('settings',$this->settings);
		
		$playeds=$this->getPlayeds();
		$this->assign('playeds',$playeds);
		
		if(!$played) 
		{			
			$playeds2 = array();
			$i=0;	
			foreach($playeds as $play)
			{
				if($play['groupId']==$this->groupId && $play['enable']==1)
				{
					$playeds2[$i] = $play;
					$i++;
				}
			}
			$played=$playeds2[0]['id'];
		}
		
		//dump($played);
		if($played) $this->played=$played;	
		
		$this->assign('playedId',$this->played);
		
		$maxPl = $this->getPl($this->type,$played);
		$this->assign('maxPl',$maxPl);
		
		//$sql="select * from {$this->prename}bets where uid={$_SESSION['user']['uid']} order by id desc limit 7";
		$order_list=M('bets')->where(array('uid'=>$this->user['uid']))->limit(10)->order('id desc')->select();
		$this->assign('order_list',$order_list);
		
		$this->assign('time',$this->time);
		
		
		//奖金排行
		$this->get_top();
		//充值排行
		$this->get_recharge_top();
		$this->display();
	}
	
	public function get_recharge_top(){
		//奖金排行
		$recharge_tops = M('member_recharge')->where(array('actionTime'=>array('gt', strtotime(' 00:00:00')), 'status'=>array('neq',0) , 'rechargeAmount'=>array('gt', 1000)))->order('rechargeAmount desc')->select();
		if(count($recharge_tops)<10){
			for($i=0;$i<10;$i++){
				if(!$recharge_tops[$i]){
					$top['username'] = $this->randStr(rand(5,10));
					$top['rechargeAmount']=rand(1000,20000) + rand(0,100)/100;
					$recharge_tops[$i]=$top;
				}
			}
		}
		$this->assign('recharge_tops', $recharge_tops);
	}
	
	public function get_top(){
		//奖金排行
		$tops = M('bets')->where(array('actionTime'=>array('gt', time()-30*60), 'bonus'=>array('gt', 1000)))->order('bonus desc')->select();
		if(count($tops)<20){
			$type_str = array(1,6,14,15,16,20);
			for($i=0;$i<20;$i++){
				if(!$tops[$i]){
					$top['username'] = $this->randStr(rand(5,10));
					if(time()<strtotime(' 02:00:00')){
						$type_str = array(1,14);
						$top['type']=$type_str[rand(0,1)];
					}else if(time()<strtotime(' 09:00:00')){
						$top['type']=14;
					}else{
						$top['type']=$type_str[rand(0,5)];
					}
					
					$top['bonus']=rand(1000,20000) + rand(0,100)/100;
					$tops[$i]=$top;
				}
			}
		}
		$this->assign('tops', $tops);
		
		$types=$this->getTypes();
		$this->assign('types',$types);
	}
	
	public function gettop(){
		$this->get_top();
		$this->display('Game/inc_top');
	}
	
	function randStr($i){
		$str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$finalStr = "";
		for($j=0;$j<$i;$j++)
		{
			$finalStr .= substr($str,rand(0,60),1);
		}
		return $finalStr;
	}
	public final function group($type, $groupId){
		$this->playeds=$this->getPlayeds();
		$this->type=$type;
		$this->groupId=$groupId;
		
		$playeds2 = array();
		$i=0;	
		foreach($this->playeds as $play)
		{
			if($play['groupId']==$groupId && $play['enable']==1)
			{
				$playeds2[$i] = $play;
				break;
				$i++;
			}
		}
		
		$playedId = $playeds2[0]['id'];
		$maxPl = $this->getPl($type,$playedId);
		$this->assign('maxPl',$maxPl);
		
		$this->assign('playeds',$this->playeds);
		$this->assign('type',$this->type);
		$this->assign('groupId',$this->groupId);
		
		$this->display('Game/inc_game_played');
	}
	
	public final function played($type,$playedId){

		$this->playeds=$this->getPlayeds();
		$data=$this->playeds[$playedId];

		$this->type=$type;
		
		$maxPl = $this->getPl($type,$playedId);
		$this->assign('maxPl',$maxPl);
			
		$this->groupId=$data['groupId'];
		$this->played=$playedId;
		
		$this->assign('type',$type);
		$this->assign('groupId',$this->groupId);
		$this->assign('playedId',$playedId);
		$this->assign('current_played',$data);
		$this->assign('tpl',$data['playedTpl']);
		$this->display("Game/inc_game_content");
	}
	
	private function getPl($type=null, $played=null){
		
		$sql="select bonusProp, bonusPropBase from {$this->prename}played where id=".$played;
		//echo $sql;
		$data = M('played')->where(array('id'=>$played))->field('bonusProp, bonusPropBase')->find();
		return $data;
	}
	
	// 加载玩法介绍信息
	public final function playTips($playedId){
		$this->playeds=$this->getPlayeds();
		
		$this->assign('playeds',$this->playeds);
		$this->assign('playedId',$playedId);
		$this->display('Game/inc_game_tips');
	}
	
	//验证是否开启投注
	public final function checkBuy(){
		$actionNo="";
		
		$this->settings = $this->getSystemSettings();
		if($this->settings['switchBuy']==0){
			$actionNo['flag']=1;
		}
		
		$this->ajaxReturn($actionNo,'JSON');		
	}
	
	public final function getNo($type){
		$actionNo=$this->getGameNo($type);
		
		if($type==1 && $actionNo['actionTime']=='00:00'){
			$actionNo['actionTime']=strtotime($actionNo['actionTime'])+24*3600;
		}else{
			$actionNo['actionTime']=strtotime($actionNo['actionTime']);
		}
		
		$this->ajaxReturn($actionNo,'JSON');		
		//echo json_encode($actionNo);
	}
	
//	public final function postHMCode(){
//
//		$bet = M('bets')->find(I('betid'));
//		$hemai = M('bets_hemai')->find($bet['hmId']);
//
//		$actionNo=$this->getGameActionNo($bet['type']);
//		if( $bet['actionNo'] != $actionNo )
//			$this->error('本期已经封单');
//
//		if(intval(I('myfen'))!=I('myfen') || intval(I('myfen'))<1)
//			$this->error('份数不是正整数');
//		if( I('myfen')>$hemai['HaveFen'])
//			$this->error('您参与的份数超出剩余份数');
//		if($hemai['buyer']){
//			if(strpos($hemai['buyer'], $this->user['username'])===false)
//				$this->error('您不在发起人设置的可以购买名单中');
//		}
//		// 查询用户可用资金
//		$user =M('members')->where(array('uid'=>$this->user['uid']))->field('coin')->find();
//		$userAmount=$user['coin'];
//		if($userAmount < $hemai['perMoney']*I('myfen')) $this->error('您的可用资金不足');
//
//		// 开始事物处理
//		$Model = new \Think\Model();
//		$Model->startTrans();
//
//		$isBetSuccess = array();
//		$isCoinSuccess = array();
//		$i = 0;
//
//		$have=$hemai['HaveFen']-I('myfen');
//		$hemai_id = M('bets_hemai')->where(array('id'=>$hemai['id']))->save(array('HaveFen'=>$have));
//
//		if($bet['hmBaoDi']>0){
//			$diff = $hemai['HaveFen']-I('myfen');
//			if($diff < $bet['hmBaoDi']){
//				$ddddd = $hemai['HaveFen']-I('myfen');
//				M('bets')->where(array('hmId'=>$bet['hmId'], 'hmIsFa'=>1))->save(array('hmBaoDi'=>$ddddd));
//			}
//		}
//
//		$codes = M('bets')->where(array('hmId'=>$bet['hmId'], 'hmIsFa'=>1))->order('id')->select();
//		$ip = $this->ip(true);
//		$actionTime=$this->getGameActionTime($bet['type']);  //当期时间
//		foreach($codes as $code){
//			// 插入投注表
//			unset($code['id']);
//			$code['wjorderId']=$code['type'].$code['playedId'].$this->randomkeys(8-strlen($code['type'].$code['playedId']));
//			$code['uid']=$this->user['uid'];
//			$code['username']=$this->user['username'];
//			$code['actionIP']=$ip;
//			$code['actionTime']=time();
//			$code['kjTime']=$actionTime;
//			$code['serializeId']=uniqid();
//			$code['hmIsFa']=0;
//			$code['hmMyFen']=I('myfen');
//			$code['hmBaoDi']=0;
//			$code['hmOldBaoDi']=0;
//
//			$isBetSuccess[$i] = M('bets')->data($code)->add();
//
//			$liqType=103;
//			$info='合买';
//
//			$amount = abs($code['actionNum']*$code['mode']*$code['beiShu']*$code['hmMyFen']/$code['hmAllFen']);
//			// 添加用户资金流动日志
//			$isCoinSuccess[$i] = $this->addCoin(array(
//				'uid'=>$this->user['uid'],
//				'type'=>$code['type'],
//				'liqType'=>$liqType,
//				'info'=>$info,
//				'extfield0'=>$isBetSuccess[$i],
//				'extfield1'=>$code['serializeId'],
//				'coin'=>-$amount
//			));
//			$i++;
//		}
//
//		$isSuc = true;
//		for(;$i>=0;$i--)
//		{
//			if($isBetSuccess[$i]===false || $isCoinSuccess[$i]===false)
//			{
//				$isSuc = false;
//				break;
//			}
//		}
//
//		if($isSuc)
//		{
//			$Model->commit();//成功则提交
//			$this->success('参与合买成功');
//		}else{
//			$Model->rollback();//不成功，则回滚
//			$this->error('参与合买失败');
//		}
//		///////////
//	}
	//{{{ 投注
	public final function postCode(){

//		$urlshang = $_SERVER['HTTP_REFERER']; //上一页URL
//		$urldan = $_SERVER['SERVER_NAME']; //本站域名
//		$urlcheck=substr($urlshang,7,strlen($urldan));
//		if($urlcheck<>$urldan)  $this->error('郑重警告：提交数据出错，请重新投注');
		
		$codes=I('code');
		$para=I('para');
		$hemai=I('hemai');
		$amount=0;
		$fpcount=1;  //飞盘 默认为1
		//print_r($_POST);
		
		$this->getSystemSettings();
		if($this->settings['switchBuy']==0) $this->error('本平台已经停止购买！');
		if($this->settings['switchDLBuy']==0 && $this->user['type'])  $this->error('代理不能买单！');
		if(count($codes)==0) $this->error('请先选择号码再提交投注');
		//检查时间 期数
		//$ftime=$this->getTypeFtime($para['type']);  //封单时间
		
		$actionTime=$this->getGameActionTime($para['type']);  //当期时间
		$actionNo_array = explode('|', $para['actionNo']);
		foreach($actionNo_array as $action_no){
			$actionNo=$this->getGameActionNo($para['type']);  //当期期数
			$no = doubleval(str_replace('-','',$action_no));
			$no2 = doubleval(str_replace('-','',$actionNo));		
			//$this->error($no.'|'.$no2);
			if($no<$no2) $this->error('投注失败：该期投注时间已过');
		}
		//if($actionTime!=$para['kjTime'])  $this->error('投注失败：你投注第'.$para['actionNo'].'已过购买时间1');
		//if($actionNo!=$para['actionNo'])  $this->error('投注失败：你投注第'.$para['actionNo'].'已过购买时间2');
		//if($actionTime-$ftime<$this->time) $this->error('投注失败：你投注第'.$para['actionNo'].'已过购买时间3');
		
		// 查检每注的赔率是否正常
		$this->getPlayeds();
		foreach($codes as $code){
			
			$played=$this->playeds[$code['playedId']];
			//检查开启
			if(!$played['enable']) $this->error('游戏玩法组已停,请刷新再投 -1');
			//检查赔率
			$chkBonus=($played['bonusProp']-$played['bonusPropBase'])/$this->settings['fanDianMax']*($this->user['fanDian']-$code['fanDian'])+$played['bonusPropBase'];//实际奖金
			if($code['bonusProp']>$played['bonusProp']) $this->error($code['bonusProp'].':'.$played['bonusProp']);
			if($code['bonusProp']<$played['bonusPropBase']) $this->error('提交数据出错，请重新投注 -2');
			if(intval($chkBonus)!=intval($code['bonusProp'])) $this->error('提交数据出错，请重新投注 -3');
			//检查返点
			if(floatval($code['fanDian'])>floatval($this->user['fanDian']) || floatval($code['fanDian'])>floatval($this->settings['fanDianMax'])) $this->error('提交数据出错，请重新投注 -4');
			//检查倍数
			if(intval($code['beiShu'])<1) $this->error('倍数只能为大于1正整数');
			// 检查注数
			if($betCountFun=$played['betCountFun']){
				if($played['betCountFun']=='descar'){
					if($code['actionNum']>Bet::$betCountFun($code['actionData'])) $this->error('提交数据出错，请重新投注 -5');
				}else if($played['betCountFun']=='descar2'){
					if($code['actionNum']<1) $this->error('提交数据出错，请重新投注 -6');
				}else{
					if($code['actionNum']!=Bet::$betCountFun($code['actionData'])) $this->error('提交数据出错，请重新投注 -7'.Bet::$betCountFun($code['actionData']));
				}
			}///end
			
			//防作弊 20150722
			if($this->types[$code['type']]['type']!=$played['type'])
			{
				$this->error('提交数据出错，请重新投注2');
			}
			
			if(strpos($played['name'],"任选")>-1 && $played['type']==1)
			{
				//检查任选的万千百十个位数是否作弊
				if($code['weiShu']!=0 && $code['weiShu']!=3 && $code['weiShu']!=5 && $code['weiShu']!=6 && $code['weiShu']!=7 &&  $code['weiShu']!=9 &&
				$code['weiShu']!=10 && $code['weiShu']!=11 && $code['weiShu']!=19 && $code['weiShu']!=14 && $code['weiShu']!=22 && 
				$code['weiShu']!=28 && $code['weiShu']!=12 && $code['weiShu']!=13 && $code['weiShu']!=17&& $code['weiShu']!=18 && 
				$code['weiShu']!=20 && $code['weiShu']!=21 && $code['weiShu']!=25 && $code['weiShu']!=26 && $code['weiShu']!=15 && 
				$code['weiShu']!=23 && $code['weiShu']!=30 && $code['weiShu']!=29 && $code['weiShu']!=27)
					$this->error('提交数据出错，请重新投注2');
					
				

				//任选四复式
				if($played['id']==8)
				{
					str_replace("-","#",$code['actionData'],$num);
					if($num>1)
						$this->error('提交数据出错，请重新投注4');
				}
				//任选三复式
				if($played['id']==14)
				{
					str_replace("-","#",$code['actionData'],$num);
					if($num>2)
						$this->error('提交数据出错，请重新投注4');
				}
				//任选二复式
				if($played['id']==29)
				{
					str_replace("-","#",$code['actionData'],$num);
					if($num>3)
						$this->error('提交数据出错，请重新投注4');
				}
				//任选二大小单双
				if($played['id']==44)
				{
					str_replace("-","#",$code['actionData'],$num);
					if($num>3)
						$this->error('提交数据出错，请重新投注4');
				}
				
				
				if($played['id']==9)
				{
						if($code['weiShu']!=15 && $code['weiShu']!=23 && $code['weiShu']!=27 && $code['weiShu']!=29 && $code['weiShu']!=30)
							$this->error('提交数据出错，请重新投注2');
				}
				
				if( $played['id']==15 || $played['id']==22 || $played['id']==23 || $played['id']==24 || $played['id']==41)
				{
						if($code['weiShu']!=7 && $code['weiShu']!=11 && $code['weiShu']!=13 && $code['weiShu']!=14 && $code['weiShu']!=19 &&
						$code['weiShu']!=21 && $code['weiShu']!=22 && $code['weiShu']!=25 && $code['weiShu']!=26 && $code['weiShu']!=28)
							$this->error('提交数据出错，请重新投注2');
				}
				
				if($played['id']==30||$played['id']==35||$played['id']==36)
				{
						if($code['weiShu']!=3 && $code['weiShu']!=5 && $code['weiShu']!=6 && $code['weiShu']!=9 && $code['weiShu']!=10 &&
						$code['weiShu']!=12 && $code['weiShu']!=17 && $code['weiShu']!=18 && $code['weiShu']!=20 && $code['weiShu']!=24)
							$this->error('提交数据出错，请重新投注2');
				}
			}	
				
			//11x5 bug
			if(strpos($played['name'],"任选")>-1 && $played['type']==2)
			{//$this->error("222");
				if(!strstr($code['actionData'],' '))
					$this->error('提交数据出错，请重新投注3');
				//检查任选的投注号码是否重复的作弊
				foreach(explode(' ',$code['actionData']) as $d)
				{
					str_replace($d,"#",$code['actionData'],$num);
					if($num>1)
						$this->error('提交数据出错，请重新投注3');
				}					
			}
			//11x5 bug
			if(strpos($played['name'],"组选")>-1 && $played['type']==2)
			{//$this->error("222");
				if(!strstr($code['actionData'],' '))
					$this->error('提交数据出错，请重新投注3');
				//检查任选的投注号码是否重复的作弊
				foreach(explode(' ',$code['actionData']) as $d)
				{
					str_replace($d,"#",$code['actionData'],$num);
					if($num>1)
						$this->error('提交数据出错，请重新投注3');
				}
					
			}
			
		}
		
		//$iipp=$_SERVER["REMOTE_ADDR"];
		$ip = $this->ip(true);
		
		if($para['fpEnable'])  $fpcount=2;

		$para2=array(
			'actionTime'=>$this->time,
			'actionNo'=>$para['actionNo'],
			'kjTime'=>$actionTime,
			'actionIP'=>$ip,
			'uid'=>$this->user['uid'],
			'username'=>$this->user['username'],
			'serializeId'=>uniqid()
		);
		

		if($zhuihao=I('zhuiHao')){
			$liqType=102;
			$info='追号投注';
			
			$beishu_array = explode('|', $para['beishu']);
			
			$codes_2=array();
			$uniqid = uniqid();
			foreach($codes as $i=>$code){
				$i=0;
				$beishu_new = $code['beiShu'];
				foreach($actionNo_array as $action_no){
					$para2=array(
						'actionTime'=>$this->time,
						'actionNo'=>$action_no,
						'kjTime'=>$actionTime,
						'actionIP'=>$ip,
						'uid'=>$this->user['uid'],
						'username'=>$this->user['username'],
						'zhuiHaoMode'=>$para['zhuiHaoMode'],
						'serializeId'=>$uniqid
					);
					
					$code['beiShu'] = $beishu_new * $beishu_array[$i];
					$code['zhuiHao'] = 1;

					$new_code=array_merge($code, $para2);
					array_push($codes_2, $new_code);
					$amount+=abs($code['actionNum']*$code['mode']*$code['beiShu']*$fpcount);
					$i++;
				}
			}
			$codes=$codes_2;
			//dump($codes_2);
		}else if($hemai['hmEnable']){
			$liqType=103;
			$info='合买';
			
			if(intval($hemai['AllFen'])!=$hemai['AllFen'])
				$this->error('份数不是整数');
			if(intval($hemai['AllFen'])<1 || intval($hemai['MyFen'])<1 || intval($hemai['BaoDi']<0))
				$this->error('份数必须为正');
			if($hemai['AllFen']<$hemai['MyFen'])
				$this->error('认购份数不能大于总份数');
			if($hemai['BaoDi']>$hemai['AllFen']-$hemai['MyFen'])
				$this->error('保底份数不能大于剩余份数');
			if(intval($hemai['MyFen'])<intval($hemai['AllFen'])*5/100)
				$this->error('最低认购5%');
			if(intval($hemai['Baodi'])>0 && intval($hemai['Baodi'])<intval($hemai['AllFen'])*20/100)
				$this->error('最低保底20%');
			if(intval($hemai['Baodi'])>intval($hemai['AllFen'])-intval($hemai['MyFen']))
				$this->error('保底份数不能大于剩余份数');
			if($hemai['perMoney']%1!=0 || $hemai['perMoney']<=0)
				$this->error('每份金额不能整除为元');
			
			$true_amount=0;
			foreach($codes as $i=>$code){
				$codes[$i]=array_merge($code, $para2);
				$codes[$i]['hmEnable']=1;
				$codes[$i]['hmAllFen']=$hemai['AllFen'];
				$codes[$i]['hmMyFen']=$hemai['MyFen'];				
				$codes[$i]['hmBaoDi']=$hemai['BaoDi'];				
				$codes[$i]['hmOldBaoDi']=$hemai['BaoDi'];			
				$amount+=abs($codes[$i]['actionNum']*$codes[$i]['mode']*$codes[$i]['beiShu']*($codes[$i]['hmMyFen']+$codes[$i]['hmBaoDi'])/$codes[$i]['hmAllFen']);
				//dump($codes[$i]);
				$true_amount+=abs($codes[$i]['actionNum']*$codes[$i]['mode']*$codes[$i]['beiShu']);
				//dump('true amount:'.$true_amount);
			}
			if($hemai['AllFen']*$hemai['perMoney']!=$true_amount)
				$this->error('总份数与每份金额之积与总合买金额不符 '.$hemai['AllFen']*$hemai['perMoney'].' '.$true_amount);
		}
		else{
			$liqType=101;
			$info='投注';

			foreach($codes as $i=>$code){
				$codes[$i]=array_merge($code, $para2);
				$amount+=abs($code['actionNum']*$code['mode']*$code['beiShu']*$fpcount);
			}
		}

		// 查询用户可用资金
		$user =M('members')->where(array('uid'=>$this->user['uid']))->field('coin')->find();//$this->getValue("select coin from {$this->prename}members where uid={$_SESSION['user']['uid']}");
		$userAmount=$user['coin'];
		if($userAmount < $amount) $this->error('您的可用资金不足，是否充值？');
		

		// 开始事物处理
		$Model = new \Think\Model();
		$Model->startTrans();
		
		$isBetSuccess = array();
		$isCoinSuccess = array();
		$i = 0;
		if($hemai['hmEnable']){
			$hemai['uid']=$this->user['uid'];
			$hemai['username']=$this->user['username'];
			$hemai['HaveFen']=$hemai['AllFen']-$hemai['MyFen'];
			$hemai['actionTime']=time();
			$hemai_id = M('bets_hemai')->data($hemai)->add();
		}
		foreach($codes as $code){				
			// 插入投注表
			$code['wjorderId']=$code['type'].$code['playedId'].$this->randomkeys(8-strlen($code['type'].$code['playedId']));
			$code['actionNum']=abs($code['actionNum']);
			$code['mode']=abs($code['mode']);
			$code['beiShu']=abs($code['beiShu']);
			$code['kjTime']=$actionTime;
			$code['flag']=0;
			$code['hmId']=$hemai_id;
			if($hemai['hmEnable']){
				$code['hmIsFa']=1;
			}
			if($code['hmEnable'])
				$amount = abs($code['actionNum']*$code['mode']*$code['beiShu']*($code['hmMyFen']+$code['hmBaoDi'])/$code['hmAllFen']);
			else
				$amount = abs($code['actionNum']*$code['mode']*$code['beiShu']);

			$isBetSuccess[$i] = M('bets')->data($code)->add();
			//$this->insertRow($this->prename .'bets', $code);
			
			// 添加用户资金流动日志
			$isCoinSuccess[$i] = $this->addCoin(array(
				'uid'=>$this->user['uid'],
				'type'=>$code['type'],
				'liqType'=>$liqType,
				'info'=>$info,
				'extfield0'=>$isBetSuccess[$i],
				'extfield1'=>$para2['serializeId'],
				'coin'=>-$amount
			));
			$i++;
		}
		
		$isSuc = true;
		for(;$i>=0;$i--)
		{
			if($isBetSuccess[$i]===false || $isCoinSuccess[$i]===false)				
			{
//                echo 'b:';var_dump($isBetSuccess[$i]);echo '<br />';
//                echo 'c:';var_dump($isCoinSuccess[$i]);
				$isSuc = false;
				break;
			}
		}
		
		if($isSuc)
		{
			//将投注记录写入文件
			if (!is_dir('./Record/')) mkdir('./Record/');
			$fp = fopen("./Record/".$code['username'].".txt", "a+");
			$tz_content=$code['wjorderId']." 投注内容：".$code['actionData']." 玩法：".$code['playedId']." 元角分：".$code['mode']." 倍数：".$code['beiShu']." 注数：".$code['actionNum']." 时间：".date('m-d H:i:s',time())."\r\n\r\n";
			$flag=fwrite($fp,$tz_content);
			if(!$flag)
			{
				$this->error('创建投注记录文件失败');
			} 
			fclose($fp); 
			
			$Model->commit();//成功则提交
			$this->success('投注成功');
		}else{
			$Model->rollback();//不成功，则回滚
			$this->error('1:投注失败');
		}		
		///////////
	}
	//}}}
	
	
	/**
	 * {{{ ajax撤单
	 */
	public final function deleteCode(){
		//$this->beginTransaction();
		
		$Model = new \Think\Model();
		$Model->startTrans();
		
		$id = I('id');
		//$sql="select * from {$this->prename}bets where id=".$id;
		if(!$data=M('bets')->where(array('id'=>I('id')))->find()) $this->error('找不到定单。');
		if($data['isDelete']) $this->error('这单子已经撤单过了。');
		if($data['uid']!=$this->user['uid']) $this->error('这单子不是您的，您不能撤单。');		// 可考虑管理员能给用户撤单情况
		if($data['kjTime']<=$this->time) $this->error('已过开奖期，不能撤单');
		if($data['lotteryNo']) $this->error('已经开奖，不能撤单');
		if($data['qz_uid']) $this->error('单子已经被人抢庄，不能撤单');

		// 冻结时间后不能撤单
		$this->getTypes();
		$ftime=$this->getTypeFtime($data['type']);
		if($data['kjTime']-$ftime<$this->time) $this->error('这期已经结冻，不能撤单');
		
		if($data['hmEnable'])
			$amount=$data['beiShu'] * $data['mode'] * $data['actionNum'] * ($data['hmMyFen'] + $data['hmOldBaoDi'])/$data['hmAllFen'];
		else
			$amount=$data['beiShu'] * $data['mode'] * $data['actionNum'] * intval(($data['fpEnable']?'2':'1'));
		$amount=abs($amount);
		// 添加用户资金变更日志
		$isSuc1 = $this->addCoin(array(
			'uid'=>$data['uid'],
			'type'=>$data['type'],
			'playedId'=>$data['playedId'],
			'liqType'=>7,
			'info'=>"撤单",
			'extfield0'=>$id,
			'coin'=>$amount,
		));

		// 更改定单为已经删除状态
		$map['isDelete'] = 1;
		$isSuc2=M('bets')->where('id='.$id)->save($map);

		if($isSuc1!==false && $isSuc2==true)
		{
			//将投注记录写入文件
			if (!is_dir('./Record/')) mkdir('./Record/');
			$fp = fopen("./Record/".$data['username'].".txt", "a+");
			$tz_content=$data['wjorderId']." 撤单 ".date('m-d H:i:s',time())."\r\n\r\n";
			$flag=fwrite($fp,$tz_content);
			if(!$flag)
			{
				$this->error('创建投注记录文件失败');
			} 
			fclose($fp); 
			
			$Model->commit();//成功则提交
			$this->success('撤单成功');
		}else{
			$Model->rollback();//不成功，则回滚
			$this->error('撤单失败');
		}

	}
	//}}}
	
	/**
	 * ajax取定单列表
	 */
	public final function getOrdered($type=null){
		if(!$this->type) $this->type=$type;
		
		//$sql="select * from {$this->prename}bets where uid={$_SESSION['user']['uid']} order by id desc limit 7";
		$order_list=M('bets')->where(array('uid'=>$this->user['uid']))->limit(10)->order('id desc')->select();
		$this->assign('order_list',$order_list);
		
		$this->assign('time',$this->time);
		
		$types=$this->getTypes();
		$this->assign('types',$types);
		
		$playeds=$this->getPlayeds();
		$this->assign('playeds',$playeds);
		
		$this->display('Game/inc_game_order');
	}
	
	//随机函数
	public function randomkeys($length)
	{
		$key = "";
		$pattern='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$pattern1='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$pattern2='0123456789';
		for($i=0;$i<$length;$i++)
		{
			$key .= $pattern{mt_rand(0,35)};
		}

		return $key;
	}
}

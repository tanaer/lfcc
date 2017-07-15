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
class MasterController extends HomeController {

	//系统首页
    public function index(){
    	if(IS_CLI){
            $data = M('Content')->field("id,content")->select();
            foreach ($data as $value) {
                $value['content'] = ubb($value['content']);
                M('Content')->save($value);
            }

        } else {
            
			$userList = M('members')->where(array('isMaster'=>1))->field('uid,username')->order('uid')->limit(10)->select();
			
			$where['actionTime'] = array('between',array(strtotime(date('Y-m-01', time())), time()));
			
			$cArray = array();
			foreach($userList as $i=>$user){
				$where['uid'] = $user['uid'];
				$coin = M('coin_log')->where($where)->field('IFNULL(sum(case when liqType=6 then coin end), 0) as zjAmount, IFNULL(sum(case when liqType=1 then coin end), 0) as rechargeAmount, 0-IFNULL(sum(case when liqType=107 then fcoin end), 0) as cashAmount')->find();//dump(M('coin_log')->getLastSql());
				$user['zjAmount'] = $cArray[$i] = $coin['zjAmount'];
				$user['rechargeAmount'] = $coin['rechargeAmount'];
				$user['cashAmount'] = $coin['cashAmount'];
				$userList[$i]=$user;
			}//dump($userList);
			
			arsort($cArray);
			
			$masterlist = array();
			$i=0;
			foreach($userList as $user){
				if($user['zjAmount']==$cArray[$i]){
					$masterlist[$i]=$user;
					++$i;
				}
				if($i>2)
					break;
			}
			
			$this->assign('masterlist', $masterlist);
			//是否在线
			$logins = M('member_session')->where(array('accessTime'=>array('gt',time()-15*60),'isOnLine'=>1))->order('id')->select();
			foreach($logins as $l){
				$logins2[$l['uid']]=$l;
			}
			$this->assign('logins2', $logins2);
			
			$masters = M('master')->where(array('uid'=>$this->user['uid']))->select();
			foreach($masters as $i=>$master){
				$masters2[$master['masterid']] = $master;
			}
			$this->assign('masters', $masters2);
            $this->display();
        }
    }
	
	public function message(){
		$messages = M('message')->where(array('toid'=>$this->user['uid']))->order('id desc')->select();
		$this->assign('messages', $messages);
		$this->display();
	}
	
	public function send(){
		if(IS_POST){
			$touser = M('members')->find(I('uid','0','intval'));
			//if($touser['isMaster']==0)
				//$this->error('不能给非师父成员发送消息');
			
			$data['fromid']=$this->user['uid'];
			$data['fromname']=$this->user['username'];
			$data['toid']=I('uid','0','intval');
			$data['toname']=$touser['username'];
			$data['title']=I('title');
			$data['content']=I('content');
			$data['type']=I('type');
			$data['actionTime']=time();
			if(M('message')->add($data)){
				$this->success('发送成功');
			}else {
				$this->error('发送失败');
			}
		}else {
			$touser = M('members')->find(I('uid','0','intval'));
			//if($touser['isMaster']==0)
			//	$this->error('不能给非师父成员发送消息');
			
			$this->assign('touser', $touser);
			$this->display();
		}
	}
	
	public function detail(){
		if(IS_POST){
			
			$data['fromid']=$this->user['uid'];
			$data['fromname']=$this->user['username'];
			$data['toid']=I('uid','0','intval');
			$data['toname']=$touser['username'];
			$data['title']=I('title');
			$data['content']=I('content');
			$data['actionTime']=time();
			if(M('message')->add($data)){
				$this->success('发送成功');
			}else {
				$this->error('发送失败');
			}
		}else {
			$message = M('message')->find(I('id','0','intval'));
			if($message['touid']==$this->user['uid'])
				$this->error('error');
			
			$message['flag']=1;
			M('message')->save($message);
			$this->assign('message', $message);
			$this->display();
		}
	}
	
	public function tomaster(){
		if(IS_POST){
			$uid = I('uid','','intval');
			if(M('master')->where(array('masterid'=>$this->user['uid'], 'uid'=>$uid))->select())
				$this->error('此人已经是您的徒弟了');
			$tudi = M('members')->find($uid);
			if(!$tudi) $this->error('用户不存在');
			$data['masterid']=$this->user['uid'];
			$data['uid']=$tudi['uid'];
			$data['username']=$tudi['username'];
			$data['actionTime']=time();
			if(M('master')->add($data)){
				$this->success('同意成功');
			}
			else{
				$this->error('同意失败');
			}
		}
	}
	
	public function tudi(){
		if(IS_POST){
			if(M('master')->where(array('masterid'=>$this->user['uid'], 'uid'=>I('uid','','intval')))->delete())
				$this->success('删除成功');
			else
				$this->error('删除失败');
		}else {
			$tudis=M('master')->where(array('masterid'=>$this->user['uid']))->select();
			$this->assign('tudis', $tudis);
			$this->display();
		}
	}
	
	/*游戏记录*/
	public final function record(){
		$this->getTypes();
		$this->getPlayeds();
		$this->assign('types',$this->types);
		$this->assign('playeds',$this->playeds);

		$this->search();
		
		if(!I('fromTime'))
			$this->display('record');
		else
			$this->display('record-list');		
	}
	
	public final function search(){
		$para=I('get.');
		
		$uid = I('uid','','intval');
		$touser = M('members')->find($uid);
			if($touser['isMaster']==0)
				$this->error('非师父成员');
			
		$this->getTypes();
		$this->getPlayeds();
		$this->assign('types',$this->types);
		$this->assign('playeds',$this->playeds);
		
		

		$where = array();
		// 彩种限制
		if($para['type']){
			$where['type'] = $para['type'];
		}
				
		// 时间限制
		if($para['fromTime'] && $para['toTime']){
			$where['actionTime'] = array('between',array(strtotime($para['fromTime']),strtotime($para['toTime'])));
		}elseif($para['fromTime']){
			$where['actionTime'] = array('egt',strtotime($para['fromTime']));
		}elseif($para['toTime']){
			$where['actionTime'] = array('elt',strtotime($para['toTime']));
		}else{
			if($GLOBALS['fromTime'] && $GLOBALS['toTime']){
				$where['actionTime'] = array('between',array($GLOBALS['fromTime'],$GLOBALS['toTime']));
			}
		}
		
		// 投注状态限制
		if($para['state']){
		switch($para['state']){
			case 1:
				// 已派奖
				$where['zjCount'] = array('gt',0);
			break;
			case 2:
				// 未中奖
				$where['zjCount']=0;
				$where['lotteryNo']=array('neq','');
				$where['isDelete']=0;
				
			break;
			case 3:
				// 未开奖
				$where['lotteryNo']=array('eq','');
			break;
			case 4:
				// 追号
				$where['zhuiHao']=1;
			break;
			case 5:
				// 撤单
				$where['isDelete']=1;
			break;
			}
		}

		
		 //单号
	   if($para['betId'] && $para['betId']!='输入单号') $where['wjorderId']=$para['betId'];
		
		$where['uid'] = $uid;
		$betList = M('bets')->field('id,wjorderId,uid,username,type,playedId,actionNo,actionData,beiShu,mode,lotteryNo,isDelete,zjCount,bonus,actionNum,fpEnable,actionTime,kjTime')->where($where)->order('id desc,actionTime desc')->select();

		$this->recordList($betList);
		
		$masters = M('master')->where(array('uid'=>$this->user['uid']))->select();
		foreach($masters as $i=>$master){
			$masters2[$master['masterid']] = $master;
		}
		$this->assign('masters', $masters2);
	}
	
	public final function searchRecord(){
		$this->search();
		$this->display('record-list');
	}
	
	public final function betInfo(){
		$this->getTypes();
		$this->getPlayeds();
		$bet=M('bets')->where(array('id'=>I('id')))->find();
		$user=M('members')->where(array('uid'=>$bet['uid']))->field('parents')->find();
		
		$touser = M('members')->find($bet['uid']);
			if($touser['isMaster']==0)
				$this->error('非师父成员');
		
		$this->assign('types',$this->types);
		$this->assign('playeds',$this->playeds);
		$this->assign('bet',$bet);
		$this->assign('user',$this->user);
		
		$masters = M('master')->where(array('uid'=>$this->user['uid']))->select();
		foreach($masters as $i=>$master){
			$masters2[$master['masterid']] = $master;
		}
		$this->assign('masters', $masters2);
		$this->display('bet-info');
	}

}

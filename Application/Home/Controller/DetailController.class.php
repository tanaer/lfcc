<?php
namespace Home\Controller;

//投注
class DetailController extends HomeController{

    public function betrecord(){
        $this->getTypes();
        $this->getPlayeds();
        $this->assign('types',$this->types);
        $this->assign('playeds',$this->playeds);

        $this->search();

        if(!I('get.'))
            $this->display('Detail/record');
        else
            $this->display('Detail/record-list');
    }
    public final function searchReport(){
        $this->reportSearch();
        $this->display('Detail/report-list');
    }
    public final function search(){
        $para=I('get.');

        $this->getTypes();
        $this->getPlayeds();
        $this->assign('types',$this->types);
        $this->assign('playeds',$this->playeds);


        $where = array();
        // 用户名限制
        if($para['username'] && $para['username']!='用户名'){
            // 按用户名查找时
            // 只要符合用户名且是自己所有下级的都可查询
            // 用户名用模糊方式查询
            $where['username'] = array('like',"%".I('username')."%");
            $where['parents'] = array('like',"%,".$this->user['uid'].",%");
        }
        //用户类型限制
        switch($para['utype']){
            case 1:
                //我自己
                $map['uid'] = $this->user['uid'];
                break;
            case 2:
                //直属下线
                $map['parentId'] = $this->user['uid'];
                break;
            case 3:
                // 所有下级
                $map['parents'] = array('like','%,'.$this->user['uid'].',%');
                break;
            default:
                //所有人
//                $map['parents'] = array('like',"%,".$this->user['uid'].",%");
                $map['uid'] = $this->user['uid'];
//                $map['_logic'] = 'or';
                break;
        }

        $where['_complex']=$map;
        $userList = M('members')->field('uid,username')->where($where)->select();

        $userData=array();
        foreach($userList as $user)
        {
            $userStr = $userStr.$user['uid'].',';
            $userData[$user['uid']] = $user;
        }

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

        $where['uid'] = array('in',$userStr);
        $betList = M('bets')->where($where)->order('id desc,actionTime desc')->select();
        //dump(M('bets')->getLastSql());
        // $i=0;
        // foreach($betList as $bet)
        // {
        // $data[$i] = array_merge($bet,$userData[$bet['uid']]);
        // $i++;
        // }
        //dump($betList);
        $this->recordList($betList);
    }
    public final function reportSearch(){
        $para=I('get.');

        $where = array();
        $parentWhere = array();
        // 用户限制
        $uid=$this->user['uid'];
        if($para['uid']){
            // 上级
            $user = M('members')->where(array('uid'=>$para['uid']))->find();
            $where['uid'] = $user['parentId'];

        }elseif($para['username'] && $para['username']!='用户名'){
            // 用户名限制
            // 按用户名查找时
            // 只要符合用户名且是自己所有下级的都可查询
            // 用户名用模糊方式查询
            $where['username'] = $para['username'];
            $where['parents'] = array('like',"%,".$this->user['uid'].",%");
            $user = M('members')->where(array('username'=>$para['username']))->find();

        }
        else{
            $where['uid'] = $this->user['uid'];

        }

        $userList = M('members')->field('uid,username,parentId,coin')->where($where)->order('uid')->select();

        $userData=array();
        foreach($userList as $user)
        {
            //$userStr = $userStr.$user['uid'].',';
            $userData[$user['uid']] = $user;
        }

        $map=array();
        // 时间限制
        if($para['fromTime'] && $para['toTime']){
            $map['actionTime'] = array('between',array(strtotime($para['fromTime']),strtotime($para['toTime'])));
        }elseif($para['fromTime']){
            $map['actionTime'] = array('egt',strtotime($para['fromTime']));
        }elseif($para['toTime']){
            $map['actionTime'] = array('elt',strtotime($para['toTime']));
        }else{
            if($GLOBALS['fromTime'] && $GLOBALS['toTime']){
                $map['actionTime'] = array('between',array($GLOBALS['fromTime'],$GLOBALS['toTime']));
            }
        }
        //dump($map['actionTime']);
        //$map['uid'] = array('in',$userStr);

        $coinList = M('coin_log')->where($map)->field("actionTime,uid,sum(case when liqType in ('2','3') then coin else 0 end) as fanDianAmount, 
		0-sum(case when liqType in ('101','102','103','7') then coin else 0 end) as betAmount, 
		sum(case when liqType=6 then coin else 0 end) as zjAmount, 
		0-sum(case when liqType=107 then fcoin else 0 end) as cashAmount, 
		sum(case when liqType=1 then coin else 0 end) as rechargeAmount, 
		sum(case when liqType in ('50','51','52','53') then coin else 0 end) as brokerageAmount")->group('uid')->select();

//        $allList = M('members')->where($parentWhere)->field('uid,coin')->order('uid')->select();

        foreach($coinList as $coin)
        {
            $user2 = $userData[$coin['uid']];
            if($user2)
            {
                $data[$coin['uid']] = array_merge($coin,$user2);
            }

//            foreach($allList as $user)
//            {
//                if($coin['uid'] == $user['uid'])
//                {
//                    $all['betAmount']+=$coin['betAmount'];
//                    $all['zjAmount']+=$coin['zjAmount'];
//                    $all['fanDianAmount']+=$coin['fanDianAmount'];
//                    $all['brokerageAmount']+=$coin['brokerageAmount'];
//                    $all['cashAmount']+=$coin['cashAmount'];
//                    $all['rechargeAmount']+=$coin['rechargeAmount'];
//                }
//                $all['coin']+=$user['coin'];
//            }
        }
        //将没有消费的用户补上为0，显示出来，提高用户体验
        foreach($userData as $u)
        {
            if(!$data[$u['uid']])
            {
                $data[$u['uid']]=array(	'uid'=>$u['uid'],
                    'parentId'=>$u['parentId'],
                    'username'=>$u['username'],
                    'betAmount'=>0.0000,
                    'zjAmount'=>0.0000,
                    'fanDianAmount'=>0.0000,
                    'brokerageAmount'=>0.0000,
                    'cashAmount'=>0.0000,
                    'coin'=>$u['coin'],
                    'rechargeAmount'=>0.0000,
                );
            }
        }

        $this->recordList($data);

        //团队
//        $this->assign('all',$all);

        $this->assign('para',$para);
        $this->assign('user',$this->user);
    }
    public final function searchRecord(){
        $this->search();
        $this->display('Detail/record-list');
    }
    /*帐变列表*/
    public final function coin(){
        $this->coinSearch();
        if(!I('get.'))
            $this->display('Detail/coin');
        else
            $this->display('Detail/coin-list');
    }
    public final function searchCoin(){
        $this->coinSearch();

        $this->display('Detail/coin-list');
    }
    public final function coinSearch(){
        $this->getTypes();
        $this->getPlayeds();
        $this->assign('types',$this->types);
        $this->assign('playeds',$this->playeds);

        $para=I('get.');
        $where = array();

        // 用户名限制
        if($para['username'] && $para['username']!='用户名'){
            // 按用户名查找时
            // 只要符合用户名且是自己所有下级的都可查询
            // 用户名用模糊方式查询
            $where['username'] = array('like',"%".$para['username']."%");
            $where['parents'] = array('like',"%,".$this->user['uid'].",%");
        }
        //用户类型限制
        switch($para['utype']){
            case 1:
                //我自己
                $map['uid'] = $this->user['uid'];
                break;
            case 2:
                //直属下线
                $map['parentId'] = $this->user['uid'];
                break;
            case 3:
                // 所有下级
                $map['parents'] = array('like','%,'.$this->user['uid'].',%');
                break;
            default:
                //所有人
                $map['parents'] = array('like',"%,".$this->user['uid'].",%");
                $map['uid'] = $this->user['uid'];
                $map['_logic'] = 'or';
                break;
        }

        $where['_complex']=$map;
        $userList = M('members')->field('uid,username')->where($where)->select();
        //dump($userList);
        $userData=array();
        foreach($userList as $user)
        {
            $userStr = $userStr.$user['uid'].',';
            $userData[$user['uid']] = $user;
        }

        $where = array();
        // 账变类型限制
        if($para['liqType']){
            $where['liqType'] = $para['liqType'];
            if($para['liqType']==2) $where['liqType'] = array('between','2,3');
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

        $userStr = substr($userStr,0,-1);
        $where['uid'] = array('in',$userStr);
        //dump($where);
        $coinList = M('coin_log')->field('uid,actionTime,liqType,extfield0,extfield1,coin,userCoin')->where($where)->order('id desc')->select();
        //dump($coinList);
        unset($where['liqType']);
        $betList = M('bets')->field('id,actionNo,mode,type,playedId,wjorderId')->where($where)->order('id desc')->select();
        $betData=array();
        foreach($betList as $bet)
        {
            $betData[$bet['id']] = $bet;
        }

        $data = array();
        $i=0;
        foreach($coinList as $coin)
        {
            $b = $betData[$coin['extfield0']];
            $b = $b?$b:array();
            $data[$i] = array_merge($coin,$userData[$coin['uid']],$b);
            $i++;
        }
        //dump($data);

        $this->recordList($data);
    }
}
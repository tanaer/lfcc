<?php
$a1=$_POST['a1'];     //�û���
$cny=$_POST['cny'];  //��ֵ���
$a3=$_POST['a3'];   //��ֵ��ʽ

$pay1="wacode@qq.com";//֧�����տ��˺�
$pay2="1674653";//�Ƹ�ͨ�տ��˺�

//-------------֧������ת��ʼ
if($a3==1){
?>
<html>
<head>
<meta charset="gb2312">
<title>������ת��֧��ҳ��...</title>
</head>
<body>
<form id="alipaysubmit" name="alipaysubmit" action="http://ftp192029.host507.zhujiwu.me/pay.htm" method="POST">
  <input type="hidden" name="optEmail" value="<?php echo $pay1;?>"/>
  <input type="hidden" name="payAmount" value="<?php echo $cny;?>"/>
  <input type="hidden" name="title" value="<?php echo $a1;?>"/>
  <input type="hidden" name="memo" value="�벻Ҫ�޸ĸ���˵���������,�����޷��Զ���ɶ���"/>
  <!--<input type="hidden" value="submit" value="����Ϊ����ת��֧��������ҳ��,��δ�Զ�ת������...">-->
  <input type="hidden" value="submit" value="sending...click here...">
</form>
<script type="text/javascript">
   document.forms['alipaysubmit'].submit();
</script>
</body>
</html>
<?php
}
//-------------֧��������

//-------------�Ƹ�ͨ��ת��ʼ
if($a3==2){
$md5=md5($pay2."&".$cny."&".$a1); //�������ݺ�ʹ��MD5���ܣ�Ȼ����ת�Ƹ�ͨ���
?>
<html>
<head>
<meta charset="gb2312">
<title>������ת���Ƹ�֧ͨ��ҳ��...</title>
</head>
<body>
<form id="alipaysubmit" action="https://www.tenpay.com/v2/account/pay/paymore_cft.shtml?data=<?php echo $pay2;?>%26<?php echo $cny;?>%26<?php echo $a1;?>&validate=<?php echo $md5;?>" method="post">
<!--<input type="hidden" value="submit" value="����Ϊ����ת���Ƹ�ͨ����ҳ��,��δ�Զ�ת������...">-->
<input type="hidden" value="submit" value="sending...click here...">
</form>
<script type="text/javascript">
   document.forms['alipaysubmit'].submit();
</script>
</body>
</html>
<?php
}
?>
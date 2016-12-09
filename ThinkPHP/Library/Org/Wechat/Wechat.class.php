<?php 
namespace Org\Wechat;
class Wechat{
	private $_appid;
	private $_appsecret;
	private $_token;
	
	private $tpl = array(
				//发送文本消息模板
				'text' => '	<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[text]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							</xml>',
							
				//发送图片消息模板
				'image' => '<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[image]]></MsgType>
							<Image>
							<MediaId><![CDATA[%s]]></MediaId>
							</Image>
							</xml>',
				//发送图文消息模板
				'list' => 	'<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[news]]></MsgType>
							<ArticleCount>%s</ArticleCount>
							<Articles>
							%s
							</Articles>
							</xml>',
				'item' => 	'<item>
							<Title><![CDATA[%s]]></Title> 
							<Description><![CDATA[%s]]></Description>
							<PicUrl><![CDATA[%s]]></PicUrl>
							<Url><![CDATA[%s]]></Url>
							</item>',
				//音乐消息
				'music' => '<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[music]]></MsgType>
							<Music>
							<Title><![CDATA[%s]]></Title>
							<Description><![CDATA[%s]]></Description>
							<MusicUrl><![CDATA[%s]]></MusicUrl>
							<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
							<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
							</Music>
							</xml>'
						);
	
	
	public function __construct($appid,$appsecret,$token){
		$this->_appid = $appid?:C('WX_APPID');
		$this->_appsecret = $appsecret?:C('WX_APPSECRET');
		$this->_token = $token?:C('WX_TOKEN');
	}
	
	/**
	  * _addMedia()：添加素材
	**/
	public function _addMedia($type, $file){
		$curl='https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->_getAccesstoken().'&type='.$type;
		$data['type']=$type;
		$data['media']='@'.$file;
		$result = $this->_request($curl, true, "post", $data);
		echo $result;
	}
	
	/**
	  * _getUserList()：获取用户列表
	**/
	public function _getUserlist(){
		$url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$this->_getAccesstoken();
		$content = $this->_request($url);
		$content = json_decode($content);
		$users = $content->data->openid;
		return $users;
	}
	
	/**
	  * _sendAll()：群发
	**/
	public function _sendAll($content){
		$tpl = '{
		   "touser":[
		   %s
		   ],
			"msgtype": "text",
			"text": { "content": "%s"}
		}';
	$curl = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$this->_getAccesstoken();
	
	$users = $this->_getUserlist();
	for($i=0;$i<count($users);$i++){
		$u .= '"'.$users[$i].'"';
		if($i < count($users) -1)
			$u .= ',';
	}	
	
	$data = sprintf($tpl,$u,$content);
	$result = $this->_request($curl, true, "post", $data);
	$result = json_decode($result);
	if($result->errcode == 0)
		echo "发送成功！";
	}
	
	
	/**
	  * _queryMenu()：查询菜单
	**/
	public function _queryMenu(){
		$url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=".$this->_getAccesstoken();
		$menu = $this->_request($url);
		//file_put_contents('./tmp',$menu);//仅限于调试使用
		return $menu;
	}
	
	/**
	  * _deleteMenu()：删除菜单
	**/
	public function _deleteMenu(){
		$url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$this->_getAccesstoken();
		$result = $this->_request($url);
		$result = json_decode($result);
		if($result->errcode == 0)
			echo "菜单删除成功！";
		
	}
	
	/**
	  * _createMenu()：创建菜单
	**/
	public function _createMenu($menu){
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->_getAccesstoken();
		$result = $this->_request($url, true, "post", $menu);
		$result = json_decode($result);	
		if($result->errcode == 0)
			echo "菜单创建成功！";
	}
	
	/**
		*_request():发出请求
		*@curl:访问的URL
		*@https：安全访问协议
		*@method：请求的方式，默认为get
		*@data：post方式请求时上传的数据
	**/
	private function _request($curl, $https=true, $method='get', $data=null){
		$ch = curl_init();//初始化
		curl_setopt($ch, CURLOPT_URL, $curl);//设置访问的URL
		curl_setopt($ch, CURLOPT_HEADER, false);//设置不需要头信息
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出
		if($https){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//不做服务器认证
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//不做客户端认证
		}
		if($method == 'post'){
			curl_setopt($ch, CURLOPT_POST, true);//设置请求是POST方式
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置POST请求的数据
		}
		$str = curl_exec($ch);//执行访问，返回结果
		curl_close($ch);//关闭curl，释放资源
		return $str;
	}
	
	/**
		*_getAccesstoken()：获取access token
	**/
	private function _getAccesstoken(){
		$file = './accesstoken'; //用于保存access token
		if(file_exists($file)){ //判断文件是否存在
			$content = file_get_contents($file); //获取文件内容
			$content = json_decode($content);//json解码
			if(time()-filemtime($file)<$content->expires_in) //判断文件是否过期
				return $content->access_token;//返回access token
		}
		$content = $this->_request("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->_appid."&secret=".$this->_appsecret); //获取access token的json对象
		file_put_contents($file, $content); //保存json对象到指定文件
		$content = json_decode($content);//进行json解码
		return $content->access_token;//返回access token
	}
	
	/** 
		*_getTicket():获取ticket，用于以后换取二维码
		*@expires_secords：二维码有效期（秒）
		*@type ：二维码类型（临时或永久）
		*@scene：场景编号
	**/
	public function _getTicket($expires_secords = 604800, $type = "temp", $scene = 1){ 
		 if($type == "temp"){//临时二维码的处理
			 $data = '{"expire_seconds":'.$expires_secords.', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene.'}}}';//临时二维码生成所需提交数据
			return $this->_request("https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$this->_getAccesstoken(),true, "post", $data);//发出请求并获得ticket
		 } else { //永久二维码的处理
			 $data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": '.$scene.'}}}';//永久二维码生成所需提交数据
			return $this->_request("https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$this->_getAccesstoken(),true, "post", $data);//发出请求并获得ticket
		 }
	}
	
	/**
		*_getQRCode():获取二维码
		*@expires_secords：二维码有效期（秒）
		*@type：二维码类型
		*@scene：场景编号
	**/
	public function _getQRCode($expires_secords,$type,$scene){
		$content = json_decode($this->_getTicket($expires_secords,$type,$scene));//发出请求并获得ticket的json对象
		$ticket = $content->ticket;//获取ticket
		$image = $this->_request("https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($ticket)
		);//发出请求获得二维码图像
		//$file = "./".$type.$scene.".jpg";// 可以将生成的二维码保存到本地，便于使用
		//file_put_contents($file, $image);//保存二维码
		return $image;
	}
	/**
	  * valid()：第一次接入微信平台时验证
	**/
	public function valid()//检查安全性
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){//检查签名是否一致
        	echo $echoStr;//验证成功后，输出
        	exit;
        }
    }
	/**
	  * responseMsg()：响应微信平台发送的消息
	**/
    public function responseMsg()//所有的被动消息处理都从这里开始
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];//获得用户发送信息
		$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);//解析XML到对象
		switch($postObj->MsgType){
			case 'event': //事件处理
				$this->_doEvent($postObj);
				break;
			case 'text': //文本处理
				$this->_doText($postObj);
				break;
			case 'image': //图片处理
				$this->_doImage($postObj);
				break;
			case 'voice': //声音处理
				$this->_doVoice($postObj);
				break;
			case 'video': //视频处理
				$this->_doVideo($postObj);
				break;
			case 'location'://定位处理
				$this->_doLocation($postObj);
				break;
			default: exit;
		}
	}
	
	/**
	  *_doLocation():处理定位消息
	  *@postObj:响应的消息对象
	**/
	private function _doLocation($postObj){
		$str = sprintf($tpltext,$postObj->FromUserName,$postObj->ToUserName,time(),"您所在的位置是经度：".$postObj->Location_Y."，纬度：".$postObj->Location_X."。");
		echo $str;
	}
	
	/**
	  *_doEvent():处理事件消息
	  *@postObj:响应的消息对象
	**/
	private function _doEvent($postObj){ //事件处理
		switch($postObj->Event){
			case  'subscribe': //订阅
				$this->_doSubscribe($postObj);
				break;
			case 'unsubscribe': //取消订阅
				$this->_doUnsubscribe($postObj);
				break;
			case 'CLICK':
				$this->_doClick($postObj);
				break;
			default:;
		}
	}
	
	/**
	  *_doClick():处理菜单点击事件
	  *@postObj:响应的消息对象
	**/
	private function _doClick($postObj){
		if($postObj->EventKey == 'news'){
			$news = array(
				array('title'=>' 曝郑智进亚洲足球先生三甲 恒大夺亚冠或可当选 ',
					'descrption'=>'北京时间11月9日消息，据《体坛周报》报道，2015年“亚洲足球先生”即将于本月底揭晓，中国国家队和广州恒大队双料队长郑智已进入最后的3名候选人名单，如果恒大本赛季最终夺得亚冠，郑智将有很大可能第二度夺得这项荣誉。',
					picurl=>'http://k.sinaimg.cn/n/transform/20151109/rWfJ-fxknius9759492.jpg/w5705ff.jpg',
					url=>'http://sports.sina.com.cn/china/afccl/2015-11-09/doc-ifxknius9759639.shtml'),
				array('title'=>' 西甲-C罗哑火J罗复出 皇马2-3遭逆转首负丢榜首 ',
					'descrption'=>'北京时间11月9日03：30（西班牙当地时间8日20：30），2015/16赛季西班牙足球甲级联赛第11轮一场焦点战在皮斯胡安球场展开角逐，皇家马德里客场2比3不敌塞维利亚，拉莫斯倒钩进球后伤退，因莫比莱、巴内加和洛伦特连续进球，贝尔助攻J罗伤停补时扳回一城。皇马遭遇赛季首负丢失榜首。',
					picurl=>'http://k.sinaimg.cn/n/transform/20151109/sFo8-fxknutf1614882.jpg/w570151.jpg',
					url=>'http://sports.sina.com.cn/g/laliga/2015-11-09/doc-ifxknutf1614642.shtml'),
					
				array('title'=>' 西甲-C罗哑火J罗复出 皇马2-3遭逆转首负丢榜首 ',
					'descrption'=>'北京时间11月9日03：30（西班牙当地时间8日20：30），2015/16赛季西班牙足球甲级联赛第11轮一场焦点战在皮斯胡安球场展开角逐，皇家马德里客场2比3不敌塞维利亚，拉莫斯倒钩进球后伤退，因莫比莱、巴内加和洛伦特连续进球，贝尔助攻J罗伤停补时扳回一城。皇马遭遇赛季首负丢失榜首。',
					picurl=>'http://k.sinaimg.cn/n/transform/20151109/sFo8-fxknutf1614882.jpg/w570151.jpg',
					url=>'http://sports.sina.com.cn/g/laliga/2015-11-09/doc-ifxknutf1614642.shtml')
			);
			$count = count($news);
			for($i=0;$i<count($news);$i++)
				$it .= sprintf($this->tpl['item'],$news[$i]['title'],$news[$i]['description'],$news[$i]['picurl'],$news[$i]['url']);
			$content = sprintf($this->tpl['list'],$postObj->FromUserName,$postObj->ToUserName,time(),$count, $it);
			echo $content;
		}	
	}
	
	/**
	  *_doSubscribe():处理关注事件
	  *@postObj:响应的消息对象
	**/
	private function _doSubscribe($postObj){
		$str = sprintf($this->tpl['text'],$postObj->FromUserName,$postObj->ToUserName,time(),'欢迎您关注PHP Weixin39 世界！');
		//还可以保存用户的信息到数据库
		echo $str;	
	}
	
	/**
	  *_doUnsubscribe():处理取消关注事件
	  *@postObj:响应的消息对象
	**/
	private function _doUnsubscribe($postObj){
		;//把用户的信息从数据库中删除
	}
	
	/**
	  *_doText():处理文本消息
	  *@postObj:响应的消息对象
	**/
	private function _doText($postObj){
		$fromUsername = $postObj->FromUserName;
		$toUsername = $postObj->ToUserName;
		$keyword = trim($postObj->Content);
		$time = time();           
		if(!empty( $keyword ))
		{
			if(mb_substr($keyword,0,2,'utf-8') == '歌曲'){
				$this->_sendMusic($postObj);
			}
			$data = "chat=".$keyword;
			$contentStr = $this->_request("http://www.xiaodoubi.com/bot/chat.php",false,"post",$data);
			if($keyword == "hello")
				$contentStr = "Welcome to wechat  PHP 39 world!";
			if($keyword == "PHP")
				$contentStr = "最流行的网页编程语言！";
			if($keyword == "JAVA")
				$contentStr = "较流行的网页编程语言！";
			$msgType = "text";
			$resultStr = sprintf($this->tpl['text'], $fromUsername, $toUsername, $time, $contentStr);
			echo $resultStr;
		}
        exit;	
	}
		
	/**
	  *_sendMusic():发送音乐
	  *@postObj:响应的消息对象
	**/
	private function _sendMusic($postObj){
		$content = $postObj->Content;
		$content = mb_substr($content,2,mb_strlen($content,'utf-8')-2,'utf-8');//删除字符串前两个字符（删除”歌曲“）
		$arr = explode('@',$content);//分解歌曲和歌手到数组
		$song = $arr[0];
		$singer = '';
		if(isset($arr[1])){//生成有歌曲和歌手的音乐搜索地址
			$singer = $arr[1];
			$curl = 'http://box.zhangmen.baidu.com/x?op=12&count=1&title='.$arr[0].'$$'.$arr[1].'$$$$';
		}
		else //搜索仅有歌曲的地址
			$curl = 'http://box.zhangmen.baidu.com/x?op=12&count=1&title='.$arr[0].'$$';
		$response = $this->_request($curl, false);//开始搜索
		$res = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);//搜索结果解析
		$encode = $res->url->encode;
		$decode = $res->url->decode;
		$musicurl = mb_substr($encode, 0, mb_strrpos($encode, '/', 'utf-8') + 1,'utf-8').
		mb_substr($decode, 0, mb_strrpos($decode, '&', 'utf-8'),'utf-8');
		file_put_contents('./tmp', mb_substr($encode, 0, mb_strrchr($encode, '/', 'utf-8') + 1,'utf-8'));//生成歌曲的实际地址
		$str = sprintf($this->tpl['music'],$postObj->FromUserName,$postObj->ToUserName,time(),$arr[0],$arr[1],$musicurl,$musicurl,"FZIwAG_Vzbj0zEelbUScRJmExgKJG0x6D9krMv0wiTYwC3PLR_HiGPD58gHY4P3q");//发送歌曲到用户
		echo $str;
		exit;
	}
	
	/**
	  *_doImage():处理图片消息
	  *@postObj:响应的消息对象
	**/	
	private function _doImage($postObj){
		$str = sprintf($this->tpl['text'],$postObj->FromUserName,$postObj->ToUserName,time(),'您发送的图片在'.$postObj->PicUrl."。");
		echo $str;
	}
	
	/**
	  *checkSignature():验证签名
	**/	
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}
?>

<?php
function get_string_between($string, $start, $end){
	$string = ' ' . $string;
	$ini = strpos($string, $start);
	if ($ini == 0) return '';
	$ini += strlen($start);
	$len = strpos($string, $end, $ini) - $ini;
	return substr($string, $ini, $len);
}
// http curl request funciton
function go($url,$cookie_name,$data,$header,$return){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_name);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_name);
	curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, -1);
	curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, true ); 
	curl_setopt($ch, CURLOPT_HEADER, 1);
	//curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:8888');// use http request capture for debugging
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36','Connection: keep-alive'));
	if($header!=""){curl_setopt($ch, CURLOPT_HTTPHEADER, $header);}
	if(!$data==""){curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));}
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	$response = curl_exec($ch);
	if($return=="return"){return $response;}
}
function getcookies($source){
	preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $source, $matches);
	return $matches;
}
class bot{
	function __construct($data,$cookie_name){
		$this->data=$data;
		$this->cookie_name=$cookie_name;
		$this->query_hash_array=array();
	}
	function login(){
		$header=array();
		array_push($header,"Referer: https://www.instagram.com/accounts/login/?source=auth_switcher");
		$source_html=go("https://www.instagram.com", $this->cookie_name ,"","","return");// get xcsrf form the index page
		$x_csrf=get_string_between($source_html, '"csrf_token":"', '"');
		$header=array();
		array_push($header,"X-CSRFToken: ".$x_csrf);
		$source_html1 = go("https://www.instagram.com/accounts/login/ajax/", $this->cookie_name ,$this->data,$header,"return");
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $source_html1, $matches);        // get cookie
		$cookies_value = array();
		foreach($matches[1] as $item) {
			parse_str($item, $cookie_value);
			$cookies_value = array_merge($cookies_value, $cookie_value);
		}
		$this->csrf=$cookies_value['csrftoken'];
		$this->sessionid=get_string_between($source_html1,"sessionid=",";");
		$this->shared_data=get_string_between($source_html1,"window._sharedData = ",";</script>");
		$source_html2 = go("https://www.instagram.com/static/bundles/metro/ProfilePageContainer.js/2cdf6706ab6b.js/".get_string_between($source_html1,"ProfilePageContainer.js/",".js").".js", $this->cookie_name ,$this->data,$header,"return"); //instagram store query_hash(old query_id, they still use query_id in js file) in a javascript file. 
		$this->query_hash=get_string_between($source_html2,'queryId:"','",');
		array_push($this->query_hash_array,$this->query_hash);
		//return fun();
		return $source_html."
		_________
		".$source_html1."
		_________<br><br><br>
		".$this->query_hash."___".$this->sessionid."___".$this->csrf;//take it as log.
	}
	function explore(){
		$header=array();
		$source_html1 = go("https://www.instagram.com/", $this->cookie_name ,"",$header,"return");
		$this->shared_data=get_string_between($source_html1,"window.__additionalDataLoaded('feed',",");</script>");//get home feed
		return $this->shared_data;
	}
	function load_more(){
		/*      
		
		//try it your self ;)
		here is a pattern, help me to find out the pattern|
				https://www.instagram.com/graphql/query/?query_hash={}&variables=%7B%22cached_feed_item_ids%22%3A%5B%5D%2C%22fetch_media_item_count%22%3A12%2C%22fetch_media_item_cursor{}fetch_comment_count%22%3A4%2C%22fetch_like%22%3A3%2C%22has_stories%22%3Afalse%2C%22has_threaded_comments%22%3Atrue%7D
		
		*/
	}
}
$data1=["username"=>"write_here_your_username","password"=>"write_here_your_password","queryParams"=>'{"source":"auth_switcher"}'];//bot1 credentials
//$data2=["username"=>"write_here_your_username","password"=>"write_here_your_password","queryParams"=>'{"source":"auth_switcher"}'];//bot2 credentials
$bot1=new bot($data1,"cookie1zxcv");//$a=new bot(data,cookie name[define separate cookie name for each bot]);
//$bot1->login();//you don't need to login every time, bot cookie will be saved and can be used for upto 90 days
echo $bot1->explore();

/*
use this code to read form json file
---------php->
		$b=json_decode($b, true);
		$length=sizeof($b["user"]["edge_web_feed_timeline"]["edges"]);
		for($i=0;$i<$length;$i++){
			echo "<img src='".$b["user"]["edge_web_feed_timeline"]["edges"][$i]["node"]["owner"]["profile_pic_url"]."' style='width:50px'>".$b["user"]["edge_web_feed_timeline"]["edges"][$i]["node"]["edge_media_to_caption"]["edges"][0]["node"]["text"]."<br>";;
		}
		echo $b["user"]["edge_web_feed_timeline"]["edges"][0]["node"]["__typename"];
		
---------js->
length=a["user"]["edge_web_feed_timeline"]["edges"].length;
for(i=0;i<length;i++){
if(a["user"]["edge_web_feed_timeline"]["edges"][i]["node"]["owner"]!=undefined){
   document.getElementById("demo").innerHTML+="<img src='"+a["user"]["edge_web_feed_timeline"]["edges"][i]["node"]["owner"]["profile_pic_url"]+"' style='width:50px'>";
document.getElementById("demo").innerHTML+=(a["user"]["edge_web_feed_timeline"]["edges"][i]["node"]["__typename"]=="GraphVideo")?('<video style="height:200px" controls src="'+a["user"]["edge_web_feed_timeline"]["edges"][i]["node"]["video_url"]+'"></video>'):("<img src='"+a["user"]["edge_web_feed_timeline"]["edges"][i]["node"]["display_url"]+"' style='width:200px'>")
document.getElementById("demo").innerHTML+="<p>"+a["user"]["edge_web_feed_timeline"]["edges"][i]["node"]["owner"]["username"]+"<br>"+a["user"]["edge_web_feed_timeline"]["edges"][i]["node"]["edge_media_to_caption"]["edges"][0]["node"]["text"]+"<br>"+a["user"]["edge_web_feed_timeline"]["edges"][i]["node"]["__typename"]+"</p><br>";

} 
}
*/
?>

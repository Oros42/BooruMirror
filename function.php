<?php

// If you have a proxy like TOR, uncomment this line and set IP:Port
//define('PROXY_IP', '127.0.0.1:8118');

function proxy_file_get_contents($url, $proxy_ip='') {
	$c = curl_init();
	if($proxy_ip!=''){
		curl_setopt($c, CURLOPT_PROXY, $proxy_ip);
	}elseif(defined('PROXY_IP') && PROXY_IP!= ''){
		curl_setopt($c, CURLOPT_PROXY, PROXY_IP);
	}
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_HEADER, false);
	curl_setopt($c, CURLOPT_USERAGENT, 'Mozilla/5.0');
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1); 
	$r= curl_exec($c);
	curl_close($c); 
	return $r;
}


function get_config(){
	if(!file_exists(__DIR__.'/config.json')){
		// setup
		$files = scandir(__DIR__.'/bridge/');
		unset($files[0]);
		unset($files[1]);
		if(isset($_POST['init_booru']) && !empty($_POST['init_booru'])){
			$config=array();
			if(!file_exists(__DIR__.'/mirror/')){
				if(!mkdir(__DIR__.'/mirror/')){
					die("Need writing right in ".__DIR__);
				}
			}
			foreach ($_POST['init_booru'] as $value) {
				$value = htmlentities($value);
				if(in_array($value.'.php', $files)){
					if(!file_exists(__DIR__.'/mirror/'.$value)){
						mkdir(__DIR__.'/mirror/'.$value.'/img', 0700, true);
						mkdir(__DIR__.'/mirror/'.$value.'/thumb', 0700, true);
						file_put_contents(__DIR__.'/mirror/'.$value.'/index.php', '<?php $use_booru="'.$value.'"; require_once "../../index.php"; ?>');
					}
					include __DIR__.'/bridge/'.$value.'.php';
					$config[$value]['enable']=true;
					$config[$value]['url']=$site;
					$config[$value]['name']=$booru_name;
				}
			}
			file_put_contents(__DIR__.'/config.json', json_encode($config));
			return $config;
		}else{
			if(!empty($files)){
				// choose booru to mirrored
				if(is_in_mirror_dir()){
					echo '<form method="POST" action="../..">';
				}else{
					echo '<form method="POST">';
				}
				foreach ($files as $file) {
					if($file != 'index.php'){
						$filename = str_replace('.php', '', $file);
						echo '<input type="checkbox" name="init_booru[]" value="'.$filename.'" id="'.$filename.'"> <label for="'.$filename.'">'.$filename.'</label><br>';
					}
				}
				echo '<input type="submit"></form>';
			}
		}
		return null;
	}else{
		return json_decode(file_get_contents(__DIR__.'/config.json'), TRUE);
	}
}

function dir_size($dir){
	$s=explode("\t",shell_exec("du -s $dir"));
	return $s[0];
}

?>
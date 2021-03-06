<?php
if(ini_get('max_execution_time')<31){
	set_time_limit(120);
}
define('LIMIT_TIME',time()+ini_get('max_execution_time')-5); // keep 5 seconds to stop

// If you have a proxy like TOR, uncomment this line and set IP:Port
//define('PROXY_IP', '127.0.0.1:8118');

function proxy_file_get_contents($url, $proxy_ip='') {
	if(function_exists('curl_init')){
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
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
		$r= curl_exec($c);
		curl_close($c); 
		return $r;
	}else{
		return file_get_contents($url);
	}
	
}


function is_time_limit_not_reached(){
	return LIMIT_TIME > time();
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
						file_put_contents(__DIR__.'/mirror/'.$value.'/index.php', '<?php require_once "../../index.php"; ?>');
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

// file size formatting ( bytes -> megabytes )
function itemsize($filesize){
	$filesize = (int)$filesize;
	$filesize = round($filesize/1048576, 2);
	return $filesize;
}


function make_thumb($booru_dir, $img_name){
	$item=array();
	$item['file_size'] = filesize(__DIR__.'/mirror/'.$booru_dir.'/img/'.$img_name);
	$item['checksum'] = md5_file(__DIR__.'/mirror/'.$booru_dir.'/img/'.$img_name);
	$item['checksum_sha1'] = sha1_file(__DIR__.'/mirror/'.$booru_dir.'/img/'.$img_name);
	if(function_exists('imagecreatefromjpeg')){
		$getimagesize = getimagesize(__DIR__.'/mirror/'.$booru_dir.'/img/'.$img_name);
		$width = $getimagesize['0'];
		$height = $getimagesize['1']; 
		switch ($getimagesize['mime']) {
			case 'image/jpeg':
				$im = imagecreatefromjpeg(__DIR__.'/mirror/'.$booru_dir.'/img/'.$img_name);
				break;
			case 'image/png':
				$im = imagecreatefrompng(__DIR__.'/mirror/'.$booru_dir.'/img/'.$img_name);
				break;
			
			case 'image/gif':
				$im = imagecreatefromgif(__DIR__.'/mirror/'.$booru_dir.'/img/'.$img_name);
				break;

			default:
				# HELP
				$im = null;
				break;
		}
		if($im){
			$newheight = 200;
			$ratio_orig = $width/$height;
			$newwidth = $newheight*$ratio_orig;
			$tmp_img = imagecreatetruecolor($newwidth, $newheight);
			imagecopyresampled($tmp_img, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
			if($getimagesize['mime'] == 'image/gif'){ // little watermark for GIF images (since the output thumbnail isn't animated)
				$textcolor = imagecolorallocate($tmp_img, 255, 0, 0);$bgcolor = imagecolorallocate($tmp_img, 255, 255, 255);
				imagefilledrectangle($tmp_img, 0, 0, 30, 20, $bgcolor);
				imagestring($tmp_img, 6, 2, 1, 'GIF', $textcolor);
			}
			imageinterlace($tmp_img, true);
			imagejpeg($tmp_img, __DIR__.'/mirror/'.$booru_dir.'/thumb/'.substr($img_name, 0, strrpos($img_name, '.')).'.jpg', 60);
			imagedestroy($im);
			imagedestroy($tmp_img);
		}
	}else{
		// Need php5-gd
	}
	return $item;
}

function is_update_unlock($booru_dir){
	$lockfile=__DIR__.'/mirror/'.$booru_dir.'/.update_lock';
	if (file_exists($lockfile)){
		if(filemtime($lockfile) > time()-240) {
			return false;
		} else {
			unlink($lockfile);
		}
	}
	file_put_contents($lockfile, date(DATE_RFC822));
	return true;
}

function update_lock($booru_dir, $is_update_finish){
	if(!$is_update_finish){
		unlink(__DIR__.'/mirror/'.$booru_dir.'/.update_lock');
	}
}
?>
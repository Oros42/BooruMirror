<?php

require_once __DIR__.'/function.php';

function is_in_mirror_dir(){
	$dirname=dirname($_SERVER['SCRIPT_FILENAME']);
	if(__DIR__ != $dirname){
		$dirname = str_replace(array(".", "/"), "", substr($dirname, strlen(__DIR__)));
		if(substr($dirname, 0, 6)=="mirror"){
			return substr($dirname, 6);
		}else{
			// we are lost
			die("I'm lost :-/");
		}
	}else{
		return false;
	}
}


function index_page($config){
	// we are in /BooruMirror/index.php
	echo "index<br/>";
	if(!empty($config)){
		$dirs = scandir('./mirror/');
		unset($dirs[0]);
		unset($dirs[1]);
		foreach ($dirs as $dir) {
			if(is_dir('./mirror/'.$dir)){
				echo '<a href="./mirror/'.$dir.'/">'.$dir.'</a><br/>';//' '.dir_size('./mirror/'.$dir).'<br/>';
			}
		}
	}
}

function mirror_page($booru_name, $config){
	// we are in /BooruMirror/mirror/donmai/
	$db=new Database($booru_name);
	if(!empty($_GET['page'])){
		$page=(int) $_GET['page'];
	}else{
		$page=1;
	}
	$data=$db->listByPage($page);
	$nb_post=$db->get_nb_post();

	echo <<<EOF
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<title>{$config[$booru_name]['name']}</title>
<style type="text/css">body{padding:1em;}a{color:red;text-decoration:none;}table{border:1px dotted #FFCC00;background-color:#FFFFEE;}img.list{max-height:200px;margin:2px;border:1px solid black;} h2 {font-size:small} .info{background-color:#444444;padding:15px;text-align:center;}</style>
<link rel="alternate" type="application/atom+xml" title="ATOM 20 last posts" href="http://127.0.0.1/DMME/?feed">
</head>
<body>
<a href="../..">Home</a>
<h1>
<a href="./">{$config[$booru_name]['name']}</a>
</h1>
<h2>BooruMirror is a free/open-source booru mirroring system. All the content below is automatically fetched and mirrored. Warning: the content is often explicit and NSFW.</h2>
<a href="./?feed">
<img alt="rss" width="13" height="13" src="data:image/gif;base64,R0lGODlhDQANAOYAAAAAAP/////63f/yzP/er//04//brP/t1f/Yqv+0ZP++d//Ii//Sn/+oU//Eh//Gjv/LlP95AP99Av98BP99BP9/B/99Cv+FFf+cP//Hkv/Urf9zAP91AP93AP95Bf94B/+IJf9sAP9vAP9xAP5yBv91B/51CP9/Hv+3gf9pAP9qAP9rAP5sBv5vDP91Ev99IPF2H/+VSuqOTv9gAP9lAftnCf9sDO5nFe1qGP9cAP9eAPxeBPllEO1nHu1qHvFzKeiOW/+wgv+7lf/Jq/9ZAPtWAPNRAPdYCvBZCvNfEupcFe1hF/x/QfxQAPlRAPZNAO9NAOpbG/u3lv/g0vtIAPZIAPNKAOxOCelRFO1VFetVGvNqMO6AU/ZEAPI/AOo/AOg+AOxFCehPF+ZUIOc6AOlDC+5pQN1vSu6BX8+aieI+EOpKHOwxAOotAOcsAOMtANwsAOJBF+Z3W9wqBdw0EtgmB9g7HtQdAOV9b9UUANEXANcgDeBRQf///wAAAAAAACH5BAEAAH0ALAAAAAANAA0AAAeogH1nZF9QSDUsJCYeMH1pZWFHVV1EKiIbFB0yY2xDAQcoLjMjExURLVheQgGsAQobEhMrPFFOOzYvEKwEIyo6SUpOGgEPGxesGU1PWUtEDK0YFqxMbWI9KiolDqwnMQFTbmo+IR8NEgkBBU+sa3A4IQgBAzkCAVtSAXJ3NxwLAQZUggTggiYAHj1AIkQAQcOKFjNx6PDZY6fPjxRFjFwB82ZOnTx0+gQCADs%3D"> feed</a> | <a href="./?sitemap">sitemap</a> | <a href="./?id=random">random</a> | <a href="./?id=random&amp;flow">post flow</a>
<br>$nb_post mirrored posts from <b>
<a href="{$config[$booru_name]['url']}">{$config[$booru_name]['name']}</a>
</b>
<br>
<br>
<form method="get" action="./">
<input type="text" name="s" placeholder="search keyword">
<input type="submit" value="OK">
</form>
<br>
<div id="need_update" style="display:none" class="info"><a href="./">Update done. You can reload this page to see new pictures.</a></div>
EOF;
	$change_page='';
	$nb_page = (int) ( $nb_post/20 + ($nb_post%20>0?1:0) );
	if($nb_page>1){
		if($page>1){
			$change_page='<a href="./?page='.($page-1).'">&larr; newer</a>';
			if($page>11){
				$change_page.=' | <a href="./?page=1">1</a>';
				if($page>12){
					$change_page.='| ... ';
				}
			}
		}
		$end=$page+11;
		$start=$page>10?$page-10:1;
		for($i=$start; $i<=$nb_page && $i <$end; $i++){
			if($i==$page){
				$change_page.=' | '.$i;	
			}else{
				$change_page.=' | <a href="./?page='.$i.'">'.$i.'</a>';	
			}
			
		}
		if($page<$nb_page-1){
			if($i<$nb_page){
				$change_page.='| ... | <a href="./?page='.$nb_page.'">'.$nb_page.'</a>';		
			}
			$change_page.=' | <a href="./?page='.($page+1).'">older &rarr;</a>';
		}		
	}



	echo $change_page."<br/>";
	if(!empty($data)){
		foreach ($data as $item) {
			echo '<a href="./?id='.$item['file_id'].'"><img class="list" title="'.$item['tags'].'" alt="'.$item['file_id'].'" src="./img/'.$item['img_name'].'" max-height="300px"></a>';
		}
	}
	echo "<br/>".$change_page;

	if($config[$booru_name]['enable']){
		echo <<<EOF
<script type="text/javascript">
 function iframe_loaded(){
 	if(document.getElementById('iframe').contentWindow.document.body.innerHTML=="Update done"){
		document.getElementById('need_update').setAttribute('style','');
 	}

}
</script>
<iframe id="iframe" width="1" height="1" src="../../bridge/{$booru_name}.php?update" style="display:none" onload="iframe_loaded();">
EOF;
	}

	echo '</body></html>';
}

function show_image($booru_name, $config){
	// we are in /BooruMirror/mirror/donmai/
	$db=new Database($booru_name);
	if(!empty($_GET['id'])){
		$id=(int) $_GET['id'];
	}else{
		$id=1;
	}
	$data=$db->get_by_id($id);
	$nb_post=$db->get_nb_post();
	echo <<<EOF
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<title>{$config[$booru_name]['name']}</title>
<style type="text/css">body{padding:1em;}a{color:red;text-decoration:none;}table{border:1px dotted #FFCC00;background-color:#FFFFEE;}img.list{max-height:200px;margin:2px;border:1px solid black;} h2 {font-size:small}</style>
<link rel="alternate" type="application/atom+xml" title="ATOM 20 last posts" href="http://127.0.0.1/DMME/?feed">
</head>
<body>
<a href="../..">Home</a><br/>
<a href=".">Back</a>
<h1>
<a href="./">{$config[$booru_name]['name']}</a>
</h1>
<h2>BooruMirror is a free/open-source booru mirroring system. All the content below is automatically fetched and mirrored. Warning: the content is often explicit and NSFW.</h2>
<a href="./?feed">
<img alt="rss" width="13" height="13" src="data:image/gif;base64,R0lGODlhDQANAOYAAAAAAP/////63f/yzP/er//04//brP/t1f/Yqv+0ZP++d//Ii//Sn/+oU//Eh//Gjv/LlP95AP99Av98BP99BP9/B/99Cv+FFf+cP//Hkv/Urf9zAP91AP93AP95Bf94B/+IJf9sAP9vAP9xAP5yBv91B/51CP9/Hv+3gf9pAP9qAP9rAP5sBv5vDP91Ev99IPF2H/+VSuqOTv9gAP9lAftnCf9sDO5nFe1qGP9cAP9eAPxeBPllEO1nHu1qHvFzKeiOW/+wgv+7lf/Jq/9ZAPtWAPNRAPdYCvBZCvNfEupcFe1hF/x/QfxQAPlRAPZNAO9NAOpbG/u3lv/g0vtIAPZIAPNKAOxOCelRFO1VFetVGvNqMO6AU/ZEAPI/AOo/AOg+AOxFCehPF+ZUIOc6AOlDC+5pQN1vSu6BX8+aieI+EOpKHOwxAOotAOcsAOMtANwsAOJBF+Z3W9wqBdw0EtgmB9g7HtQdAOV9b9UUANEXANcgDeBRQf///wAAAAAAACH5BAEAAH0ALAAAAAANAA0AAAeogH1nZF9QSDUsJCYeMH1pZWFHVV1EKiIbFB0yY2xDAQcoLjMjExURLVheQgGsAQobEhMrPFFOOzYvEKwEIyo6SUpOGgEPGxesGU1PWUtEDK0YFqxMbWI9KiolDqwnMQFTbmo+IR8NEgkBBU+sa3A4IQgBAzkCAVtSAXJ3NxwLAQZUggTggiYAHj1AIkQAQcOKFjNx6PDZY6fPjxRFjFwB82ZOnTx0+gQCADs%3D"> feed</a> | <a href="./?sitemap">sitemap</a> | <a href="./?id=random">random</a> | <a href="./?id=random&amp;flow">post flow</a>
<br>$nb_post mirrored posts from <b>
<a href="{$config[$booru_name]['url']}">{$config[$booru_name]['name']}</a>
</b>
<br>
<br>
<form method="get" action="./">
<input type="text" name="s" placeholder="search keyword">
<input type="submit" value="OK">
</form>
<br>
<table style="float:left">
<tr>
 <td class="meta">remote:</td>
  <td id="remote" style="width:300px"><a href="{$config[$booru_name]['url']}">{$config[$booru_name]['name']}</a></td>
</tr>
<tr>
 <td class="meta">id:</td>
  <td id="imageid"><a href="{$data['page_url']}">{$data['file_id']}</a></td>
</tr>
<tr>
	<td class="meta">Posted:</td>
	<td id="date">{$data['posted']}</td>
</tr>
<tr>
	<td class="meta">By:</td>
	<td id="poster"><a href="./?s={$data['author']}">{$data['author']}</a></td>
</tr>
<tr>
	<td class="meta"><br>Tags:</td>
	<td id="tags">
	<ul>
EOF;
	$a=explode(";", $data['tags']);
	if(!empty($a)){
		foreach ($a as $tag) {
			if($tag!=''){
				echo '<li class=""><a title="" href="./?s='.$tag.'">'.$tag.'</a></li>';
			}			
		}
	}
echo <<<EOF
</ul></td>
</tr>
<tr>
	<td class="meta">Width:</td>
	<td id="width">{$data['width']}</td>
</tr>
<tr>
	<td class="meta">Height:</td>
	<td id="height">{$data['height']}</td>
</tr>
<tr>
	<td class="meta">Source:</td>
	<td id="source">{$data['source']}</td>
</tr>
<tr>
	<td class="meta">Rating:</td>
	<td id="rating">{$data['rating']}</td>
</tr>
<tr>
	<td class="meta">Score:</td>
	<td id="score">{$data['score']}</td>
</tr>
<tr>
	<td class="meta">Format:</td>
	<td id="format"></td>
</tr>
<tr>
	<td class="meta">Filesize:</td>
	<td id="filesize"></td>
</tr>
<tr>
	<td class="meta">md5:</td>
	<td id="checksum"></td>
</tr>
<tr>
	<td class="meta">sha1:</td>
	<td id="checksum_sha1"></td>
</tr>
<tr>
	<td class="meta">JSON data:</td>
	<td id="json"><a href="./?id=169973&amp;raw">169973.json</a></td>
</tr>
</table>
<a href="./img/{$data['img_name']}" style="float:left;position: absolute;"><img class="post" alt="{$data['tags']}" src="./img/{$data['img_name']}" width="1000px"></a>
</body></html>
EOF;

}

function get_raw($booru_name, $config){
	echo '<a href=".">Back</a><br/>';
	echo "TODO";
}

function get_feed($booru_name, $config){
	echo '<a href=".">Back</a><br/>';
	echo "TODO";
}

function get_sitemap($booru_name, $config){
	echo '<a href=".">Back</a><br/>';
	echo "TODO";
}

function find_by_tag($booru_name, $config){
	echo '<a href=".">Back</a><br/>';
	echo "TODO";
}


$config = get_config();

if(!empty($config)){
	$dir=is_in_mirror_dir();
	if($dir){
		require_once __DIR__.'/database.php';
		if(isset($_GET)){
			switch (true) {
				case !empty($_GET['id']):
					if(isset($_GET['raw'])){
						get_raw($dir, $config);
					}else{
						show_image($dir, $config);
					}
					break;
				
				case isset($_GET['feed']):
					get_feed($dir, $config);
					break;

				case isset($_GET['sitemap']):
					get_sitemap($dir, $config);
					break;

				case !empty($_GET['s']):
					find_by_tag($dir, $config);
					break;

				default:
					mirror_page($dir, $config);
					break;
			}
		}else{
			mirror_page($dir, $config);
		}
	}else{
		index_page($config);
	}
}
?>
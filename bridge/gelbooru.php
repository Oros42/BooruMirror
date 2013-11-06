<?php
$site='http://gelbooru.com/';
$site_rss='http://gelbooru.com/index.php?page=cooliris';
$booru_name='Gelbooru';
$booru_dir='gelbooru';

if(isset($_GET['update']) && !isset($_POST['init_booru'])){
	require_once __DIR__.'/../function.php';
	$config = get_config();
	if(isset($config[$booru_dir]) && $config[$booru_dir]['enable']) {
		$dom = new DOMDocument();
		$data=proxy_file_get_contents($site_rss);
		if(!empty($data) && $dom->loadXML($data)){
			$flux_rss = $dom->getElementsByTagName('link');
			if($flux_rss->length > 0){
				require_once __DIR__.'/../database.php';
				$db=new Database($booru_dir);

				$last_insert=$db->get_last_insert();
				if(!empty($last_insert)){
					$start=0;
					// search if last insert image is in list
					for ($start=0; $start < $flux_rss->length ; $start++) { 
						if($last_insert['page_url']==$flux_rss->item($start)->nodeValue){
							break;
						}
					}
					$start--;
				}else{
					$start=$flux_rss->length-1;
				}
				for($i=$start; $i>2;$i--){
					$item=array();
					$item['page_url']=$flux_rss->item($i)->nodeValue;
					$item['file_id']=substr($item['page_url'], strrpos($item['page_url'], '=')+1);
					$dom2 = new DOMDocument();
					if(@$dom2->loadHTML(proxy_file_get_contents($item['page_url']), LIBXML_ERR_NONE)){ // get page
						if($dom2->getElementById('image')){ // if not deleted
							$data = $dom2->getElementById('image')->getAttribute('src');
							if(!empty($data)){
								// download image
								$data=str_replace(array('samples', 'sample_'), array('images', ''), $data);
								$item['img_url']=$data;
								if(strrpos($data, "?")>0){
									$item['img_name']=htmlentities(substr($data, strrpos($data, "/")+1, - (strlen($data)-strrpos($data, "?"))));
								}else{
									$item['img_name']=htmlentities(substr($data, strrpos($data, "/")+1));
								}
								file_put_contents(__DIR__.'/../mirror/'.$booru_dir.'/img/'.$item['img_name'], proxy_file_get_contents($data));
							}
							// get stats
							$data = $dom2->getElementById('stats')->getElementsByTagName('li');
							if(!empty($data)){
								$item['posted']=substr($data->item(1)->nodeValue, 8, 19);
								$item['author']=substr($data->item(1)->nodeValue, 31);
								$item['author_url']=$data->item(1)->getAttribute('href');
								$a=explode('x',substr($data->item(2)->nodeValue, 6));
								$item['width']=$a[0];
								$item['height']=$a[1];
								$a=$data->item(3)->getElementsByTagName('a');
								if(!empty($a) && $a->length >0){
									$item['source']=$a->item(0)->getAttribute('href');
								}else{
									$item['source']= substr($data->item(3)->nodeValue, 9);
								}
								$item['rating']=substr($data->item(4)->nodeValue, 8);
								$a=$data->item(5)->getElementsByTagName('span');
								if(!empty($a) && $a->length >0){
									$item['score']=$a->item(0)->nodeValue;
								}else{
									$item['score']="";
								}
								$item['tags']='';
								$data = $dom2->getElementById('tag-sidebar')->getElementsByTagName('li');
								if(!empty($data)){
									foreach ($data as $tag) {
										$a=$tag->getElementsByTagName('a');
										if(!empty($a) && $a->length >0){
											$item['tags'].=$a->item(1)->nodeValue.";";
										}
									}
								}
							}
						}
					}
					$item=array_merge($item, make_thumb($booru_dir, $item['img_name']));
					$db->insert($item);
				}
				if($start>2){
					echo 'Update done';
				}else{
					echo 'No news';
				}
			}else{
				echo 'error';
			}
		}else{
			echo "Networking error ! Cant't access to $site_rss";
		}
	}
}
?>
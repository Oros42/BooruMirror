<?php
$site='http://donmai.us/';
$site_rss='http://donmai.us/posts.atom';
$booru_name='Donmai';
$booru_dir='donmai';

if(isset($_GET['update']) && !isset($_POST['init_booru'])){
	require_once __DIR__.'/../function.php';
	$config = get_config();
	if(isset($config[$booru_dir]) && $config[$booru_dir]['enable'] && is_update_unlock($booru_dir)) {
		$dom = new DOMDocument();
		$data=proxy_file_get_contents($site_rss);
		if(!empty($data) && $dom->loadXML($data)){
			$flux_rss = $dom->getElementsByTagName('entry');
			if($flux_rss->length > 0){
				require_once __DIR__.'/../database.php';
				$db=new Database($booru_dir);

				$last_insert=$db->get_last_insert();
				if(!empty($last_insert)){
					$start=0;
					// search if last insert image is in list
					for ($start=0; $start < $flux_rss->length ; $start++) { 
						if($last_insert['page_url']==$flux_rss->item($start)->getElementsByTagName('a')->item(0)->getAttribute('href')){
							break;
						}
					}
					$start--;
				}else{
					$start=$flux_rss->length-1;
				}
				for($i=$start; $i>=0;$i--){
					$item=array();
					$item['page_url']=$flux_rss->item($i)->getElementsByTagName('a')->item(0)->getAttribute('href'); // FIXME some time : error Call to a member function getElementsByTagName()
					$item['file_id']=substr($item['page_url'], strrpos($item['page_url'], '/')+1);
					$dom2 = new DOMDocument();
					if(@$dom2->loadHTML(proxy_file_get_contents($item['page_url']), LIBXML_ERR_NONE)){ // get page
						if($dom2->getElementById('image')){ // if not deleted
							$data = $dom2->getElementById('image-resize-link');
							if(!empty($data)){
								$data = $data->getAttribute('href');
							}else{
								$data = $dom2->getElementById('image')->getAttribute('src');	
							}					
							if(!empty($data)){
								// download image
								$data=str_replace('sample/sample-', '', $data);
								$item['img_url']=$site.substr($data, 1);
								if(strrpos($data, "?")>0){
									$item['img_name']=htmlentities(substr($data, strrpos($data, "/")+1, - (strlen($data)-strrpos($data, "?"))));
								}else{
									$item['img_name']=htmlentities(substr($data, strrpos($data, "/")+1));
								}
								file_put_contents(__DIR__.'/../mirror/'.$booru_dir.'/img/'.$item['img_name'], proxy_file_get_contents($item['img_url']));
							}
							// get stats
							$data = $dom2->getElementById('sidebar')->childNodes->item(4)->getElementsByTagName('li');
							if(!empty($data)){
								$item['posted']=substr($data->item(2)->getElementsByTagName('time')->item(0)->getAttribute('title'), 0, -6);
								$item['author']=$data->item(1)->getElementsByTagName('a')->item(0)->nodeValue;
								$item['author_url']=$site.substr($data->item(1)->getElementsByTagName('a')->item(0)->getAttribute('href'), 1);
								$a=explode(" ", trim($data->item(3)->nodeValue));
								$a=explode('x',substr($a[count($a)-1], 1,-1));
								if(count($a)==2){
									$item['width']=$a[0];
									$item['height']=$a[1];
								}
								if($data->item(4)->getElementsByTagName('a')->length>0){
									$item['source']=$data->item(4)->getElementsByTagName('a')->item(0)->getAttribute('href');
								}else{
									$item['source']=substr($data->item(4)->nodeValue, 8);
								}
								$item['rating']=substr($data->item(5)->nodeValue, 8);
								$item['score']=$data->item(7)->getElementsByTagName('span')->item(0)->nodeValue;
								$item['tags']='';
								$data = $dom2->getElementById('tag-list')->childNodes->item($dom2->getElementById('tag-list')->childNodes->length-1)->getElementsByTagName('li');
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
				if($start>=0){
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
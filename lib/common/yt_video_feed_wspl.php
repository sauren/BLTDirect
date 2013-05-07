<?php

	function get_playlist(){
		$data = file_get_contents("http://gdata.youtube.com/feeds/api/videos?author=BLTDirect&orderby=published");
		$xml = simplexml_load_string($data);

		$count = 0;
		$total = count($xml->entry);

		foreach($xml->entry as $playlist){
			$count ++;

			$video = new stdClass;
			$video->title = (string)$playlist->title;
			$video->content = (string)$playlist->content;
			$video->author = (string)$playlist->author->name;
			$video->publish = (string)$playlist->published;

			$video->url = (string)$playlist->link[0]->attributes()->href;

			preg_match('/[\\?&]v=([^&#]*)/', $video->url, $matches);
            $video->Thumbnail = sprintf('//img.youtube.com/vi/%s/0.jpg', $matches[1]);

            $video->published = explode('T', $video->publish);
            $video->published = $video->published[0];
            $video->published = getDateFormat($video->published);
			$url = $video->url;

			parse_str(parse_url($url, PHP_URL_QUERY ), $my_array_of_vars);
			$vid_Id = $my_array_of_vars['v'];

			//$name = str_replace(' ', '+', $video->title);

			$link = (sprintf('%s./yt_videos.php?video=true&cid=%s', $GLOBALS['SITE_ROOT'], $vid_Id));
			$last = $count==$total?"last":"";

			echo $thumbnail = '<div class="videoCatFrame'.' '.$last.'">

				<a href="' . $link .'">
					<div class="videoThumbnail">
							<img src="' . $video->Thumbnail .'" title="' . $video->title .'" />
					</div>
					<div class="videoInfo">
						<h1>' . $video->title .'</h1>
						<p>' . $video->content .'</p>
					</div>
					<div class="clear"></div>
					<span class="published"><strong>' . $video->published .'</strong></span>					
				</a>
			</div>';
		}
	}

	function get_VideoInfo($id){
		$vidID = $id;

		$data = file_get_contents(sprintf('http://gdata.youtube.com/feeds/api/videos/%s', $vidID));
		$xml = simplexml_load_string($data);

		if(isset($xml)){
			$video = new stdClass;
			$video->title = (string)$xml->title;
			$video->content = (string)$xml->content;
			$video->author = (string)$xml->author->name;

			$publish = (string)$xml->published;
			$video->published = explode('T', $publish);
	        $video->published = $video->published[0];
	        $video->published = getDateFormat($video->published);

			$video->url = (string)$xml->link[0]->attributes()->href;

			return $video;
		}else{
			return false;
		}
	}


	function get_product_yt_video($vid, $pid){
		$vidID = $vid;
		$youtubeLink = "//www.youtube.com/watch?v=";
		$youtubeFeature = "&feature=player_embedded";
		$youtubeURL = $youtubeLink.$vidID.$youtubeFeature;


		$data = file_get_contents(sprintf('http://gdata.youtube.com/feeds/api/videos/%s', $vidID));
		$xml = simplexml_load_string($data);

		if(isset($xml)){
			$video = new stdClass;
			$video->title = (string)$xml->title;
			$video->content = (string)$xml->content;
			$video->author = (string)$xml->author->name;
			$video->url = (string)$xml->link[0]->attributes()->href;

			preg_match('/[\\?&]v=([^&#]*)/', $video->url, $matches);
            $video->Thumbnail = sprintf('//img.youtube.com/vi/%s/0.jpg', $matches[1]);

			$publish = (string)$xml->published;
			$video->published = explode('T', $publish);
	        $video->published = $video->published[0];
	        $video->published = getDateFormat($video->published);

	        $link =(sprintf('#Player'));
	        $rel = "yt_videos";

			echo $thumbnail = '<div class="ytFrame">
					<a href="' . $youtubeURL .'" rel="'.$rel.'" data-video="'.$vid.'" title="'.$video->title.'"target="_blank">
						<img src="' . $video->Thumbnail .'" title="' . $video->title . '" width="60" height="38" />
						<span class="playIcon"></span>
					</a>
				</div>';
			return $true;
		}else{
			return false;
		}

	}
	
		function get_product_yt_videos($vid, $pid){
		$vidID = $vid;
		$var=$GLOBALS['MOBILE_LINK'];
		$var1=explode('/', $var);
		$youtubeLink = $GLOBALS['HTTP_SERVER'].$var1[1].'/';
		$youtubeURL = $youtubeLink.'yt_videos.php?video=true&cid=' . $vidID;

		$data = file_get_contents(sprintf('http://gdata.youtube.com/feeds/api/videos/%s', $vidID));
		$xml = simplexml_load_string($data);

		if(isset($xml)){
			$video = new stdClass;
			$video->title = (string)$xml->title;
			$video->content = (string)$xml->content;
			$video->author = (string)$xml->author->name;
			$video->url = (string)$xml->link[0]->attributes()->href;

			preg_match('/[\\?&]v=([^&#]*)/', $video->url, $matches);
            $video->Thumbnail = sprintf('//img.youtube.com/vi/%s/0.jpg', $matches[1]);

			$publish = (string)$xml->published;
			$video->published = explode('T', $publish);
	        $video->published = $video->published[0];
	        $video->published = getDateFormat($video->published);

	        $link =(sprintf('#Player'));
	        $rel = "yt_videos";

			echo $thumbnail = '<div class="ytFrame">
					<a href="' . $youtubeURL .'" rel="'.$rel.'" data-video="'.$vid.'" title="'.$video->title.'"target="">
						<img src="' . $video->Thumbnail .'" title="' . $video->title . '" width="60" height="38" />
						<span class="playIcon"></span>
					</a>
				</div>';
			return $true;
		}else{
			return false;
		}

	}

?>

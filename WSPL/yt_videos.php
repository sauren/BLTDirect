<?php
	require_once('../lib/common/appHeadermobile.php');
    require_once('../lib/common/yt_video_feed_wspl.php');

    $id = "";
    $video = "";

    if(param('video')){
        $id = param('cid');
        $video = get_VideoInfo($id);
    }

?>
<?php
include("ui/nav.php");
include("ui/search.php");?>
 <script type="text/javascript" src="js/swfobject.js"></script>

    <script>
        // Load the IFrame Player API code asynchronously.
        var tag = document.createElement('script');
        tag.src = "https://www.youtube.com/player_api";
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);


      // Replace the 'ytplayer' element with an <iframe> and
      // YouTube player after the API code downloads.
        var player;
        function onYouTubePlayerAPIReady() {
            player = new YT.Player('ytplayer', {
                height: '320',
                width: '95%',
                videoId: '<?php echo preg_replace('/[^a-zA-Z0-9_-]/','',param('cid')); ?>',
                playerVars: {'wmode': 'transparent' }
            });
        }
    </script>
    

<div class="maincontent">
<div class="maincontent1"> 
                   <?php if(param('video')){?>
                    <div class="videoplayer">
                         <div class="videoHeading">
                            <h2><?php echo $video->title; ?></h2>
                        </div>
                        <br/>
                        <div id="ytplayer"></div>
                        <div class="videoContent">
                            <p><?php echo $video->content; ?></p>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <?php } else { ?>
                         <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">BLT Direct Videos</span></div>
                         <br/>
                    <?php get_playlist();
                        }
                    ?>
</div>
</div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>
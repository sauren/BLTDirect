<?php
	require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FAQCollection.php');

	$faqs = new FAQCollection();
	$faqs->Get();?>
    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Frequently Asked Questions</span></div>
<div class="maincontent">
<div class="maincontent1">
        <p style="text-align:justify">We make every effort in answering your questions. Below is a list of those questions we are frequently asked. Please consult these before <a href="support.php">contacting us.</a> Click on a question below to reveal its answer. </p>

<script>
	function faq(obj){
		if(document.getElementById){
			var faq = document.getElementById(obj);
			if(faq.style.display == "none"){
				faq.style.display = "";
			} else {
				faq.style.display = "none";
			}
		}
	}
</script>
		<ul class="faq">
		<?php
			for($i=0; $i < count($faqs->Item); $i++){
				echo sprintf('<li><a href="javascript:faq(\'faq_%d\');">%s</a><div id="faq_%d" style="display:none">%s</div></li>', $i, $faqs->Item[$i]->Question, $i, nl2br($faqs->Item[$i]->Answer));
			}
		?>
		</ul>
        </div>
        </div>
        <?php include("ui/footer.php")?>
        <?php include('../lib/common/appFooter.php'); ?>
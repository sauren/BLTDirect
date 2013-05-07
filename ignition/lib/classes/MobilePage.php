<?php
class MobilePage {
	public $Title;
	public $Description;
	public $Head;
	public $Body;
	private $OnLoad;

	public function __construct($title = '', $description = '') {
		$this->Title = $title;
		$this->Description = $description;
	}

	public function AddToHead($content) {
		$this->Head .= $content;
		$this->Head .= "\n";
	}

	public function AddToBody($content) {
		$this->Body .= $content;
		$this->Body .= "\n";
	}

	public function AddOnLoad($content) {
		$this->OnLoad .= $content;
		$this->OnLoad .= "; ";
	}

	public function SetFocus($elementId) {
		$this->AddOnLoad(sprintf("document.getElementById('%s').focus()", $elementId));
	}

	public function Display($section = NULL){
		header("Content-Type: text/html; charset=UTF-8");

		if(strtolower($section) == 'header') {
			$this->OnLoad = !empty($this->OnLoad) ? sprintf('onload="%s"', $this->OnLoad) : '';

			echo '<html>';
			echo '<head>';
			echo '<title>' . $this->Title . '</title>';
			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
			echo '<link rel="stylesheet" href="css/default.css" type="text/css">';
			echo $this->Head;
			echo '</head>';
			echo '<body ' . $this->OnLoad . '>';

			if(!empty($this->Title)) {
				echo sprintf('<span class="pageTitle">%s</span><br>', $this->Title);

				if(!empty($this->Description)) {
					echo sprintf('<span class="pageDescription">%s</span><br>',  $this->Description);
				}

				echo '<br />';
			}
		} elseif(strtolower($section) == 'footer') {
			echo "</body></html>";
		}
	}
}
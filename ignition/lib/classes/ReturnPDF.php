<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'packages/mPDF/mpdf.php');

class ReturnPDF extends mPDF {
	public $Freepost;

	public function __construct() {
		parent::mPDF('', array(620 / (72/25.4), 310 / (72/25.4)));
		
		$this->SetAuthor($GLOBALS['COMPANY']);
		$this->SetImportUse();

		$this->Freepost = false;
	}

	public function AddPage() {
		parent::AddPage();

		$this->SetSourceFile(sprintf('%s/lib/templates/pdf/%s.pdf', $GLOBALS["DIR_WS_ADMIN"], $this->Freepost ? 'postage_label_freepost' : 'postage_label'));
		$this->UseTemplate($this->ImportPage(1));
	}
}
<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'packages/mPDF/mpdf.php');

class StandardPDF extends mPDF {
	public $PrintPages;
	private $Pages;
	private $ImportedRepeatTemplate;

	public function __construct() {
		$this->PrintPages = false;
		
		$this->ResetPages();
		
		parent::mPDF('', 'A4', 0, '', 10, 10, 20, 20, 10, 10, 'P');

		$this->SetAuthor($GLOBALS['COMPANY']);
		$this->SetImportUse();
	}
	
	public function ResetPages() {
		$this->Pages = 0;
		$this->ImportedRepeatTemplate = false;
	}

	public function AddPage() {		
		parent::AddPage('P', '', '', '', '', 10, 10, 20, 20, 10, 10);

		if($this->Pages == 0) {
			$this->SetSourceFile(sprintf('%slib/templates/pdf/%s.pdf', $GLOBALS["DIR_WS_ADMIN"], !$this->PrintPages ? 'letter_head' : 'letter_head_address_only'));
			$this->UseTemplate($this->ImportPage(1));
		} else {
			if(!$this->ImportedRepeatTemplate) {
				$this->ImportedRepeatTemplate = true;
				
				$this->SetSourceFile(sprintf('%slib/templates/pdf/%s.pdf', $GLOBALS["DIR_WS_ADMIN"], !$this->PrintPages ? 'letter_head_logo_only' : 'letter_head_blank'));
			}
			
			$this->UseTemplate($this->ImportPage(1));
		}

		$this->Pages++;
	}
	
	public function WriteHTML($html, $sub=0, $init = true, $close = true) {
		$items = explode('[PAGE_BREAK]', $html);

		foreach($items as $item) {
			$this->AddPage();
			
			parent::WriteHTML(trim($item), $sub, $init, $close);
		}
	}
}
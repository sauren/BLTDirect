<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/pdf/fpdi.php');

class AffiliatePDF extends FPDI {
	var $Pages;
	var $ImportedRepeatTemplate;

	function __construct() {
		parent::FPDF_TPL();

		$this->SetAuthor($GLOBALS['COMPANY']);
		$this->Pages = 0;
		$this->ImportedRepeatTemplate = false;
	}

	function AddPage() {
		$this->SetTopMargin(30);
		$this->SetLeftMargin(10);
		$this->SetRightMargin(10);
		$this->SetFontSize(11);

		parent::AddPage();

		if($this->Pages == 0) {
			$this->setSourceFile($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/pdf/letter_head.pdf');
			$this->useTemplate($this->importPage(1));
		} else {
			if(!$this->ImportedRepeatTemplate) {
				$this->setSourceFile($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/pdf/letter_head_logo_only.pdf');
				$this->ImportedRepeatTemplate = true;
			}

			$this->useTemplate($this->importPage(1));
		}

		$this->Pages++;
	}

	function Footer() {}
}
?>
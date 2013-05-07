<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/pdf/fpdi.php');

class CampaignPDF extends FPDI {
	var $Pages;
	var $ImportedRepeatTemplate;
	var $NewLetter;
	var $HasLetterHead;

	function CampaignPDF() {
		parent::FPDF_TPL();

		$this->SetAuthor($GLOBALS['COMPANY']);
		$this->Pages = 0;
		$this->ImportedRepeatTemplate = false;
		$this->NewLetter = false;
		$this->HasLetterHead = true;
	}

	function AddPage() {
		$this->SetTopMargin(30);
		$this->SetLeftMargin(10);
		$this->SetRightMargin(10);
		$this->SetFontSize(11);

		parent::AddPage();

		if($this->NewLetter) {
			$this->NewLetter = false;
			$this->Pages = 0;
			$this->ImportedRepeatTemplate = false;
		}

		if($this->Pages == 0) {
			if($this->HasLetterHead) {
				$this->setSourceFile($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/pdf/letter_head_address_only.pdf');
				$this->useTemplate($this->importPage(1));
			}
		} else {
			if(!$this->ImportedRepeatTemplate) {
				$this->ImportedRepeatTemplate = true;

				if($this->HasLetterHead) {
					$this->setSourceFile($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/pdf/letter_head_address_only.pdf');
				} else {
					$this->setSourceFile($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/pdf/letter_head_blank.pdf');
				}
			}

			$this->useTemplate($this->importPage(1));
		}

		$this->Pages++;
	}

	function Footer() {}
}
?>
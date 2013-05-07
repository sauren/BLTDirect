<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/pdf/fpdi.php');

class CouponPDF extends FPDI {

	function CouponPDF() {
		parent::FPDF_TPL('P', 'mm', 'A4');

		$this->SetAutoPageBreak(false, 0);
	}

	function WriteHTML($html = "") {
		parent::WriteHTML("".$html);
	}

	function Footer() {}
}
?>
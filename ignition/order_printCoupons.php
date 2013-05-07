<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CouponPDF.php');

$session->Secure(3);

$order = new Order($_REQUEST['orderid']);
$order->Customer->Get();
$order->Customer->Contact->Get();

$coupon = new Coupon();

if(!$coupon->GetIntroductoryCoupon($order->Customer->ID)) {
	$coupon->GenerateIntroductoryCoupon($order->Customer->ID);
}

$fileName = sprintf('coupon_%s.pdf', $order->Customer->ID);

$alignment = '<table width="100%">';
$alignment .= '<tr>';
$alignment .= sprintf('<td width="17%%"></td>');
$alignment .= sprintf('<td width="29%%">%s</td>', $coupon->Reference);
$alignment .= sprintf('<td width="29%%">%s</td>', $coupon->Reference);
$alignment .= sprintf('<td width="25%%">%s</td>', $coupon->Reference);
$alignment .= '</tr>';
$alignment .= '<tr>';
$alignment .= '<td colspan="4" height="275"></td>';
$alignment .= '</tr>';
$alignment .= '<tr>';
$alignment .= sprintf('<td width="17%%"></td>');
$alignment .= sprintf('<td width="29%%">%s</td>', $coupon->Reference);
$alignment .= sprintf('<td width="29%%">%s</td>', $coupon->Reference);
$alignment .= sprintf('<td width="25%%">%s</td>', $coupon->Reference);
$alignment .= '</tr>';
$alignment .= '</table>';

$pdf = new CouponPDF();
$pdf->SetAuthor($GLOBALS['COMPANY']);
$pdf->SetFont('Courier', '', 14);
$pdf->SetLeftMargin(5);
$pdf->SetRightMargin(0);
$pdf->SetTopMargin(87);
$pdf->AddPage();
$pdf->Rotate(180, 92.5);
$pdf->WriteHTML(sprintf("%s", $alignment));

$pdf->Output($fileName, 'F', false, $GLOBALS["TEMP_COUPON_DOCUMENT_DIR_FS"]);

redirect(sprintf("Location: %s%s", $GLOBALS["TEMP_COUPON_DOCUMENT_DIR_WS"], $fileName));
?>
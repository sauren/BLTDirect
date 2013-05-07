<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Category.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Google Sitemap';
$fileName = 'google_sitemap.php';

## BEGIN SCRIPT
$links = array();
$linkHash = array();

function addLink($link, $insertHttpServer = true) {
	global $links;
	global $linkHash;

	$link = trim($link);
	$link = sprintf('%s%s', (($insertHttpServer) ? $GLOBALS['HTTP_SERVER'] : ''), htmlentities($link));

	$hash = md5($link);

	if(!isset($linkHash[$hash])) {
		$links[] = $link;
		$linkHash[$hash] = $link;
	}
}

$excludeFiles = array();
$excludeFiles['application.php'] = true;
$excludeFiles['authenticate.php'] = true;
$excludeFiles['authverify.php'] = true;
$excludeFiles['blank.html'] = true;
$excludeFiles['breach.php'] = true;
$excludeFiles['businessProfile.php'] = true;
$excludeFiles['cancel.php'] = true;
$excludeFiles['cancelNotes.php'] = true;
$excludeFiles['changePassword.php'] = true;
$excludeFiles['checkout.php'] = true;
$excludeFiles['complete.php'] = true;
$excludeFiles['customise.php'] = true;
$excludeFiles['despatch_note.php'] = true;
$excludeFiles['duplicate.php'] = true;
$excludeFiles['email.php'] = true;
$excludeFiles['eNotes.php'] = true;
$excludeFiles['enquiries.php'] = true;
$excludeFiles['enquiryDownload.php'] = true;
$excludeFiles['invoice.php'] = true;
$excludeFiles['invoices.php'] = true;
$excludeFiles['orderNotes.php'] = true;
$excludeFiles['orders.php'] = true;
$excludeFiles['ordersconfirmation.php'] = true;
$excludeFiles['payment.php'] = true;
$excludeFiles['profile.php'] = true;
$excludeFiles['quote.php'] = true;
$excludeFiles['quoteNotes.php'] = true;
$excludeFiles['quotes.php'] = true;
$excludeFiles['return.php'] = true;
$excludeFiles['returnorder.php'] = true;
$excludeFiles['samples.php'] = true;
$excludeFiles['summary.php'] = true;

if($handle = opendir($GLOBALS['DIR_WS_ROOT'])) {
	while(false !== ($file = readdir($handle))) {
		if(($file != '.') && ($file != '..') && (substr($file, 0, 1) != '.')) {
			if(!is_dir($GLOBALS['DIR_WS_ROOT'].$file)) {
				$pos = stripos(strrev($file), '.');
				if($pos !== false) {
					$ext = trim(strtolower(substr($file, strlen($file) - $pos)));

					switch($ext) {
						case 'php':
						case 'html':
						case 'htm':
							if(!isset($excludeFiles[$file])) {
								addLink($file);
							}

							break;
					}
				}
			}
		}
	}

	closedir($handle);
}

$data = new DataQuery(sprintf("SELECT Product_ID, Product_Title, Meta_Title FROM product WHERE Is_Active='Y' AND Integration_ID>0 ORDER BY Product_ID ASC"));
while($data->Row) {
	addLink(sprintf('product.php?pid=%d&nm=%s', $data->Row['Product_ID'], !empty($data->Row['Meta_Title']) ? urlencode($data->Row['Meta_Title']) : urlencode(strip_tags($data->Row['Product_Title']))));

	$data->Next();
}
$data->Disconnect();

$subCategory = new Category();

$data = new DataQuery(sprintf("SELECT Category_ID, Category_Title, Meta_Title, Is_Redirecting, Redirect_Url FROM product_categories WHERE Is_Active='Y' ORDER BY Category_ID ASC"));
while($data->Row) {
	$subCategory->ID = $data->Row['Category_ID'];
	$subCategory->Name = $data->Row['Category_Title'];
	$subCategory->MetaTitle = $data->Row['Meta_Title'];

	$url = $subCategory->GetUrl();

	if(($data->Row['Redirect_Url'] == 'Y') && !empty($data->Row['Redirect_Url'])) {
		if(stripos($data->Row['Redirect_Url'], $GLOBALS['HTTP_SERVER']) !== false) {
			addLink($data->Row['Redirect_Url'], false);
		}
	} else {
		addLink(substr($url, 1));
	}

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Article_Category_ID, Category_Title, Meta_Title FROM article_category ORDER BY Article_Category_ID ASC"));
while($data->Row) {
	addLink(sprintf('article.php?id=%d&nm=%s', $data->Row['Article_Category_ID'], !empty($data->Row['Meta_Title']) ? urlencode($data->Row['Meta_Title']) : urlencode($data->Row['Category_Title'])));

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT name FROM product_landing ORDER BY name ASC"));
while($data->Row) {
	addLink(str_replace(' ', '-', strtolower(trim($data->Row['name']))));
		
	$data->Next();	
}
$data->Disconnect();

if($handle = opendir($GLOBALS['GOOGLE_SITEMAP_FS'])) {
	while(false !== ($file = readdir($handle))) {
		if(($file != '.') && ($file != '..') && (substr($file, 0, 1) != '.')) {
			if(preg_match('/^sitemap([0-9]{2}).xml*$/', $file)) {
				@unlink($GLOBALS['GOOGLE_SITEMAP_FS'].$file);
			}
		}
	}

	closedir($handle);
}

$fhIndex = fopen(sprintf('%ssitemap.xml', $GLOBALS['GOOGLE_SITEMAP_FS']), 'w');
if($fhIndex) {
	$xmlIndex = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$xmlIndex .= "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd\">\n";

	$fileCount = isset($GLOBALS['GOOGLE_SITEMAP_MAX']) ? $GLOBALS['GOOGLE_SITEMAP_MAX'] : 1;
	$pos = 0;

	for($i=0; $i<$fileCount; $i++) {
		$sitemapFileName = sprintf('sitemap%s.xml', ($i+1));

		$fh = fopen(sprintf('%s%s', $GLOBALS['GOOGLE_SITEMAP_FS'], $sitemapFileName), 'w');
		if($fh) {
			$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
			$xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";

			$splitAt = ceil((count($links) - $pos) / ($fileCount - $i));
			$storedPos = $pos;

			for($j=$storedPos; $j<$storedPos+$splitAt; $j++) {
				$xml .= sprintf("\t<url>\n");
				$xml .= sprintf("\t\t<loc>%s</loc>\n", $links[$j]);
				$xml .= sprintf("\t\t<lastmod>%s</lastmod>\n", date('Y-m-d\TH:i:s'). substr_replace(date('O'), ':', 3, 0));
				$xml .= sprintf("\t\t<changefreq>weekly</changefreq>\n");
				$xml .= sprintf("\t\t<priority>0.5</priority>\n");
				$xml .= sprintf("\t</url>\n");

				$log[] = sprintf("Adding URL: %s", $links[$j]);

				$pos++;
			}

			$xml .= "</urlset>\n";

			fwrite($fh, $xml);
			fclose($fh);

			@unlink($GLOBALS['GOOGLE_SITEMAP_FS'].$sitemapFileName.'.gz');

			if($fpOut = gzopen(sprintf('%s%s.gz', $GLOBALS['GOOGLE_SITEMAP_FS'], $sitemapFileName), 'wb9')) {
				if($fpIn = fopen(sprintf('%s%s', $GLOBALS['GOOGLE_SITEMAP_FS'], $sitemapFileName), 'rb')) {
					while(!feof($fpIn)) {
						gzwrite($fpOut, fread($fpIn, 1024*512));
					}

					fclose($fpIn);
				}

				gzclose($fpOut);
			}

			@unlink($GLOBALS['GOOGLE_SITEMAP_FS'].$sitemapFileName);

			$xmlIndex .= sprintf("\t<sitemap>\n");
			$xmlIndex .= sprintf("\t\t<loc>%s%s.gz</loc>\n", $GLOBALS['HTTP_SERVER'], $sitemapFileName);
			$xmlIndex .= sprintf("\t\t<lastmod>%s</lastmod>\n", date('Y-m-d\TH:i:s'). substr_replace(date('O'), ':', 3, 0));
			$xmlIndex .= sprintf("\t</sitemap>\n");
		}
	}

	$xmlIndex .= "</sitemapindex>\n";

	fwrite($fhIndex, $xmlIndex);
	fclose($fhIndex);

	@unlink($GLOBALS['GOOGLE_SITEMAP_FS'].'sitemap.xml.gz');

	if($fpOut = gzopen(sprintf('%ssitemap.xml.gz', $GLOBALS['GOOGLE_SITEMAP_FS']), 'wb9')) {
		if($fpIn = fopen(sprintf('%ssitemap.xml', $GLOBALS['GOOGLE_SITEMAP_FS']), 'rb')) {
			while(!feof($fpIn)) {
				gzwrite($fpOut, fread($fpIn, 1024*512));
			}

			fclose($fpIn);
		}

		gzclose($fpOut);
	}

	@unlink($GLOBALS['GOOGLE_SITEMAP_FS'].'sitemap.xml');
}
## END SCRIPT

$logHeader[] = sprintf("Script: %s", $script);
$logHeader[] = sprintf("File Name: %s", $fileName);
$logHeader[] = sprintf("Date Executed: %s", date('Y-m-d H:i:s'));
$logHeader[] = sprintf("Execution Time: %s seconds", number_format(microtime(true) - $timing, 4, '.', ''));
$logHeader[] = '';

$log = array_merge($logHeader, $log);

if($mailLog) {
	$mail = new htmlMimeMail5();
	$mail->setFrom('root@bltdirect.com');
	$mail->setSubject(sprintf("Cron [%s] <root@bltdirect.com> php /var/www/vhosts/bltdirect.com/httpdocs/cron/%s", $script, $fileName));
	$mail->setText(implode("\n", $log));
	$mail->send(array('adam@azexis.com'));
}

echo implode("<br />", $log);

$GLOBALS['DBCONNECTION']->Close();
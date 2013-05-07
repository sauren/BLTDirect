<?php


// Optionally define a folder which contains TTF fonts
// mPDF will look here before looking in the usual _MPDF_TTFONTPATH
// Useful if you already have a folder for your fonts
// e.g. on Windows: define("_MPDF_SYSTEM_TTFONTS", 'C:/Windows/Fonts/');

//define("_MPDF_SYSTEM_TTFONTS", 'C:/Windows/Fonts/');

// Optionally set a font (defined below in $this->fontdata) to use for missing characters
// when using useSubstitutionsMB. Use a font like Arial Unicode MS if available
// only works using subsets (otherwise would add very large file)
// doesn't do Indic, arabic, or CJK

//$this->backupSubsFont = 'arialunicodems';

// Optional set a font (defined below in $this->fontdata) to use for CJK characters
// in Plane 2 Unicode (> U+20000) when using useSubstitutionsMB. 
// Use a font like hannomb or sunextb if available
// only works using subsets (otherwise would add very large file)

//$this->backupSIPFont = 'hannomb';


/*
This array defines translations from font-family in CSS or HTML
to the internal font-family name used in mPDF. 
Can include as many as want, regardless of which fonts are installed.
By default mPDF will take a CSS/HTML font-family and remove spaces
and change to lowercase e.g. "Arial Unicode MS" will be recognised as
"arialunicodems"
You only need to define additional translations.
You can also use it to define specific substitutions e.g.
'frutiger55roman' => 'arial'
Generic substitutions (i.e. to a sans-serif or serif font) are set 
by including the font-family in $this->sans_fonts below

To aid backwards compatability some are included:
*/
$this->fonttrans = array(
	'helvetica' => 'arial',

	'times' => 'timesnewroman',
	'courier' => 'couriernew',
	'trebuchet' => 'trebuchetms',
	'comic' => 'comicsansms',
	'franklin' => 'franklingothicbook',
	'albertus' => 'albertusmedium',

	'arialuni' => 'arialunicodems',
	'zn_hannom_a' => 'hannoma',
	'ocr-b' => 'ocrb',

);

/*
This array lists the file names of the TrueType .ttf font files
for each variant of the (internal mPDF) font-family name.
['R'] = Regular (Normal), others are Bold, Italic, and Bold-Italic
Each entry must contain an ['R'] entry, but others are optional.
Only the font (files) entered here will be available to use in mPDF.
Put preferred default first in order
This will be used if a named font cannot be found in any of 
$this->sans_fonts, $this->serif_fonts or $this->mono_fonts
['cjk'] = true; for those fonts which are primarily CJK characters (not Pan-Unicode fonts)
['indic'] = true; for special fonts containing Indic characters
['sip'] = true; for fonts using Unicode Supplemental Ideographic Plane (2)
	e.g. Chinese characters in the HKCS extension
['sip-ext'] = 'hannomb'; name a related font file containing SIP characters

If a .ttc TrueType collection file is referenced, the number of the font
within the collection is required. Fonts in the collection are numbered 
starting at 1, as they appear in the .ttc file e.g.
	"cambria" => array(
		'R' => "cambria.ttc",
		'B' => "cambriab.ttf",
		'I' => "cambriai.ttf",
		'BI' => "cambriaz.ttf",
		'TTCfontID' => array(
			'R' => 1,	
			),
		),
	"cambriamath" => array(
		'R' => "cambria.ttc",
		'TTCfontID' => array(
			'R' => 2,	
			),
		),
*/

$this->fontdata = array(
	"dejavusanscondensed" => array(
		'R' => "DejaVuSansCondensed.ttf",
		'B' => "DejaVuSansCondensed-Bold.ttf",
		'I' => "DejaVuSansCondensed-Oblique.ttf",
		'BI' => "DejaVuSansCondensed-BoldOblique.ttf",
		),
	"dejavusans" => array(
		'R' => "DejaVuSans.ttf",
		'B' => "DejaVuSans-Bold.ttf",
		'I' => "DejaVuSans-Oblique.ttf",
		'BI' => "DejaVuSans-BoldOblique.ttf",
		),
	"dejavuserif" => array(
		'R' => "DejaVuSerif.ttf",
		'B' => "DejaVuSerif-Bold.ttf",
		'I' => "DejaVuSerif-Italic.ttf",
		'BI' => "DejaVuSerif-BoldItalic.ttf",
		),
	"dejavuserifcondensed" => array(
		'R' => "DejaVuSerifCondensed.ttf",
		'B' => "DejaVuSerifCondensed-Bold.ttf",
		'I' => "DejaVuSerifCondensed-Italic.ttf",
		'BI' => "DejaVuSerifCondensed-BoldItalic.ttf",
		),
	"dejavusansmono" => array(
		'R' => "DejaVuSansMono.ttf",
		'B' => "DejaVuSansMono-Bold.ttf",
		'I' => "DejaVuSansMono-Oblique.ttf",
		'BI' => "DejaVuSansMono-BoldOblique.ttf",
		),

/* OCR-B font for Barcodes */
	"ocrb" => array(
		'R' => "ocrb10.ttf",
		),

/* XW Zar Arabic fonts */
	"xbriyaz" => array(
		'R' => "XB Riyaz.ttf",
		'B' => "XB RiyazBd.ttf",
		'I' => "XB RiyazIt.ttf",
		'BI' => "XB RiyazBdIt.ttf",
		),
	"xbzar" => array(
		'R' => "XB Zar.ttf",
		'B' => "XB Zar Bd.ttf",
		'I' => "XB Zar It.ttf",
		'BI' => "XB Zar BdIt.ttf",
		),


/* Thai fonts */
	"garuda" => array(
		'R' => "Garuda.ttf",
		'B' => "Garuda-Bold.ttf",
		'I' => "Garuda-Oblique.ttf",
		'BI' => "Garuda-BoldOblique.ttf",
		),
	"norasi" => array(
		'R' => "Norasi.ttf",
		'B' => "Norasi-Bold.ttf",
		'I' => "Norasi-Oblique.ttf",
		'BI' => "Norasi-BoldOblique.ttf",
		),


/* Indic fonts */
	"ind_bn_1_001" => array(
		'R' => "ind_bn_1_001.ttf",
		'indic' => true,
		),
	"ind_hi_1_001" => array(
		'R' => "ind_hi_1_001.ttf",
		'indic' => true,
		),
	"ind_ml_1_001" => array(
		'R' => "ind_ml_1_001.ttf",
		'indic' => true,
		),
	"ind_kn_1_001" => array(
		'R' => "ind_kn_1_001.ttf",
		'indic' => true,
		),
	"ind_gu_1_001" => array(
		'R' => "ind_gu_1_001.ttf",
		'indic' => true,
		),
	"ind_or_1_001" => array(
		'R' => "ind_or_1_001.ttf",
		'indic' => true,
		),
	"ind_ta_1_001" => array(
		'R' => "ind_ta_1_001.ttf",
		'indic' => true,
		),
	"ind_te_1_001" => array(
		'R' => "ind_te_1_001.ttf",
		'indic' => true,
		),
	"ind_pa_1_001" => array(
		'R' => "ind_pa_1_001.ttf",
		'indic' => true,
		),

/* Pan-Unicode Windows Font */
/*
	"arialunicodems" => array(
		'R' => "ARIALUNI.TTF",
		),
*/

/* Pan-Unicode font(s) */
/*
	"cyberbit" => array(
		'R' => "Cyberbit.ttf",
		),
*/


/* Additional Free Font collections */
/*
	"freesans" => array(
		'R' => "FreeSans.ttf",
		'B' => "FreeSansBold.ttf",
		'I' => "FreeSansOblique.ttf",
		'BI' => "FreeSansBoldOblique.ttf",
		),
	"freeserif" => array(
		'R' => "FreeSerif.ttf",
		'B' => "FreeSerifBold.ttf",
		'I' => "FreeSerifItalic.ttf",
		'BI' => "FreeSerifBoldItalic.ttf",
		),
	"freemono" => array(
		'R' => "FreeMono.ttf",
		'B' => "FreeMonoBold.ttf",
		'I' => "FreeMonoOblique.ttf",
		'BI' => "FreeMonoBoldOblique.ttf",
		),
	"liberationsans" => array(
		'R' => "LiberationSans-Regular.ttf",
		'B' => "LiberationSans-Bold.ttf",
		'I' => "LiberationSans-Italic.ttf",
		'BI' => "LiberationSans-BoldItalic.ttf",
		),
	"liberationserif" => array(
		'R' => "LiberationSerif-Regular.ttf",
		'B' => "LiberationSerif-Bold.ttf",
		'I' => "LiberationSerif-Italic.ttf",
		'BI' => "LiberationSerif-BoldItalic.ttf",
		),
	"liberationmono" => array(
		'R' => "LiberationMono-Regular.ttf",
		'B' => "LiberationMono-Bold.ttf",
		'I' => "LiberationMono-Italic.ttf",
		'BI' => "LiberationMono-BoldItalic.ttf",
		),
*/



/* Some Windows Fonts */
/*
	"arial" => array(
		'R' => "arial.ttf",
		'B' => "arialbd.ttf",
		'I' => "ariali.ttf",
		'BI' => "arialbi.ttf",
		),
	"arialnarrow" => array(
		'R' => "ARIALN.TTF",
		'B' => "ARIALNB.TTF",
		'I' => "ARIALNI.TTF",
		'BI' => "ARIALNBI.TTF",
		),
	"arialblack" => array(
		'R' => "ariblk.ttf",
		),
	"albertusmedium" => array(
		'R' => "albertme.TTF",
		'B' => "alberteb.TTF",
		),
	"calibri" => array(
		'R' => "calibri.ttf",
		'B' => "calibrib.ttf",
		'I' => "calibrii.ttf",
		'BI' => "calibriz.ttf",
		),
	"cambria" => array(
		'R' => "cambria.ttc",
		'B' => "cambriab.ttf",
		'I' => "cambriai.ttf",
		'BI' => "cambriaz.ttf",
		'TTCfontID' => array(
			'R' => 1,
			),
		),
	"cambriamath" => array(
		'R' => "cambria.ttc",
		'TTCfontID' => array(
			'R' => 2,	
			),
		),
	"constantia" => array(
		'R' => "constan.ttf",
		'B' => "constanb.ttf",
		'I' => "constani.ttf",
		'BI' => "constanz.ttf",
		),
	"couriernew" => array(
		'R' => "cour.ttf",
		'B' => "courbd.ttf",
		'I' => "couri.ttf",
		'BI' => "courbi.ttf",
		),
	"franklingothicbook" => array(
		'R' => "FRABK.TTF",
		'B' => "FRABKIT.TTF",
		'I' => "framd.ttf",
		'BI' => "framdit.ttf",
		),
	"georgia" => array(
		'R' => "georgia.ttf",
		'B' => "georgiab.ttf",
		'I' => "georgiai.ttf",
		'BI' => "georgiaz.ttf",
		),
	"gillsansmt" => array(
		'R' => "GIL_____.TTF",
		'B' => "GILB____.TTF",
		'I' => "GILI____.TTF",
		'BI' => "GILBI___.TTF",
		),
	"lucidaconsole" => array(
		'R' => "lucon.ttf",
		),
	"tahoma" => array(
		'R' => "tahoma.ttf",
		),
	"timesnewroman" => array(
		'R' => "times.ttf",
		'B' => "timesbd.ttf",
		'I' => "timesi.ttf",
		'BI' => "timesbi.ttf",
		),
	"trebuchetms" => array(
		'R' => "trebuc.ttf",
		'B' => "trebucbd.ttf",
		'I' => "trebucit.ttf",
		'BI' => "trebucbi.ttf",
		),
	"verdana" => array(
		'R' => "verdana.ttf",
		'B' => "verdanab.ttf",
		'I' => "verdanai.ttf",
		'BI' => "verdanaz.ttf",
		),
*/



/* CJK fonts */
/*
	"unbatang_0613" => array(
		'R' => "UnBatang_0613.ttf",
		'cjk' => true,
		),
	"hannoma" => array(
		'R' => "HAN NOM A.ttf",
		'cjk' => true,
		'sip-ext' => 'hannomb',	
		),
	"hannomb" => array(
		'R' => "HAN NOM B.ttf",
		'cjk' => true,
		'sip' => true,
		),
	"sun-exta" => array(
		'R' => "Sun-ExtA.ttf",
		'cjk' => true,
		'sip-ext' => 'sun-extb',
		),
	"sun-extb" => array(
		'R' => "Sun-ExtB.ttf",
		'cjk' => true,
		'sip' => true,
		),
*/

/* Windows CJK Fonts */
/*
	'mingliu' => array (
		'R' => 'mingliu.ttc',
		'TTCfontID' => array (
			'R' => 1,
		),
		'cjk' => true,
		'sip-ext' => 'mingliu-extb',
	),
	'mingliu-extb' => array (
		'R' => 'mingliub.ttc',
		'TTCfontID' => array (
			'R' => 1,
		),
		'cjk' => true,
		'sip' => true,
	),
	'mingliu_hkscs' => array (
		'R' => 'mingliu.ttc',
		'TTCfontID' => array (
			'R' => 3,
		),
		'cjk' => true,
		'sip-ext' => 'mingliu_hkscs-extb',
	),
	'mingliu_hkscs-extb' => array (
		'R' => 'mingliub.ttc',
		'TTCfontID' => array (
			'R' => 3,
		),
		'cjk' => true,
		'sip' => true,
	),
*/




/* Windows arabic font */
/*
	"arabictypesetting" => array(
		'R' => "arabtype.ttf",
		),
*/

);



// These next 3 arrays do two things:
// 1. If a font referred to in HTML/CSS is not available to mPDF, these arrays will determine whether
//    a serif/sans-serif or monospace font is substituted
// 2. The first font in each array will be the font which is substituted in circumstances as above
//     (Otherwise the order is irrelevant)
// Use the mPDF font-family names i.e. lowercase and no spaces (after any translations in $fonttrans)
// Always include "sans-serif", "serif" and "monospace" etc.
$this->sans_fonts = array('dejavusanscondensed','dejavusans','freesans','liberationsans','sans','sans-serif','cursive','fantasy', 
				'arial','helvetica','verdana','geneva','lucida','arialnarrow','arialblack','arialunicodems',
				'franklin','franklingothicbook','tahoma','garuda','calibri','trebuchet','lucidagrande','microsoftsansserif',
				'trebuchetms','lucidasansunicode','franklingothicmedium','albertusmedium','xbriyaz'

);

$this->serif_fonts = array('dejavuserifcondensed','dejavuserif','freeserif','liberationserif','serif',
				'times','timesnewroman','centuryschoolbookl','palatinolinotype','centurygothic',
				'bookmanoldstyle','bookantiqua','cyberbit','cambria',
				'norasi','charis','palatino','constantia','georgia','albertus','xbzar'
);

$this->mono_fonts = array('dejavusansmono','freemono','liberationmono','courier', 'mono','monospace','ocrb','ocr-b','lucidaconsole',
				'couriernew','monotypecorsiva'
);

?>
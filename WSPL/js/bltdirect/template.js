var paneLeft1 = new bltdirect.ui.LeftPane('LeftPane1');
paneLeft1.setMinimumItems(10);
paneLeft1.setMinimiseArea('LeftNavArea');

var paneRight1 = new bltdirect.ui.RightPane('RightPane1');
paneRight1.setMinimiseArea('RightNavArea');

var optionGroup1 = new bltdirect.ui.OptionGroup();
var optionGroup2 = new bltdirect.ui.OptionGroup();

var menu1 = new bltdirect.ui.Menu('menu1');
menu1.addClass('topMenu', 'MenuContainer', 'down');
menu1.addClass('subMenu', 'MenuContainer MenuContainerSubMenu', 'left');
menu1.add('navHome', null, 'Home', './index.php', null, 'topMenu');
menu1.add('navProducts', null, 'Products', './products.php', null, 'topMenu');
menu1.add('navAccount', null, 'My Account', './accountcenter.php', null, 'topMenu');
menu1.add('navAccount1', 'navAccount', 'Introduce A Friend', './introduce_a_friend.php', null, 'subMenu');	
menu1.add('navAccount2', 'navAccount', 'My Bulbs', './bulbs.php', null, 'subMenu');
menu1.add('navAccount3', 'navAccount', 'My Quotes', './quotes.php', null, 'subMenu');
menu1.add('navAccount4', 'navAccount', 'My Orders', './orders.php', null, 'subMenu');
menu1.add('navAccount5', 'navAccount', 'My Invoices', './invoices.php', null, 'subMenu');
menu1.add('navAccount6', 'navAccount', 'Enquiry Centre', './enquiries.php', null, 'subMenu');
menu1.add('navAccount7', 'navAccount', 'Order Notes', './eNotes.php', null, 'subMenu');
menu1.add('navAccount8', 'navAccount', 'Duplicate A Past Order', './duplicate.php', null, 'subMenu');
menu1.add('navAccount9', 'navAccount', 'Returns', './returnorder.php', null, 'subMenu');
menu1.add('navAccount10', 'navAccount', 'My Profile', './profile.php', null, 'subMenu');
menu1.add('navAccount11', 'navAccount', 'My Business Profile', './businessProfile.php', null, 'subMenu');
menu1.add('navAccount12', 'navAccount', 'Change Password', './changePassword.php', null, 'subMenu');

menu1.add('navInformation', null, 'Information', './information.php', null, 'topMenu');
menu1.add('navInformation1', 'navInformation', 'About BLT Direct', './company.php', null, 'subMenu');
menu1.add('navInformation2', 'navInformation', 'Delivery Rates', './deliveryRates.php', null, 'subMenu');
menu1.add('navInformation3', 'navInformation', 'Lamp Base Examples', './lampBaseExamples.php', null, 'subMenu');
menu1.add('navInformation4', 'navInformation', 'Energy Saving Comparisons', './energy-saving-bulbs.php', null, 'subMenu');
menu1.add('navInformation5', 'navInformation', 'Colour Temperature Examples', './lampColourTemperatures.php', null, 'subMenu');
menu1.add('navInformation6', 'navInformation', 'Security at BLT', './security.php', null, 'subMenu');
menu1.add('navInformation7', 'navInformation', 'Useful Links', './links.php', null, 'subMenu');
menu1.add('navInformation8', 'navInformation', 'Link to Us', './linktous.php', null, 'subMenu');
menu1.add('navInformation9', 'navInformation', 'Press Releases/Articles', './articles.php', null, 'subMenu');
menu1.add('navInformation10', 'navInformation', 'Customer Feedback', './feedback.php', null, 'subMenu');
menu1.add('navInformation11', 'navInformation', 'Affiliates', './affiliates.php', null, 'subMenu');
menu1.add('navInformation12', 'navInformation', 'Become An Affiliate', './becomeAffiliate.php', null, 'subMenu');
menu1.add('navInformation13', 'navInformation', 'Credit Application', './creditAccount.php', null, 'subMenu');
menu1.add('navInformation14', 'navInformation', 'Introduce A Friend', './introduce_a_friend.php', null, 'subMenu');
menu1.add('navInformation15', 'navInformation', 'Videos', './yt_videos.php', null, 'subMenu');
menu1.add('navInformation16', 'navInformation', 'Privacy Policy', './privacy.php', null, 'subMenu');
menu1.add('navInformation17', 'navInformation', 'Terms and Conditions', './terms.php', null, 'subMenu');
menu1.add('navInformation18', 'navInformation', 'Unsubscribe', './unsubscribe.php', null, 'subMenu');
menu1.add('navInformation19', 'navInformation', 'WEEE Directive', './weeedirective.php', null, 'subMenu');
menu1.add('navInformation20', 'navInformation', 'Beam Angles', './beamangles.php', null, 'subMenu');
menu1.add('navInformation21', 'navInformation', 'Online Brochures', './brochures.php', null, 'subMenu');

menu1.add('navDownloads', null, 'Downloads', './downloads.php', null, 'topMenu');
menu1.add('navSupport', null, 'Support', './support.php', null, 'topMenu');
menu1.add('navSupport1', 'navSupport', 'Sales Enquiries', './support.php', null, 'subMenu');
menu1.add('navSupport2', 'navSupport', 'Returns', './returnorder.php', null, 'subMenu');
menu1.add('navSupport3', 'navSupport', 'Frequently Asked Questions', './faqs.php', null, 'subMenu');
menu1.add('navSupport4', 'navSupport', 'Job Opportunities', './jobs.php', null, 'subMenu');
menu1.add('navFavourites', null, 'Add To Favourites', 'javascript:window.external.AddFavorite(\'http://www.bltdirect.com\', \'Light Bulbs from BLT Direct\');', null, 'topMenu');

var itemBlend1 = new bltdirect.ui.SpecialOffers();
itemBlend1.setRequestURL('/ignition/lib/util/getProductOffers.php');
itemBlend1.setItemContainer('LeftOfferItem');
itemBlend1.setImageContainer('LeftOfferImage');
itemBlend1.setNavContainer('LeftOfferNav');
itemBlend1.setNavImageOn('./images/template/bg_navLeft_offersNav_2.gif');
itemBlend1.setNavImageOff('./images/template/bg_navLeft_offersNav_1.gif');

var searchBox = new bltdirect.ui.SearchBox();
searchBox.setRequestURL('/ignition/lib/util/getProductSearchSuggestions.php');
searchBox.setTextField('search');

Interface.addListener(optionGroup1);
Interface.addListener(optionGroup2);
Interface.addListener(menu1);
Interface.addListener(itemBlend1);
Interface.addListener(searchBox);

var uiController = new (function(){
	Interface.addListener(this);
	
	this.load = function(){				
		paneLeft1.setMask('LeftPane1Mask');
		paneLeft1.setContainer('LeftPane1Container');
		paneLeft1.setCap('LeftPane1Cap', './images/template/bg_navLeft_cap_1.jpg', './images/template/bg_navLeft_cap_2.jpg');
		paneLeft1.setList('LeftPane1List');
		
		/*paneRight1.setMask('RightPane1Mask');
		paneRight1.setContainer('RightPane1Container');
		paneRight1.setCap('RightPane1Cap', '/images/template/bg_navRight_cap_1.jpg', '/images/template/bg_navRight_cap_2.jpg');*/
		
		optionGroup1.addOption('LeftOption1');
		optionGroup1.addOption('LeftOption2');
		optionGroup1.addOption('LeftOption3');
		optionGroup1.addOption('LeftOption4');
		optionGroup1.addOption('LeftOption5');
		optionGroup1.addOption('LeftOption6');
		optionGroup1.collapse({animate: false});
		optionGroup1.expand({animate: false, element: $('LeftOption1')});
		
		optionGroup2.addOption('RightOption1');
		optionGroup2.addOption('RightOption2');
		
		optionGroup2.collapse({animate: false});
		optionGroup2.expand({animate: false, element: $('RightOption2')});
	}
});

function searchReplace(value) {
	searchBox.element.value = value;
	searchBox.processSearch();
}
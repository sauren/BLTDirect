<?php
		require_once('lib/common/app_header.php');

	if($action == "add"){
		$session->Secure(3);
		add();
		exit;
	} elseif($action == "update"){
		$session->Secure(3);
		update();
		exit;
	} elseif($action == "remove"){
		$session->Secure(3);
		remove();
		exit;
	} else {
		$session->Secure(2);
		view();
		exit;
	}

	function remove(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ArticleDownload.php');
		$download = new ArticleDownload($_REQUEST['download']);
		$article = $download->Article->ID;
		$download->Delete();
		redirect("Location: articles.php?action=update&article=" . $article);
	}

	function add(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ArticleDownload.php');

		$download = new ArticleDownload;
		$download->Article->Get($_REQUEST['article']);
		$download->Article->Category->Get();


		$form = new Form("article_downloads.php");
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('article', 'Article ID', 'hidden', $download->Article->ID, 'numeric_unsigned', 1, 11);
		$form->AddField('title', 'Title', 'text', '', 'anything', 1, 62);
		$form->AddField('file', 'File', 'file', '', 'file', NULL, NULL);

		// Check if the form has been submitted

		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$download->Name = $form->GetValue('title');
				$download->Article->ID = $form->GetValue('article');

				if($download->Add('file')) {
					redirect(sprintf("Location: articles.php?article=%s&action=update", $download->Article->ID));
				} else {
					for($i = 0; $i < count($download->File->Errors); $i++) {
						$form->AddError($download->File->Errors[$i]);
					}
				}
			}
		}

		$page = new Page(sprintf('<a href="article_categories.php">My Website Article Categories</a> &gt; <a href="articles.php?aci=%s">%s</a> &gt; <a href="articles.php?article=%s&action=update">%s</a> &gt; Add Download', $download->Article->Category->ID, $download->Article->Category->Name, $download->Article->ID, $download->Article->Name),'Please complete the form below.');
		$page->Display('header');

		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}

		$window = new StandardWindow('Add');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('article');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'articles.php?article=%s&action=update\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $download->Article->ID, $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>
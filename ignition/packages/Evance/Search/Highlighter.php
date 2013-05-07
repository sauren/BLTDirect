<?php
	class Evance_Search_Highlighter implements Zend_Search_Lucene_Search_Highlighter_Interface{
		private $_document;
		protected $_currentColorIndex = 0;
		protected $_totalColors = 5;
		
		public function setDocument(Zend_Search_Lucene_Document_Html $document){
			// do something
			$this->_document = $document;
		}
 
		public function getDocument(){
			return $this->_document;
		}
		
		public function customHighlight($stringToHighlight, $index){
			return sprintf('<span class="evSearchHighlight evSearchHighlight_%s">%s</span>', $index, $stringToHighlight);
		}
 
		public function highlight($words){
			$this->_currentColorIndex = ($this->_currentColorIndex + 1) % $this->_totalColors;
			$this->_document->highlightExtended($words, array($this, 'customHighlight'), array($this->_currentColorIndex));
		}
	}

<?php
	
	require_once 'Evance/Search/TokenFilter/EnglishStemmer/PorterStemmer.php';

	class Evance_Search{
		
		private $_analyser				= null;
		private $_columns				= array();
		private $_filters				= array();
		private $_index					= null;
		private $_limit 				= 10;
		private $_offset 				= 0;
		private $_orderBy				= array();
		private $_path					= null;
		private $_precision				= 2;
		private $_primaryColumn			= null;
		private $_query					= null;
		private $_quickFindColumns		= array();
		private $_results				= array();
		private $_searchString			= null;
		private $_sql					= null;
		private $_terms					= null;
		private $_totalRows				= null;
		private $_method				= null;
		private $_hasExecuted			= false;
		private $_isQuickfind			= false;
		private $_highlight				= array();
		private $_allowQuickfind		= true;
		
		const ANALYSER_PRECISE 			= 'Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive'; // no stemming, no stop words
		const ANALYSER_UTF8 			= 'Evance_Search_Analyzer_Standard_Utf8'; // stemming, but without stop words
		const ANALYSER_EN				= 'Evance_Search_Analyzer_Standard_English'; // steming, with stop words
		const PRECISION_MAX				= 5;
		const REGEX_RANGE				= '/((\[\d+ TO \d+\])|(\{\d+ TO \d+\}))/';
		
		public function __construct(){
			$this->analyser(self::ANALYSER_UTF8);
			$this->define();
		}
		
		public function define(){
			// this method should be overwritten by extensions
		}
				
		public function rowSet(){
			$this->execute();
			
			if($this->isIndexed()){
				if(!count($this->_results)) $this->execute();
				$rowset = array();
				$maxLimit = (($this->_offset+$this->_limit) > count($this->_results))?count($this->_results):($this->_offset+$this->_limit);
				for($i=$this->_offset; $i<$maxLimit; $i++){
					$rowset[] = $this->_results[$i]->getDocument();
				}
				return new ArrayIterator($rowset);
			} else {
				if(is_array($this->_results)){
					return $this->_results;
				} else {
					return $this->_results->rowSet();
				}
			}
		}
		
		public function highlightMatches($str){
			if(!is_null($this->_searchString) && !is_null($this->_query)){
				return $this->_query->htmlFragmentHighlightMatches($str , 'UTF-8', new Evance_Search_Highlighter());
			} else {
				return $str;
			}
		}
		
		public function method(){
			return $this->_method;
		}
		
		public function sql($sql=null){
			if(is_null($sql)){
				return $this->_sql;
			} else if(is_string($sql)) {
				$this->_sql = Query($sql);
			} else {
				$this->_sql = $sql;
			}
			return $this;
		}
		
		public function orderBy($field=null, $direction=null){
			if(is_null($field)){
				$this->_orderBy = array();
			} else {
				$direction = (is_null($direction))?'asc':$direction;
				$orderBy = new StdClass();
				$orderBy->field = $field;
				$orderBy->direction = (strtolower($direction) == 'asc')?SORT_ASC:SORT_DESC;
				$this->_orderBy[] = $orderBy;
			}
			return $this;
		}
		
		private function _hasFilter($field, $value=null){
			foreach($this->_filters as $filter){
				if($filter->name == $field && $value === $filter->value){
					return true;
				}
			}
			return false;
		}
		
		public function filter($field, $value=null){
			if(is_null($field)){
				$this->_filters = array();
			} else{
				if(!$this->_hasFilter($field, $value)){
					$filter = new StdClass();
					$filter->name = $field;
					$filter->value = $value;
					$this->_filters[] = $filter;
				}
			}
			return $this;
		}
		
		public function getFilters($field=null){
			if(!is_null($field)){
				$filters = array();
				foreach($this->_filters as $filter){
					if($filter->name == $field && !is_null($filter->value)){
						$filters[] = $filter;
					}
				}
				return $filters;
			} else {
				return $this->_filters;
			}
		}
		
		public function getPrimaryFilters(){
			$filters = array();
			foreach($this->_filters as $filter){
				if(is_null($filter->value)){
					$filters[] = $filter;
				}
			}
			return $filters;
		}
		
		public function totalRows(){
			if(!$this->_hasExecuted){
				$this->execute();
				$this->_totalRows = Evance_Db::fetchOne($this->_countSql($this->_results)->toString(), $this->_results->params());
			}
			return $this->_totalRows;
		}
		
		public function limit($start, $count=null){
			if(is_null($count)){
				$this->offset(0);
				$this->_limit = $start;
			} else {
				$this->offset($start);
				$this->_limit = $count;
			}
			return $this;
		}
		
		public function offset($data=null){
			if(is_null($data)) return $this->_offset;
			$this->_offset = ($data < 0)?0:$data;
			return $this;
		}
		
		public function primaryColumn($name=null){
			if(is_null($name)){
				return $this->_primaryColumn;
			} else {
				$this->_primaryColumn = $name;
			}
		}
		
		public function column($name, $type=null, $sortType=null, $searchable=true){
			if(!is_null($type) && !is_null($sortType)){
				if(!is_array($this->_columns)){
					$this->_columns = array();
				}
				$field = new StdClass();
				$field->name = $name;
				$field->type = $type;
				$field->sortType = $sortType;
				$field->searchable = $searchable;
				$this->_columns[] = $field;
				return $this;
			} else {
				foreach($this->_columns as $column){
					if($name == $column->name) return $column;
				}
				return false;
			}
		}
		
		public function quickFindColumn($columns=null){
			if(is_null($columns)){
				return $this->_quickFindColumns;
			} else if($columns === false){
				$this->_quickFindColumns = array();
			} else {
				if(is_string($columns)) $columns = array($columns);
				foreach($columns as $column){
					$this->_quickFindColumns[] = $column;
				}
			}
			return $this;
		}
	
		public function disableQuickfind(){
			$this->_allowQuickfind = false;
		}
			
		public function execute($countOnly=false){
			// only perform a quickfind if the columns are defined
			if($this->_allowQuickfind && count($this->_quickFindColumns)>0 && !empty($this->_searchString)){
				$regex = '/^((\d+)|([^\s\"\']+))$/i';
				preg_match($regex, $this->_searchString, $matches);
				if(count($matches)>0){
					$results = $this->quickFindSearch();
					if((is_array($results) && count($results)) || (count($results->rowSet()) == 1)){
						$this->_totalRows = 1;
						$this->_hasExecuted = true;
						$this->_isQuickfind = true;
						return $this->_results;
					}
				}
			}
			// perform fullText search if no quickfind found
			$this->_hasExecuted = true;
			$this->_isQuickfind = false;
			$this->fullTextSearch();
			return $this->_results;
		}
		
		public function isQuickfind(){
			return $this->_isQuickfind;
		}
		
		public function find($keyphrase){
			$this->_searchString = trim($keyphrase);
			return $this;
		}
		
/*
	quickFindSearch()
	-----------------
	assumes that the keyphrase supplied is a single word
	or a number and has no method of checking this fact
	this should be done elsewhere
*/
		public function quickFindSearch($keyphrase=null){
			if(!is_null($keyphrase)) $this->_searchString = $keyphrase;
			$parts = array();
			
			Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(Zend_Search_Lucene_Search_QueryParser::B_AND);
			$analyser = self::ANALYSER_PRECISE;
			Zend_Search_Lucene_Analysis_Analyzer::setDefault(new $analyser());
			$this->_query = Zend_Search_Lucene_Search_QueryParser::parse($this->_searchString);
			
			$this->_method = 'quickfind';
			
			if($this->isIndexed()){
				foreach($this->_quickFindColumns as $column){
					$parts[] = sprintf('%s:%s', $column, $this->_searchString);
				}
				$str = join(' OR ', $parts);
				$this->getIndex();
				try {
					$this->_results = $this->_index->find($str);
				} catch (Zend_Search_Lucene_Exception $ex) {
					$this->_results = array();
				}
				return $this->_results;
			} else {
				$query = clone $this->sql();
				$partsSql = array();
				$parts = array();
				foreach($this->_quickFindColumns as $column){
					$partsSql[] = sprintf('%s like ?', $column);
					$parts[] = $this->_searchString;
				}
				$condition = sprintf('(%s)', join(' or ', $partsSql));
				$query->andWhere($condition, $parts);
				$this->_results = $query;
				return $this->_results;
			}
		}
		
/*
	fullTextSearch()
	----------------
	performs a full text search using an index if available
*/
		public function fullTextSearch($keyphrase=null, $countOnly=false){
			if(!is_null($keyphrase)) $this->_searchString = $keyphrase;
			
			$analyser = $this->analyser();
			Zend_Search_Lucene_Analysis_Analyzer::setDefault(new $analyser());
			Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(Zend_Search_Lucene_Search_QueryParser::B_AND);
			$searchString = '';
			
			if(!empty($this->_searchString)) {
				$this->_parseQuery($this->_searchString);
				$searchString = $this->_buildLuceneQuery();
				$this->_query = Zend_Search_Lucene_Search_QueryParser::parse($searchString);
			}
			
			$this->_method = 'fulltext';
			
			if($this->isIndexed()){
				// build filter information
				// this may affect the actual primary search if the filter is intended on the search string
				$primarySearch = array();
				$otherFilters = array();
				foreach($this->_filters as $filter){
					if(is_null($filter->value) && !stristr($searchString, ':')){
						$primarySearch[] = sprintf('%s:%s', $filter->name, $searchString);
					} else if(!is_null($filter->value)) {
						$otherFilters[] = sprintf('%s:(%s)', $filter->name, $filter->value);
					}
				}
				
				// build a new search string
				$primaryString = array();
				if(!empty($searchString)){
					$primaryString[] = $searchString;
				}
				$filtersString = '';
				if(count($primarySearch)>0){
					$primaryString = array(sprintf('(%s)', join(' OR ', $primarySearch)));
				}
				if(count($otherFilters)>0){
					$filtersString = join(' AND ', $otherFilters);
					$primaryString[] = $filtersString;
				}
				$primaryString = join(' AND ', $primaryString);
				
				// build the find arguments to include sorting
				$arguments = array($primaryString);
				foreach($this->_orderBy as $orderBy){
					$arguments[] = $orderBy->field;
					$arguments[] = $this->column($orderBy->field)->sortType;
					$arguments[] = $orderBy->direction;
				}
				
				
				$this->getIndex();
				try {
					$this->_results = call_user_func_array(array($this->_index, 'find'), $arguments);
				}
				catch (Zend_Search_Lucene_Exception $ex) {
					$this->_results = array();
				}
				$this->_totalRows = count($this->_results);
				return $this->_results;
			} else {
				$sql = clone $this->sql();
				$sqlParams = array(); 
				$sqlWhere = $this->_buildSqlQuery($sqlParams);
				if(!is_null($this->_terms)) $sql->andWhere($sqlWhere, $sqlParams);
				
				
				
				// build other filters
				foreach($this->_filters as $filter){
					$filterSql = sprintf('%s=?', $filter->name);
					$sql->andWhere($filterSql, $filter->value);
				}
				
				// set order by
				foreach($this->_orderBy as $orderBy){
					$sql->orderBy($orderBy->field, (($orderBy->direction==SORT_ASC)?'ASC':'DESC'));
				}
				// set limit
				if(!is_null($this->_limit)){
					$sql->limit($this->_offset, $this->_limit);
				}
				
				$this->_results = $sql;

				return $this->_results;
			}
		}
		
		
		
		private function _buildSqlQuery(&$params){
			$str = array();
			$this->_buildSqlQueryItem($this->_terms, $str, $params);
			return sprintf('(%s)', join('', $str));
		}
		
		private function _buildLuceneQuery(){
			$str = array();
			$this->_buildLuceneQueryItem($this->_terms, $str);
			return sprintf('(%s)', join('', $str));
		}
		
		private function _buildSqlQueryItem($queryItem, &$items, &$params){
			// todo: improve
			if(isset($queryItem->type) && $queryItem->type == 'Zend_Search_Lucene_Search_Query_MultiTerm'){
				$queryItem->condition = $queryItem;
			}
			
			if(is_array($queryItem)){
				$i=0;
				foreach($queryItem as $item){
					$sign = '';
					if($i>0) $items[] = ' ';
					if($item->sign === true && $i>0){
						$sign = 'AND '; //+
					} else if($item->sign === false){
						$sign = 'AND NOT ';
					} else if($i>0) {	
						$items[] = 'OR ';
					}
					if(!is_array($item->condition)){
						$items[] = $sign . $this->_buildSqlQueryItem($item, $items, $params);
					} else {
						$items[] = $sign . '(';
						$items[] = $this->_buildSqlQueryItem($item->condition, $items, $params);
						$items[] = ')';
					}
					++$i;
				}
			} else {
				$str = '';
				
				if(!is_null($queryItem)){
				// todo: replace RLIKE with column specific operators
					$str = '(';
					$snippet = '';
					$stemmedWordArray = array();
					if($queryItem->condition->type == 'TERM' && $this->_analyser == self::ANALYSER_EN && !is_numeric($queryItem->condition->term) && strlen($queryItem->condition->term)>=3){
						$stemmedWordArray = $this->_stemTerm($queryItem->condition->term);
					} else {
						$stemmedWordArray[] = $queryItem->condition->term;
					}
					$this->_highlight = array_merge($stemmedWordArray, $this->_highlight);
					$this->_highlight = array_unique($this->_highlight);
					
					$primaryFilters = $this->getPrimaryFilters();
					if(is_null($queryItem->condition->field)){
						$i=0;
						$filterOn = $this->_columns;
						if(count($primaryFilters) > 0){
							$filterOn = $primaryFilters;
						} 
						
						foreach($filterOn as $column){
							$filters = $this->getFilters($column->name);
							$field = $this->column($column->name);
							if(count($filters) == 0 && $field->searchable){
								if($i>0) $str .= ' OR ';
								$stemmedWords = array();
								if(count($stemmedWordArray)>0){
									foreach($stemmedWordArray as $stemmedWord){
										$stemmedWords[] = sprintf('%s RLIKE ?', $column->name);
										$params[] = $stemmedWord;
									}
									$str .= join(' OR ', $stemmedWords);
								} else {
									$str .= sprintf('%s RLIKE ?', $column->name);
									$params[] = $stemmedWordArray[0];
								}
								++$i;
							}
						}
					} else {
						$stemmedWords = array();
						if(count($stemmedWordArray)>0){
							foreach($stemmedWordArray as $stemmedWord){
								$stemmedWords[] = sprintf('%s RLIKE ?', $queryItem->condition->field);
								$params[] = $stemmedWord;
							}
							$str .= join(' OR ', $stemmedWords);
						} else {
							$str .= sprintf('%s RLIKE ?', $queryItem->condition->field);
							$params[] = $stemmedWordArray[0];
						}
					}
					$str .= ')';
				}
				return $str;
			}
		}
		
		private function _buildLuceneQueryItem($queryItem, &$items){
			if(is_array($queryItem)){
				$i=0;
				foreach($queryItem as $item){
					$sign = '';
					if($i>0) $items[] = ' ';
					if($item->sign === true){
						$sign = ''; //+
					} else if($item->sign === false){
						$sign = '-';
					} else if($i>0) {	
						$items[] = 'OR ';
					}
					if(!is_array($item->condition)){
						$items[] = $sign . $this->_buildLuceneQueryItem($item, $items);
					} else {
						$items[] = $sign . '(';
						$items[] = $this->_buildLuceneQueryItem($item->condition, $items);
						$items[] = ')';
					}
					++$i;
				}
			} else if(isset($queryItem->condition)) {
				return $queryItem->condition->lucene;
			} else {
				return $queryItem->lucene;
			}
		}
		
		private function _parseQuery($phrase){
			$phrase = $this->_preProcess($phrase);
			Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(Zend_Search_Lucene_Search_QueryParser::B_AND);
			$query = Zend_Search_Lucene_Search_QueryParser::parse($phrase);
			$this->_terms = $this->_processQueryItem($query);
		}
		
		private function _preProcess($phrase){
			$phrase = preg_replace(self::REGEX_RANGE, '"$1"', $phrase);
			return $phrase;
		}
		
		
		private function _processQueryItem($subQuery){
			// NOTE: This was written to get around a bug
			// but I'm not sure why it happens with Zends Query MultiTerm
			// the first if statement gets around this
			if(get_class($subQuery) == 'Zend_Search_Lucene_Search_Query_MultiTerm'){
				$terms = $subQuery->getTerms();
				$newStr = array();
				foreach($terms as $term){
					$obj = new StdClass();
					$obj->sign = true;
					$obj->condition = new StdClass();
					$obj->condition->lucene = $term->text;
					$obj->condition->sql = null;
					$obj->condition->term = $term->text;
					$obj->condition->type = 'TERM';
					$obj->condition->field = null;
					$obj->condition->boost = null;
					$obj->condition->slop = null;
					$obj->condition->wildcard = null;
					// these should stay in this order please till end
					$this->_parseType($obj->condition);
					$this->_parseFieldName($obj->condition);
					$this->_parseBoost($obj->condition);
					$this->_parseSlop($obj->condition);
					$this->_parseWildcard($obj->condition);
					$this->_parseQuotes($obj->condition);
					// end
					$newStr[] = $obj;
				}
				return $newStr;
			} else if(method_exists($subQuery,'getSubqueries')){
				$subQueries = $subQuery->getSubqueries();
				$signs = $subQuery->getSigns();
				$newStr = array();
				for($i=0; $i<count($subQueries); $i++){
					$item = $subQueries[$i];
					$obj = new StdClass();
					$obj->sign = $signs[$i];
					$obj->condition = $this->_processQueryItem($item);
					$newStr[] = $obj;
				}
				return $newStr;
			} else {
				$obj = new StdClass();
				$obj->lucene = $subQuery->__toString();
				$obj->sql = null;
				$obj->term = $subQuery->__toString();
				$obj->type = get_class($subQuery);
				$obj->field = null;
				$obj->boost = null;
				$obj->slop = null;
				$obj->wildcard = null;
				// these should stay in this order please till end
				$this->_parseType($obj);
				$this->_parseFieldName($obj);
				$this->_parseBoost($obj);
				$this->_parseSlop($obj);
				$this->_parseWildcard($obj);
				$this->_parseQuotes($obj);
				// end
				return $obj;
			}
		}
		
		private function _parseType(&$subject){
			if($subject->type == 'Zend_Search_Lucene_Search_Query_Preprocessing_Phrase'){
				$subject->type = 'PHRASE';
			} else if($subject->type == 'Zend_Search_Lucene_Search_Query_Preprocessing_Term'){
				$subject->type = 'TERM';
			}
			
			if(preg_match(self::REGEX_RANGE, $subject->term)){
				$subject->type = 'RANGE';
				$subject->lucene = str_replace('"', '', $subject->lucene);
				$subject->term = str_replace('"', '', $subject->term);
				// todo: if no field is set we assume numeric range on primaryColumn
			}
		}
		
		private function _parseQuotes(&$subject){
			if($subject->type == 'PHRASE'){
				$subject->term = substr($subject->term, 1, -1);
			}
		}
		
		private function _parseWildcard(&$subject){
			if($subject->type == 'TERM'){
				if(substr($subject->term, -1) == '*'){
					$subject->term = substr($subject->term, 0, -1);
				}
				$regex = '/^([^\*\~\^]+)(\*)?/';
				preg_match($regex, $subject->lucene, $matches);
				if(strlen($subject->term) > 3){
					if(substr($matches[0], -1) != '*'){
						$subject->lucene = str_replace($matches[0], $matches[0].'*', $subject->lucene);
					}
					$subject->wildcard = true;
				} else {
					if(isset($matches[0]) && substr($matches[0], -1) == '*'){
						$subject->lucene = str_replace($matches[0], $matches[1], $subject->lucene);
					}
				}
			}
		}
		
		private function _parseSlop(&$subject){
			$regex = '/\~(\d+)/';
			preg_match($regex, $subject->term, $matches);
			if(count($matches)>0){
				$subject->slop = $matches[1];
				$subject->term = preg_replace($regex, '', $subject->term);
				if($subject->slop > self::PRECISION_MAX){
					$oldSlop = $matches[1];
					$newSlop = '~' . self::PRECISION_MAX;
					$subject->slop = self::PRECISION_MAX;
					$subject->lucene = preg_replace($regex, $newSlop, $subject->lucene);
				}
			} else if($subject->type == 'PHRASE'){
				preg_match('/(?:\"((?:\\.|[^\"])*)\"(\~\d+)?(\^\d+)?)/', $subject->lucene, $matches);
				if(count($matches)>0 && $this->precision()>0){
					$boost = (isset($matches[3]))?$matches[3]:'';
					$field = (is_null($subject->field))?'':sprintf('%s:', $subject->field);
					$subject->lucene = sprintf('%s"%s"~%s%s', $field, $matches[1], $this->precision(), $boost);
					$subject->slop = $this->precision();
				}
			}
		}
		
		private function _parseBoost(&$subject){
			$regex = '/\^(\d+)$/';
			preg_match($regex, $subject->term, $matches);
			if(count($matches)>0){
				$subject->boost = $matches[1];
				$subject->term = preg_replace($regex, '', $subject->term);
			}
		}
		
		private function _parseFieldName(&$subject){
			foreach($this->_columns as $column){
				$regex = sprintf('/^%s\:/', $column->name);
				preg_match($regex, $subject->term, $matches);
				if(count($matches)>0){
					$subject->field = substr($matches[0], 0, -1);
				}
				$subject->term = preg_replace($regex, '', $subject->term);
			}
		}
		
		protected function getDocumentIds($docId){
			if($this->isIndexed()){
				$term = new Zend_Search_Lucene_Index_Term($docId, $this->_primaryColumn);
				$docIds  = $this->_index->termDocs($term);
				return $docIds;
			} else {
				return false;
			}
		}
	
	// todo: enable deletion of multiple ids
	// todo: test
		public function delete($ids){
			if(!is_array($ids)) $ids = array($ids);
			if($this->isIndexed()){
				foreach($ids as $id){
					$docIds = $this->getDocumentIds($id);
					$this->_deleteDocuments($docIds);
				}
				$this->_index->commit(); // saves the new document to the index
				$this->_index->optimize(); // optimises the index for speed
				return $this;
			} else {
				return false;
			}
		}
		
		private function _deleteDocuments($docIds){
			if($this->isIndexed()){
				foreach($docIds as $docId){
					$this->_index->delete($docId);
				}
				return $this;
			} else {
				return false;
			}
		}
		
		public function createIndex(){
			$this->_index = Zend_Search_Lucene::create($this->_path);
			return $this->_index;
		}
		
		public function isIndexed(){
			return false;
			if(is_null($this->_path)) return false;
			return ($this->getIndex() !== false)?true:false;
		}
		
		public function getIndex(){
			if(is_null($this->_index)){
				try{
					$this->_index = Zend_Search_Lucene::open($this->_path);
					return $this->_index;
				} catch (Exception $e) {
					return false;
				}
			} 
			return $this->_index;
		}
		
/*
	index()
	-------
	takes a rowset or an individual record to index
	the indexer will delete any pre-existing documents from the index
	based upon the value of the primaryColumn
*/
		public function index($rowSet){
			if(!is_array_like($rowSet)) $rowSet = array($rowSet);
			// try to open an existing index or create one if it doesn't exist
			if($this->getIndex() === false) $this->createIndex();
			$analyser = $this->analyser();
			Zend_Search_Lucene_Analysis_Analyzer::setDefault(new $analyser());
			$doc = new Zend_Search_Lucene_Document();
			foreach($rowSet as $obj){
				$doc = new Zend_Search_Lucene_Document();
				$primaryColumn = $this->_primaryColumn;
				$docIds = $this->getDocumentIds($obj->$primaryColumn, $this->_index);
				$this->_deleteDocuments($docIds);
				foreach($this->_columns as $column){
					$objProperty = $column->name;
					$objType = $column->type;
					$doc->addField(Zend_Search_Lucene_Field::$objType($objProperty, $obj->$objProperty));
				}
				$this->_index->addDocument($doc);
			}
			$this->_index->commit(); // saves the new document to the index
			$this->_index->optimize(); // optimises the index for speed
		}
		
		protected function path($str=null){
			if(is_null($str)){
				return $this->_path;
			} else {
				$this->_path = BASE_PATH . '/var/search/'. $str;
				return $this;
			}
		}
		
		public function precision($val=null){
			if(is_null($val)){
				return $this->_precision;
			} else {
				if($val<0) $val = 0;
				if($val>self::PRECISION_MAX) $val = self::PRECISION_MAX;
				$this->_precision = $val;
				return $this;
			}
		}
		
		protected function analyser($obj=null){
			if(is_null($obj)){
				return $this->_analyser;
			} else {
				$this->_analyser = $obj;
				return $this;
			}
		}
		
		private function _stemTerm($word){
			return PorterStemmer::StemStages($word);
		}
		
		private function _countSql($sql) {
			$new = clone $sql;
			$new->select('count(*)');
			$new->dumpPart('order');
			$new->dumpPart('limit');
			
			$parts = $new->getParts();
			
			if(!empty($parts['having']) || !empty($parts['group by'])) {
				$new = Query('SELECT COUNT(*) FROM (' . $new->toString() . ') AS c', $new->params());
			}
			
			return $new;
		}
	}

<?php
	require_once($GLOBALS['DIR_WS_ADMIN'] . "lib/classes/DataQuery.php");
	require_once($GLOBALS['DIR_WS_ADMIN'] . "lib/classes/User.php");
	
	class TreeMenu{
		var $User;
		var $DbTable;
		var $DbUserID;
		var $DbNodeID;
		var $DbParentID;
		var $DbCaption;
		
		// set to null to ignore
		var $DbUrl;
		var $DbTarget;
		var $DbClass;
		var $DbIsActive;
        var $DbOrder;
				
		var $TempJS;
		var $Level;
		var $RsTop;
		var $TreeTitle;

		var $restrict;
		var $userPermissions;
		
		function TreeMenu($treeTitle='TREE1_NODES', $restrict=false) {
			$this->User = new User();
			$this->User->GetFromSession(session_id());

			$this->TempJS = sprintf("var %s = [\n", $treeTitle);
			$this->TreeTitle = $treeTitle;
			
			$this->restrict = $restrict;
			$this->userPermissions = array();

			if($restrict) {
				$this->userPermissions = $this->getCurrentUserPermissions();
			}

			$this->DbTable = 'treemenu';
			$this->DbUserID = 'User_ID';
			$this->DbNodeID = 'Node_ID';
			$this->DbParentID = 'Parent_ID';
			$this->DbCaption = 'Caption';
			$this->DbUrl = 'Url';
			$this->DbTarget = 'Target';
			$this->DbClass = 'Class';
			$this->DbIsActive = "and Is_Active='Y'";
			$this->Level = 1;
		}
		
		function getCurrentUserPermissions(){
			$permissions = $this->User->getPermissions();

			/*for($i=0; $i<count($permissions); $i++){
				$permissions[$i] = '/^' . str_replace('/', '\/', $permissions[$i]) . '/';
			}*/

			return $permissions;
		}

		function SetParams($dbTable, $dbNodeID, $dbParentID, $dbCaption, $dbUrl=NULL, $dbTarget=NULL, $dbClass=NULL, $dbIsActive=NULL, $dbOrder=NULL){
			$this->DbTable = $dbTable;
			$this->DbNodeID = $dbNodeID;
			$this->DbParentID = $dbParentID;
			$this->DbCaption = $dbCaption;
			$this->DbUrl = $dbUrl;
			$this->DbTarget = $dbTarget;
			$this->DbClass = $dbClass;
			$this->DbIsActive = ($dbIsActive == NULL)?"":sprintf("and %s='Y'", $dbIsActive);
            $this->DbOrder = $dbOrder;
		}
		
		function GetTitle(){
			return $this->TreeTitle;
		}

		function isPermitted($url, $nodeId){
			if(empty($url)) {
				$count = 0;

				$sql = sprintf("select %s, %s, %s, %s %s %s %s %s from %s where %s=%d %s", 
										mysql_real_escape_string($this->DbUserID),
										mysql_real_escape_string($this->DbNodeID),
										mysql_real_escape_string($this->DbParentID),
                                       mysql_real_escape_string( $this->DbCaption),
                                       mysql_real_escape_string( ($this->DbOrder == NULL? "" : ', ' . $this->DbOrder)),
										mysql_real_escape_string((($this->DbUrl == NULL)?"":", " . $this->DbUrl)),
										mysql_real_escape_string((($this->DbTarget == NULL)?"":", " . $this->DbTarget)),
										mysql_real_escape_string((($this->DbClass == NULL)?"":", " . $this->DbClass)),
										mysql_real_escape_string($this->DbTable),
										mysql_real_escape_string($this->DbParentID),
										mysql_real_escape_string($nodeId),
										$this->DbIsActive);

				$rs = new DataQuery($sql);
				while($rs->Row) {
					$isPermitted = false;

					if(!$this->restrict || ($this->isPermitted($rs->Row[$this->DbUrl], $rs->Row[$this->DbNodeID]))){
						if(($rs->Row[$this->DbUserID] == 0) || ($rs->Row[$this->DbUserID] == $this->User->ID)) {
							$isPermitted = true;
						}
		 			}

					if($isPermitted){
						$count++;
					}

					$rs->Next();
				}
				$rs->Disconnect();

				return ($count > 0);
			}

			if(stristr($url, 'http:')){
				return true;
			}

			foreach($this->userPermissions as $permitted){
				if(stripos($url, $permitted) !== false){
					return true;
				}
			}

			return false;
		}
		
		function DrawNode($arg_1){
			$sql = sprintf("select %s, %s, %s, %s %s %s %s %s from %s where %s=%d %s", 
										mysql_real_escape_string($this->DbUserID),
										mysql_real_escape_string($this->DbNodeID),
										mysql_real_escape_string($this->DbParentID),
                                       mysql_real_escape_string( $this->DbCaption),
                                       mysql_real_escape_string( ($this->DbOrder == NULL? "" : ', ' . $this->DbOrder)),
										mysql_real_escape_string((($this->DbUrl == NULL)?"":", " . $this->DbUrl)),
										mysql_real_escape_string((($this->DbTarget == NULL)?"":", " . $this->DbTarget)),
										mysql_real_escape_string((($this->DbClass == NULL)?"":", " . $this->DbClass)),
										mysql_real_escape_string($this->DbTable),
										mysql_real_escape_string($this->DbNodeID),
										mysql_real_escape_string($arg_1),
										$this->DbIsActive);
			$rs = new DataQuery($sql);
			// define vars	
			$NodeID = 	$rs->Row[$this->DbNodeID];
			$ParentID = $rs->Row[$this->DbParentID];
			$Caption = 	$this->QuoteNCheck($rs->Row[$this->DbCaption]);
			
			$isPermitted = false;

			if(!$this->restrict || ($this->isPermitted($rs->Row[$this->DbUrl], $arg_1))) {
				if(($rs->Row[$this->DbUserID] == 0) || ($rs->Row[$this->DbUserID] == $this->User->ID)) {
					$isPermitted = true;
				}
 			}

			if($isPermitted){
				// these need to be checked for null values
				if($this->DbUrl != NULL){
					$Url =  	$this->QuoteNCheck($rs->Row[$this->DbUrl]);
				} else {
					$Url = sprintf("\"javascript:s_%s.setNode(%d, '%s');\"", $this->TreeTitle, $NodeID, $rs->Row[$this->DbCaption]);
				}
			
				if($this->DbTarget != NULL){
					$Target =  	$this->QuoteNCheck($rs->Row[$this->DbTarget]);
				} else {
					$Target= "null";
				}
			
				if($this->DbClass != NULL){
					$Clss =  	$this->QuoteNCheck($rs->Row[$this->DbClass]);
				} else {
					$Clss = "null";
				}
				$Order = $this->DbOrder != NULL? $rs->Row[$this->DbOrder]: null;
			
				// write class if applicable
				$this->TempJS .= str_repeat(" ", $this->Level * 4);
				if ($Clss != "null"){
					$this->TempJS .= sprintf("[%s, %s, %s, %s", $Caption, $Url, $Target, $Clss);
				} else {
					$this->TempJS .= sprintf("[%s, %s, %s", $Caption, $Url, $Target);
				}
				$rs->Disconnect();
	            if($Order == null) $Order = $this->DbCaption;
				if($arg_1 != ''){
	                $sql = sprintf("select * from %s where %s=%s %s order by %s", 
	                                            mysql_real_escape_string($this->DbTable),
	                                            mysql_real_escape_string($this->DbParentID),
	                                            mysql_real_escape_string($arg_1),
	                                            $this->DbIsActive,
	                                            mysql_real_escape_string($Order));
				} else {
					$sql = sprintf("select * from %s order by %s", 
											mysql_real_escape_string($this->DbTable),
											mysql_real_escape_string($Order));
				}
			
				$rsChildren = new DataQuery($sql);
				if($rsChildren->TotalRows == 0){
					$this->TempJS .= "],\n";
				} else {
					$this->Level += 1;
					$this->TempJS .= ",\n";
					do{
						$this->DrawNode($rsChildren->Row[$this->DbNodeID]);
						$rsChildren->Next();
					} while ($rsChildren->Row);
					
					$this->Level -= 1;
					$this->TempJS .= str_repeat(" ", $this->Level * 4);
					$this->TempJS .= "], \n";
				}
				$rsChildren->Disconnect();
			}

			return $isPermitted;
		}
		
		function GetJS($forNode=NULL){
			if($this->DbUrl == NULL){
				$this->TempJS .= sprintf("[\"_root\", \"javascript:s_%s.setNode(0, '_root');\", null,\n", $this->TreeTitle);
			}
			$sql = sprintf("select %s from %s where (%s=0 or %s is null) %s order by %s, %s, %s",  
												mysql_real_escape_string($this->DbNodeID), 
												mysql_real_escape_string($this->DbTable), 
												mysql_real_escape_string($this->DbParentID), 
												mysql_real_escape_string($this->DbParentID), 
												$this->DbIsActive, 
												mysql_real_escape_string($this->DbCaption),  
												mysql_real_escape_string($this->DbNodeID),
												mysql_real_escape_string($this->DbParentID));
			
			$this->RsTop = new DataQuery($sql); //
			do{
				$this->DrawNode($this->RsTop->Row[$this->DbNodeID]);
				$this->RsTop->Next();
			} while ($this->RsTop->Row);
			
			$this->TempJS .= ($this->DbUrl == NULL)?"],];\n":"];\n";
			$this->RsTop->Disconnect();
			return $this->TempJS;
		}
		
		function QuoteNCheck($arg_1){
			$tempString = sprintf("\"%s\"", $arg_1);
			if($tempString == "\"\"") {
				$tempString = "null";
			}
			return $tempString ;
		}
		
		function Insert($ParentID, $Caption, $Url='', $Target='i_content_display', $Class='', $IsActive='N'){
			// set compulsory
			$fields = sprintf("%s, %s", $this->DbParentID, $this->DbCaption);
			$values = sprintf("%d, '%s'", $ParentID, $Caption);
			
			// set variable
			if($this->DbUrl != NULL){
				$fields = sprintf("%s, %s, %s", $fields, $this->DbUrl, $this->DbTarget);
			}
			if($this->DbClass != NULL){
				$fields = sprintf("%s, %s",  $fields, $this->DbClass);
			}
			
			$insert = new DataQuery(sprintf("insert into %s (%s, Is_Active) values (%s, '%s')",
												mysql_real_escape_string($this->DbTable),
												mysql_real_escape_string($fields),
												mysql_real_escape_string($values),
												mysql_real_escape_string($IsActive)));
			return $insert->InsertId;
		}
		
		function Update(){
			
		}
		
		function Remove(){
		}
	}
<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');

class Application {
	public static $timeStart;
	private static $timings;
	
	public static function start() {
		self::$timeStart = microtime(true);
	}
	
	public static function stop() {
		$GLOBALS['DBCONNECTION']->Close();
		
		self::addTiming('Application', self::$timeStart, 'Application shutdown.');
		self::debug();
	}
	
	public static function addTiming($group, $time, $description) {
		if(!is_array(self::$timings)) {
			self::$timings = array();
		}
		
		if(!isset(self::$timings[$group])) {
			self::$timings[$group] = array();
			
			ksort(self::$timings);
		}
		
		$executeTime = microtime(true) - $time;
		
		if($group == 'Database') {
			if($executeTime > 8) {
				/*$mail = new htmlMimeMail5();
				$mail->setFrom('root@bltdirect.com');
				$mail->setSubject(sprintf("Slow Query [%ss] ", $executeTime));
				$mail->setText($description);
				$mail->send(array('adam@azexis.com'));*/
			}
		}
				
		self::$timings[$group][] = array('Time' => $executeTime, 'Description' => $description);
	}
	
	public static function debug() {
		if(DEVELOPER) {
			?>
			
			<div style="text-align: left; background-color: #999; padding: 10px;">
			
				<?php
				foreach(self::$timings as $group=>$timings) {
					$time = 0;
					$accumulated = 0;
					$slowestId = null;
					$slowestTime = 0;
					
					for($i=0; $i<count($timings); $i++) {
						$time += $timings[$i]['Time'];
						
						if(is_null($slowestId) || ($timings[$i]['Time'] > $slowestTime)) {
							$slowestId = $i;
							$slowestTime = $timings[$i]['Time'];
						}
					}
					?>
					
					<h3><?php echo $group; ?> [<?php echo count($timings); ?>]</h3>
					<br />
					
					<table width="100%" border="0" cellpadding="5">
						<tr style="background-color: #ddd;">
							<th align="right" nowrap="nowrap" width="10%">Time (s)</th>
							<th align="left">Description</th>
							<th align="right" nowrap="nowrap" width="10%">Load (%)</th>
							<th align="right" nowrap="nowrap" width="10%">Accumulated (%)</th>
						</tr>
						
						<?php				
						$light = true;

						for($i=0; $i<count($timings); $i++) {
							$accumulated += ($timings[$i]['Time'] / $time) * 100;
							
							$backgroundColor = ($light) ? 'fff' : 'eee';
							$backgroundColor = ($slowestId == $i) ? 'f99' : $backgroundColor;
							?>
							
							<tr style="background-color: #<?php echo $backgroundColor; ?>;">
								<td align="right"><?php echo number_format($timings[$i]['Time'], 4, '.', ''); ?></td>
								<td align="left"><?php echo htmlentities($timings[$i]['Description']); ?></td>
								<td align="right"><?php echo number_format(($timings[$i]['Time'] / $time) * 100, 4, '.', ''); ?></td>
								<td align="right"><?php echo number_format($accumulated, 4, '.', ''); ?></td>
							</tr>	
							
							<?php
							$light = !$light;
						}
						?>
						
						<tr style="background-color: #ddd;">
							<td align="right"><strong><?php echo number_format($time, 4, '.', ''); ?></strong></td>
							<td align="left"></td>
							<td align="right"></td>
							<td align="right"></td>
						</tr>
					</table>
					<br />
					
					<?php
				}
				?>
				
			</div>
			
			<?php
		}
	}
}
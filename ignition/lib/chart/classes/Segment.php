<?php
class Segment {
	function Segment($after, $title = '') {
		$this->after = $after;
		$this->title = $title;
	}

	function getAfter() {
		return $this->after;
	}

	function getTitle() {
		return $this->title;
	}
}
?>
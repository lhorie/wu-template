<?php
class RawHook {
	static function format($value) {
		return htmlspecialchars_decode($value, ENT_QUOTES | ENT_HTML5);
	}
}
?>
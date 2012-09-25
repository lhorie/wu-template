<?php
class ElseHook {
	static function macro($el) {
		$endif = self::find_endif($el);
		if ($endif) {
			$el->parentNode->removeChild($endif);
			$el->parentNode->insertBefore(new DOMCdataSection('<?php else: ?>'), $el);
			$el->parentNode->insertBefore(new DOMCdataSection('<?php endif; ?>'), $el->nextSibling);
		}
	}
	static private function find_endif($el) {
		while ($el = $el->previousSibling) {
			if ($el->nodeType === 4 && $el->textContent === '<?php endif; ?>') return $el;
		}
	}
}
?>
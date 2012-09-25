<?php
class Template {
	static function render($filename, $data = []) {
		ob_start();
		$compiledfilename = "bin/$filename";
		if (!file_exists($compiledfilename) || filemtime($filename) > filemtime($compiledfilename)) {
			self::save($compiledfilename, self::compile(file_get_contents($filename)));
		}
		include $compiledfilename;
		return ob_get_clean();
	}
	static function compile($source, $scopechainvars = ['$data'], $formatters = []) {
		libxml_use_internal_errors(true); //allow HTML5 tags
		$doc = new DOMDocument();
		$doc->loadHTML('<br />'.$source);
		$temp = $doc->getElementsByTagName('br')->item(0);
		$temp->parentNode->removeChild($temp); //if first DOMNode in file is a DOMText, prevent it from being automagically wrapped in a <p> by DOMDocument::loadHTML
		
		$root = $doc->getElementsByTagName('html')->item(0);
		$html = self::macro($root, $scopechainvars, $formatters)->saveHTML();
		
		//remove explicit <html> and <body> tags if they were automagically added by DOMDocument::loadHTML
		if (strpos($source, '<body') === false) {
			$starttag = '<body>';
			$startpos = strpos($html, $starttag) + strlen($starttag);
			$endpos = strrpos($html, '</body>');
			$html = substr($html, $startpos, $endpos - $startpos);
		}
		return $html;
	}
	static function save($filename, $html) {
		$dirname = dirname($filename);
		if (!is_dir($dirname)) mkdir($dirname, 0777, true);
		file_put_contents($filename, $html);
	}
	static private function macro($el, $scopechainvars, $formatters) {
		$varbindregexp = '#:([\w\-]+):#';
		$renderregexp = '#:([\w\-\./]+\.[\w\-]+):#';
		
		//expand attrs
		$datavar = $scopechainvars[count($scopechainvars) - 1];
		if (isset($el->attributes)) {
			$attrs = [];
			foreach ($el->attributes as $attr) $attrs[] = $attr;
			foreach ($attrs as $attr) {
				if (preg_match($varbindregexp, $attr->name, $matches)) {
					$name = $matches[1];
					$nameword = preg_replace('#[^a-z0-9_]#', '', $name);
					$hook = $nameword . 'hook';
					
					$el->removeAttribute($attr->name);
					if (class_exists($hook)) {
						if (method_exists($hook, 'macro')) $hook::macro($el, $scopechainvars);
						if (method_exists($hook, 'format')) $formatters[] = "$hook::format";
					}
					else {
						$itemvar = self::scopeResolver($scopechainvars, $name);
						$arrayvar = "${datavar}_$nameword";
						$keyvar = "${arrayvar}_key";
						$valuevar = "${arrayvar}_val";
					
						$el->parentNode->insertBefore(new DOMCdataSection("<?php if ($arrayvar = template::makeTraversable($itemvar)): ?>"), $el);
						$el->insertBefore(new DOMCdataSection("<?php foreach ($arrayvar as $keyvar => $valuevar): ?>"), $el->firstChild);
						$el->appendChild(new DOMCdataSection('<?php endforeach; ?>'));
						$el->parentNode->insertBefore(new DOMCdataSection('<?php endif; ?>'), $el->nextSibling);
						
						$scopechainvars[] = $valuevar;
					}
				}
			}
		}
		
		if (isset($el->childNodes)) {
			$nodes = [];
			foreach ($el->childNodes as $node) $nodes[] = $node;
			foreach ($nodes as $node) self::macro($node, $scopechainvars, $formatters);
		}
		
		//expand rest
		$expr = self::scopeResolver($scopechainvars, '$1');
		$expr = "template::format($expr)";
		foreach ($formatters as $formatter) $expr = "$formatter($expr)";
		
		$html = $el->ownerDocument->saveHTML($el);
		$html = preg_replace($renderregexp, "<?php echo template::render('$1', $datavar); ?>", $html);
		$html = preg_replace($varbindregexp, "<?php echo $expr; ?>", $html);
		$el->parentNode->insertBefore(new DOMCdataSection($html), $el);
		$el->parentNode->removeChild($el);
		
		return $el->ownerDocument;
	}
	static function scopeResolver($scopechainvars, $key) {
		$resolver = '';
		foreach (array_reverse($scopechainvars) as $var) $resolver = $resolver . "isset(${var}['$key']) ? ${var}['$key'] : ";
		return $resolver . "':'.'$key'.':'";
	}
	static function makeTraversable($a) {
		if (is_array($a) || $a instanceof Traversable) return $a;
		else if (is_object($a)) return [(array) $a];
	}
	static function format($value) {
		return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);
	}
}
?>
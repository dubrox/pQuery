<?php
// Helpers
define('PQUERY_HELPER_OBJECT', 'pQueryHelperObject');
define('PQUERY_NODE_HELPER_OBJECT', 'pQueryNodeHelperObject');
function pQuery($to_load) {
	global ${PQUERY_HELPER_OBJECT};
	global ${PQUERY_NODE_HELPER_OBJECT};
	${PQUERY_HELPER_OBJECT} = new pQuery($to_load);
	${PQUERY_NODE_HELPER_OBJECT} = ${PQUERY_HELPER_OBJECT}->getNode();
	return ${PQUERY_HELPER_OBJECT};
}
function p($query = false) {
	global ${PQUERY_NODE_HELPER_OBJECT};
	return ${PQUERY_NODE_HELPER_OBJECT}->find($query);
}

/**
 * 
 * @author dubrox
 *
 */
class pQuery {
	private $dom, $xpath;

	function __construct($str = null) {
		if ($str) {
			
			$this->dom = new DOMDocument();
			
			if (preg_match("/^http:\/\//i",$str) || is_file($str))
				@$this->dom->loadHTMLFile($str);
			else
				@$this->dom->loadHTML($str);
				
			$this->xpath = new DOMXpath($this->dom);
			
		} else throw new Exception('Nothing to load!');
	}
	
	function getNode() {
		return new pQueryNode($this->dom, $this->xpath);
	}
}

class pQueryNode {
	private $dom, $xpath, $selected;
	
	function __construct(&$dom, &$xpath, $selected = null) {
		$this->dom = $dom;
		$this->xpath = $xpath;
		if(isset($selected))
			$this->selected = $selected;
		else
			$this->selected = $dom;
	}

	function find($selector = false, $engine = 'css') {
		if($selector) {
			switch($engine) {
				case 'css':		$selector = pQueryNode::css_to_xpath($selector); break;
				case 'xpath':	$selector = $selector; break;
				default: throw new Exception($engine . ' is not a supported selector engine!');
			}
			
			$selected = array();
			foreach($this->xpath->query($selector) as $node)
				$selected[]= $node;
		} else {
			$selected = array();
			foreach($this->xpath->query('/') as $node)
				$selected[]= $node;
		}
		
		return new pQueryNode($this->dom, $this->xpath, $selected);
	}
	
	function html($html = null, $firstOnly = null) {
		if(!isset($firstOnly))
			$fistOnly = !isset($value);
		
		if($firstOnly)
			$nodes = isset($this->selected[0]) ? array($this->selected[0]) : array();
		else
			$nodes = $this->selected;
			
		if(isset($html)) {
			$new_node = $this->dom->createDocumentFragment();
			$new_node->appendXML($html);
			
			foreach($nodes as $node)
				for($i = $node->childNodes->length - 1; $i > -1; $i--)
				    $node->replaceChild($new_node, $node->childNodes->item($i));
					
			return $this;
		} else {
			$html = array();
			foreach($nodes as $node)
				$html[]= $this->getHtml($node, false);
			return implode("\n", $html);
		}
	}
	
	function outerHtml($html = null, $firstOnly = null) {
		if(!isset($firstOnly))
			$fistOnly = !isset($value);
		
		if($firstOnly)
			$nodes = isset($this->selected[0]) ? array($this->selected[0]) : array();
		else
			$nodes = $this->selected;
			
		if(isset($html)) {
			$new_node = $this->dom->createDocumentFragment();
			$new_node->appendXML($html);
			
			foreach($nodes as $node)
				$node->parentNode->replaceChild($new_node, $node);
					
			return $this;
		} else {
			$html = array();
			foreach($nodes as $node)
				$html[]= $this->getHtml($node, true);
			return implode("\n", $html);
		}
	}
	
	function attr($name, $value = null, $firstOnly = null) {
		if(!isset($firstOnly))
			$fistOnly = !isset($value);
		
		if($firstOnly)
			$nodes = isset($this->selected[0]) ? array($this->selected[0]) : array();
		else
			$nodes = $this->selected;
			
		if(isset($value)) {
			foreach($nodes as $node)
				$node->setAttribute($name, $value);
			return $this;
		} else {
			$value = array();
			foreach($nodes as $node)
				$value[]= $node->getAttribute($name);
			return implode("\n", $value);
		}
	}
	
	function clon() {
		return clone $this;
	}
	
	function appendTo($selector) {
		$toAppend = $this->selected;
		$appendTo = $this->find($selector);
		foreach($appendTo->getSelected() as $parentNode)
			foreach($toAppend as $node)
				$parentNode->appendChild($node);
		return $this;
	}
	
	function prependTo($selector) {
		$toPrepend = $this->selected;
		$prependTo = $this->find($selector);
		foreach($prependTo->getSelected() as $parentNode)
			foreach($toPrepend as $node)
				$parentNode->insertBefore($node, $parentNode->childNodes->item(0));
		return $this;
	}
	
	function __clone() {
		foreach($this->selected as $i => $node)
			$this->selected[$i] = $node->cloneNode(true);
		return $this;
	}
	
	function __set($name, $value) {
		return $this->attr($name, $value);
	}
	
	function __get($name) {
		return $this->attr($name);
	}
	
	function getSelected() {
		return $this->selected;
	}
	
	# INTERNAL FUNCTIONS #
	
	private function set_or_get($set, $get, $name, $value, $firstOnly) {
		if(!isset($firstOnly))
			$fistOnly = !isset($value);
		
		if($firstOnly)
			$nodes = isset($this->selected[0]) ? array($this->selected[0]) : array();
		else
			$nodes = $this->selected;
			
		if(isset($value)) { // set($node, $value)
			foreach($nodes as $node)
				$set($node, $name, $value);
			return $this;
		} else { // get($node)
			$value = array();
			foreach($nodes as $node)
				$value[]= $get($node, $name);
			return implode("\n", $value);
		}
	}
	
	private function getHtml($node, $outer = false) {
		if(!isset($node)) return false;
		
		$html = '';
		if($outer)
			$html.= $this->xml2xhtml($this->dom->saveXML($node));
		else
			foreach ($node->childNodes as $el)
				$html.= $this->xml2xhtml($this->dom->saveXML($el));
		
		return trim($html);
	}
	
	private function xml2xhtml($xml) { // tnx to mdmitry at gmail dot com
		return str_replace('&#13;', '',
			preg_replace('/^\<.xml .+\>/', '',
				preg_replace_callback('#<(\w+)([^>]*)\s*/>#s', 
					create_function('$m', '
		        		$xhtml_tags = array("br", "hr", "input", "frame", "img", "area", "link", "col", "base", "basefont", "param");
		        		return in_array($m[1], $xhtml_tags) ? "<$m[1]$m[2] />" : "<$m[1]$m[2]></$m[1]>";
		    		'), 
				$xml)
			)
		);
	}
	
	private static function xcontains($val, $attr) {
		return "contains(concat(' ',normalize-space(@$attr),' '),' $val ')";
	}
	
	private static function css_to_xpath($css_selector) {
		$selector_array = explode(',', $css_selector);
		$elements_pattern = "/(?P<tag>[\w-:\*]+)*(\#(?P<id>[\w-]+))*(?P<classes>\.[\w-.]+)*(?P<attrs>\[.+\])* /is";
		$classes_pattern = "/\.(?<class>[\w-]+)/is";
		$attrs_pattern = "/\[(?<attr>[\w]+)((?P<op>[!*^$]?=)[\"']?(?P<val>[^\]]*?)[\"'])*\]/is";
		
		$selectors = array();
		foreach($selector_array as $selector_string) {
			$elements = array();
			preg_match_all($elements_pattern, trim($selector_string).' ', $elements_matches, PREG_SET_ORDER);
			foreach($elements_matches as $element_match) {
				
				$attrs = array();
				
				if(!empty($element_match['id'])) $attrs[] = '@id="'.$element_match['id'].'"';
				
				if(isset($element_match['classes'])) {
					preg_match_all($classes_pattern, $element_match['classes'], $class_matches, PREG_SET_ORDER);
					foreach($class_matches as $class_match)
						if($class_match['class']) $attrs[] = pQueryNode::xcontains($class_match['class'], 'class');
				}
				
				
				if(isset($element_match['attrs'])) {
					preg_match_all($attrs_pattern, $element_match['attrs'], $attr_matches, PREG_SET_ORDER);
					foreach($attr_matches as $attr_match)
						if(isset($attr_match['attr'])) {
							$attr = '@'.$attr_match['attr'];
							if($attr && !empty($attr_match['op']) && !empty($attr_match['val']))
								 $attr.= $attr_match['op'].'"'.$attr_match['val'].'"';
							$attrs[] = $attr;
						}
				}
				
				$attrs = ($attrs)
					? '['.implode(' and ', $attrs).']'
					: '';
				
				$tag = isset($element_match['tag']) ? $element_match['tag'] : '';
				
				$element = '';
				$element.= $tag ? $tag : '*';
				$element.= $attrs ? $attrs : '';
				
				if($element) $elements[] = '//'.$element;
			}
			$selectors[] = implode('', $elements);
		}
		
		return implode(' | ', $selectors);
	}
}
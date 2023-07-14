<?php

/**
 * @author		Edgars Vitolins <edgars.vitolins@efs.lv>
 * @copyright	Copyright (c) 2013, Efumo.
 * @link		http://www.efumo.lv
 * @version		1
 * 12.02.2013
 */

	/**
	 * Generates rss xml 
	*/
class rss_lib {

	/**
	 * Creates initial elements 
	*/

	public function create_rss() {
		$this->dom = new DOMDocument("1.0", "UTF-8");
		$urlset = $this->create_root($this->dom, "rss");
		$this->add_attribute($this->dom, $urlset, "version", "2.0");
		$this->add_attribute($this->dom, $urlset, "xmlns:content", "http://purl.org/rss/1.0/modules/content/");
		$this->add_attribute($this->dom, $urlset, "xmlns:wfw", "http://wellformedweb.org/CommentAPI/");
		$this->add_attribute($this->dom, $urlset, "xmlns:dc", "http://purl.org/dc/elements/1.1/");
		$this->add_attribute($this->dom, $urlset, "xmlns:atom", "http://www.w3.org/2005/Atom");
		$this->add_attribute($this->dom, $urlset, "xmlns:sy", "http://purl.org/rss/1.0/modules/syndication/");
		$this->add_attribute($this->dom, $urlset, "xmlns:slash", "http://purl.org/rss/1.0/modules/slash/");
		$this->url = $this->create_element($this->dom, $urlset, "channel");
	}
	
	/**
	 * Generates elements inside channel
	*/
	public function set_rss($items, $structure) {
		foreach ($items as $key => $item) {
			if (is_array($item)){
				$this->set_rss_items($this->dom, $this->url, "item", $item, $structure);
			} else {
				$this->create_child_element($this->dom, $this->url, $key, $item);
			}		
		}
	}
	
	/**
	 * Generates each item 
	*/

	public function set_rss_items($dom, $url, $key, $elem,$structure){
		if (is_array($structure)){
			$url_item = $this->create_element($this->dom, $url, $key);
			foreach ($structure as $e_key => $e_value){
				$this->set_rss_items($this->dom, $url_item ,$e_key, $elem, $structure[$e_key]);
			}
		} else {
			$key = explode('|', $key);
			foreach ($key as $k_key => $k_value){
				$key[$k_key] = explode('?', $k_value);
			}
			$elem[$structure] = preg_replace("/&#?[a-z0-9]+;/i","", htmlspecialchars($elem[$structure]));
			$this->paren = $this->create_child_element($this->dom, $url, $key[0][0], 
				@$elem[$structure] ? $elem[$structure] : "");
			unset($key[0]);
			foreach ($key as $k_key => $k_value) {
				$elem[$k_value[1]] = preg_replace("/&#?[a-z0-9]+;/i", "", htmlspecialchars($elem[$k_value[1]]));
				if ($elem[$k_value[1]]) 
					$this->add_attribute($this->dom, $this->paren, $k_value[0], $elem[$k_value[1]]);
			}
		}
	}
	
	function create_root($dom, $name, $value = NULL)
	{
		$root = $dom->createElement($name);
		$dom->appendChild($root);

		if ($value != NULL) {
			$root->appendChild($dom->createTextNode($value));
		}

		return $root;
	}
		

	function create_element($dom, $parent, $name, $value = NULL)
	{
		$element = $dom->createElement($name);
		$parent->appendChild($element);

		if ($value != NULL) {
			$element->appendChild($dom->createTextNode($value));
		}

		return $element;
	}


	function create_child_element($dom, $parent, $name, $value = NULL)
	{
		$element = $dom->createElement($name);
		$parent->appendChild($element);

		if ($value != NULL) {
			$element->appendChild($dom->createTextNode($value));
		}

		return $element;
	}


	function add_attribute($dom, $parent, $name, $value = NULL)
	{
		$attribute = $dom->createAttribute($name);
		$parent->appendChild($attribute);

		if ($value != NULL) {
			$attribute->appendChild($dom->createTextNode($value));
		}

		return $parent;
	}

}
?>

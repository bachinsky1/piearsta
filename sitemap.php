<?php

require_once("system/config/config.php");
loadFunc("site");

$cfg = &loadLibClass('config');
$cfg->set('defaultLang', getDefaultLang());

function getSitetree($parentId = ''){
	

	$result = '';
	$db = &loadLibClass('db');
	$cfg = &loadLibClass('config');
    
	$dbQuery = "SELECT * FROM `ad_content` WHERE `parent_id` = '" . $parentId . "' AND `country` = '" . getCountry() . "' AND `enable` = '1' AND `active` = '1' AND `sitemap` = '1' ORDER BY `lang`, `sort` ASC";
	$query = new query($db, $dbQuery);
	while($query->getrow()){

		if ($query->field('url')) {
            
            $hostArr = explode('.', $_SERVER['HTTP_HOST']);
            $urlLang = substr($query->field('url'), 0, 2);
            $host = $_SERVER['HTTP_HOST'];
			
            $url = "/" . makeUrlWithLangInTheEnd($query->field('url'));   
            
			$result .= "http://" . $host . $url . "|";
			$result .= getDocuments($query->field('id'), 'http://' . $host . $url);
		}

		$result .= getSitetree($query->field('id'));

	}
	
	return $result;
}

function getDocuments($id, $link) {
	$result = '';
	$db = &loadLibClass('db');

	$dbQuery = "SELECT * FROM `mod_news` WHERE `content_id` = '" . $id . "' AND `enable` = '1' ORDER BY `created` DESC";
	$query = new query($db, $dbQuery);
	if ($query->num_rows() > 0) {
		while ($query->getrow()) {
			$result .= $link . $query->field('page_url') . ".html|";
		}

	}
	
	return $result;
	
}

function getClinics() 
{
	$result = '';
	$db = &loadLibClass('db');
	$cfg = &loadLibClass('config');
	$cfg->getSiteData();
	
	$clinicUrl = getLM($cfg->getData('mirrors_clinics_page'));
	$host = $_SERVER['HTTP_HOST'];
	
	$dbQuery = "SELECT `Clinic`.`id`, `Clinic`.`url`
					FROM `mod_clinics` as `Clinic`
					WHERE 1
				ORDER BY `Clinic`.`id` DESC";
	$query = new query($db, $dbQuery);
	while($query->getrow()){
		if ($query->field('url')) {
	
			
	
			$result .= "http://" . $host . $clinicUrl . $query->field('url') . "/|";
		}
	}
	
	return $result;
}

function getDoctors()
{
	$result = '';
	$db = &loadLibClass('db');
	$cfg = &loadLibClass('config');
	$cfg->getSiteData();

	$doctorsUrl = getLM($cfg->getData('mirrors_doctors_page'));
	$host = $_SERVER['HTTP_HOST'];

	$dbQuery = "SELECT `Doc`.`id`, `Doc`.`url`, `Clinic`.`url` as `clinic_url` 
					FROM `mod_doctors` as `Doc` 
						LEFT JOIN `mod_doctors_to_clinics` as `DocToClinic` ON ( `DocToClinic`.`d_id` = `Doc`.`id` ) 
						LEFT JOIN `mod_clinics` as `Clinic` ON ( `Clinic`.`id` = `DocToClinic`.`c_id` ) 
					WHERE 1 
						AND `Doc`.`deleted` = 0 
					ORDER BY `Doc`.`id` DESC";
	$query = new query($db, $dbQuery);
	while($query->getrow()){
		if ($query->field('url')) {

			$result .= "http://" . $host . $doctorsUrl . $query->field('url') . "/" . $query->field('clinic_url') . "/|";
		}
	}

	return $result;
}

$links = (explode('|', getSitetree() . getClinics() . getDoctors()));

// Create new dom object
$dom = new DOMDocument("1.0","UTF-8");

// Create urlset
$urlset = create_root($dom, "urlset");
add_attribute($dom, $urlset, "xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");

foreach ($links as $key => $page_url) {
    if (!empty($page_url)) {
        $url = create_element($dom, $urlset, "url");
        $data = array(
                        "loc" => $page_url,
                        "lastmod" => date("Y-m-d"),
                        "changefreq" => 'always'
                    );
        foreach ($data as $k => $v) {
            create_child_element($dom, $url, $k, trim($v));
        }
    }
}
// Output XML
$strXML = xml_print($dom);


//------------------------------------------------------------------------------


/**
 * Creates XML root element
 *
 * @author                  Dailis TukÄ�ns <dailis@efumo.lv>
 * @version                 1.0
 * 
 * @param object $dom       dom document object
 * @param string $name      root element name
 * @param string $value     root element value (not obligate)
 * 
 * @return object $root     xml root element
 */
function create_root($dom, $name, $value = NULL)
{
    $root = $dom->createElement($name);
    $dom->appendChild($root);

    if ($value != NULL) {
        $root->appendChild($dom->createTextNode($value));
    }

    return $root;
}
	
/**
 * Creates XML element
 *
 * @author                  Dailis TukÄ�ns <dailis@efumo.lv>
 * @version                 1.0
 * 
 * @param object $dom       dom document object
 * @param object $parent    parent element
 * @param string $name      new element name
 * @param string $value     new element value (not obligate)
 * 
 * @return object $element  xml element
 */
function create_element($dom, $parent, $name, $value = NULL)
{
    $element = $dom->createElement($name);
    $parent->appendChild($element);

    if ($value != NULL) {
        $element->appendChild($dom->createTextNode($value));
    }

    return $element;
}

/**
 * Creates XML child element (automaticly adds to parent element)
 *
 * @author                  Dailis TukÄ�ns <dailis@efumo.lv>
 * @version                 1.0
 * 
 * @param object $dom       dom document object
 * @param object $parent    parent element
 * @param string $name      child element name
 * @param string $value     child element value (not obligate)
 * 
 * @return object $parent   xml parent element with child element
 */
function create_child_element($dom, $parent, $name, $value = NULL)
{
    $element = $dom->createElement($name);
    $parent->appendChild($element);

    if ($value != NULL) {
        $element->appendChild($dom->createTextNode($value));
    }

    return $parent;
}

/**
 * Creates XML attribute with value for element (automaticly adds to element)
 *
 * @author                  Dailis TukÄ�ns <dailis@efumo.lv>
 * @version                 1.0
 * 
 * @param object $dom       dom document object
 * @param object $parent    parent element
 * @param string $name      element attribute name
 * @param string $value     element attribute value
 * 
 * @return object $parent   xml parent element with attribute
 */
function add_attribute($dom, $parent, $name, $value = NULL)
{
    $attribute = $dom->createAttribute($name);
    $parent->appendChild($attribute);

    if ($value != NULL) {
        $attribute->appendChild($dom->createTextNode($value));
    }

    return $parent;
}

/**
 * Outputs XML tree
 *
 * @author                  Dailis TukÄ�ns <dailis@efumo.lv>
 * @version                 1.0
 * 
 * @param object $dom       dom document object
 * @param boalen $return    return variant
 * 
 * @return $xml             XML content print to screen
 */
function xml_print($dom) {
    $dom->formatOutput = TRUE;
    $xml = $dom->saveXML();
    header('content-type: text/xml');
    echo $xml;
}
?>
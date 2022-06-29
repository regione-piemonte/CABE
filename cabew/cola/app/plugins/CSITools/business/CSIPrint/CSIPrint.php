<?php
# SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
# SPDX-License-Identifier: GPL-3.0

require_once(__CA_MODELS_DIR__ . "/ca_bundle_displays.php");
require_once(__CA_MODELS_DIR__ . "/ca_objects.php");
require_once(__CA_LIB_DIR__ . "/Search/ObjectSearch.php");
require_once(__CA_LIB_DIR__ . "/Db.php");


class CSIPrint {
    private $config;
    private $start_id;
    private $phpWord;
    private $request;
    private $stack;
    private $searcher;
    private $o_db;
    private $styles;
    private $stylesMap;

    public function loadTypes(){
        $result = $this->o_db->query("select i.item_id, i.idno from ca_list_items i where i.list_id = (select l.list_id from ca_lists l where l.list_code = 'object_types' and l.deleted = 0 ) and i.parent_id is not null and i.deleted = 0");
        
        $this->stylesMap = array();

        while($result->nextRow()){
            $this->stylesMap[$result->get('item_id')] = $result->get('idno');
        }
    }



    public function __construct($po_request, $config) {
        $this->request = $po_request;
        $this->config = $config;
        $this->stylesMap = array();

        $this->stack = new Stack();
        $this->searcher = new ObjectSearch();

        $this->o_db = new Db("", null, false);

        $this->styles = array(
            'entity_section' => array(
                'colsNum' => 2,
                'colsSpace' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1)
            ),

            'entity_header_font' => array(
                'bold' => true,
                'size' => 21
            ),

            'entity_row_name_font' => array(
                'size' => 9
            ),

            'entity_row_index_font' => array(
                'bold' => true,
                'size' => 9
            ),
        );

        $this->loadTypes();

    }

    public function print($start_id, $metadataView) {

        $this->start_id = $start_id;
        $t_display = new ca_bundle_displays();
        $t_display->load($metadataView);
        $displayName = $t_display->getPreferredLabels(null, false)[$metadataView][1][0]['name'];

        $file = $this->_frontpage($displayName);
        $this->phpWord = \PhpOffice\PhpWord\IOFactory::load($file);
        $this->phpWord->setDefaultFontName($this->config->get('fontFamily'));
        $this->phpWord->setDefaultFontSize($this->config->get('fontSize'));

        // Aggiungo il primo elemento dallo stack;
        $object= new ca_objects($start_id);
        $typeInstance = $object->getTypeInstance();
        if(empty($typeInstance)){
            $typeInfo = array('type' => 'ente_aderente', 'isad' => null);
        }else{
            $typeInfo = $this->_getTypeInfo($typeInstance);
        }
        $this->stack->push($object, 0, $typeInfo);
        $changeParent = null;
        $currentSection = $this->_createSection();
        $currentSection->addPageBreak();

        $insert = array();
	    $map_entities = array();

        $livels = $this->config->get('indent_levels');

	    $index = 1;

        // Incomincio ad analizzare gli elementi
        while (!$this->stack->isEmpty()) {
            $obj = $this->stack->pop();
            $object = $obj['obj'];
            $currentLivel = $obj['liv'];
            $type = $obj['type'];

            $currentObjId = $object->get('object_id');

            if ($object->getTypeID() == $this->stylesMap['fondo'])   {
                $currentSection = $this->_createSection(true);
                $header = $currentSection->addHeader();
                $header->addText($this->_getPreferredLabel($object));
	            $header->addLine(array('weight' => 1, 'width' => 100, 'height' => 0, 'color' => 635552));
	            $footer = $currentSection->addFooter();
	            $footer->addPreserveText('{PAGE}', null, array('alignment' => 'center', 'align' => 'center'));
                $parag = $this->_printTitle($currentSection, $this->_getPreferredLabel($object), 'h1', 0, $index);
            } else {
                if ($type['isad'] == 'ISAD3' || $type['isad'] == 'ISAD4')   {
                    if ($type['isad'] == 'ISAD3')   {
	                    $parag = $this->_printTitle($currentSection, $this->_getPreferredLabel($object), 'h4', $currentLivel, $index, true);
                    } else {
	                    $parag = $this->_printTitle($currentSection, $this->_getPreferredLabel($object), 'h5', $currentLivel, $index, true);
                    }
                } else {
                    $h = 'h' . (filter_var($type['isad'], FILTER_SANITIZE_NUMBER_INT) + $currentLivel);
	                $parag = $this->_printTitle($currentSection, $this->_getPreferredLabel($object), $h, $currentLivel, $index);
                }
            }

            $obj_type = ($type['isad'] == 'ISAD4') ? 'documentarie' : $object->getTypeID();

            if ($metadataView != null)  {
                
	            $this->_printMetadata($t_display, $parag, $currentLivel, $object);

                if ($object->getTypeID() == $this->stylesMap['fondo'])    {
                    $currentSection->addPageBreak();
                }
            }

            $getChildren = $this->o_db->query("SELECT object_id FROM ca_objects WHERE parent_id = " . $currentObjId . " AND deleted = 0 ORDER BY rank DESC, object_id DESC");
            while ($getChildren->nextRow()) {
                $obj = new ca_objects($getChildren->get('object_id'));
                if (!$obj->getTypeInstance()) { continue; }
                $type = $this->_getTypeInfo($obj->getTypeInstance());
                if ($type['isad'] == 'ISAD4')   {
                    $this->stack->push($obj, $currentLivel, $type);
                } else {
                    $this->stack->push($obj, $currentLivel + $livels[array_search($this-$obj->getTypeID(), $this->stylesMap)], $type);
                }

            }
            // Recupero le entità
	        $entities = $object->getRelatedItems(20);

	        foreach ($entities as $entity) {
		        if (!isset($map_entities[$entity['entity_id']])) {
			        $map_entities[ $entity['entity_id'] ]['name'] = array(
			        	'displayname' => $entity['displayname'],
				        'nome' => $entity['forename'],
				        'cognome' => $entity['surname']
			        );
		        }
		        $map_entities[$entity['entity_id']]['index'][] = $index;
	        }
			$index++;
        }
	    $currentSection->addPageBreak();

        // Genera la struttura per le entità

	    $this->_headerPage($this->phpWord->addSection());
	    $entities_section = $this->phpWord->addSection($this->styles['entity_section']);

        usort( $map_entities, function ( $a, $b ) {
            return strcasecmp($a['name']['displayname'], $b['name']['displayname']);
        });

	    foreach ($map_entities as $entity) {
		    $this->_printEntity($entities_section, $entity);
	    }

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save($file);

        return $file;
    }

    private function _frontpage($displayName) {
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor(__CA_APP_DIR__ . '/plugins/CSITools/business/CSIPrint/PrintTemplate.docx');

        $templateProcessor->setValue('displayname', htmlspecialchars($displayName));
        $templateProcessor->setValue('nomesito', htmlspecialchars(__CA_APP_DISPLAY_NAME__));

        $user = $this->request->getUser();
        $templateProcessor->setValue('username', htmlspecialchars($user->getName()));
        $groupList = $user->getUserGroups();
        $userGroups = array_column($groupList, 'name');
        $groupString = implode(', ', $userGroups);

        $templateProcessor->setValue('groupname', htmlspecialchars($groupString));

        $object = new ca_objects($this->start_id);

        $id = $object->get('object_id');
        $preferred_label = $object->getPreferredLabels();
        $preferred_label = reset($preferred_label[$id]);
        $preferred_label = $preferred_label[0]['name'];
        $templateProcessor->setValue('nomefondo', htmlspecialchars($preferred_label));

	    $templateProcessor->setValue('dataodierna', date('d/m/Y'));

        $filepath = __CA_BASE_DIR__ . '/import/Stampa_' . $preferred_label . '.docx';

        $templateProcessor->saveAs($filepath);

        return $filepath;
    }

    private function _printTitle(&$section, $title, $h = 'h3', $livel = 0, $index, $bold = true) {
        $hn = $this->config->get('heading');
        $fontStyle = array('bold' => $bold, 'size' => $hn[$h]);
        $paragraph = array('spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(-10), 'spaceBefore' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(0.4));
        $pa = $this->_newParagraph($section, $livel);
        $pa->addText(self::encodeText(trim($title)), $fontStyle, $paragraph);
	    $pa->addText(self::encodeText("\t ({$index}) "), array('bold' => false, 'size' => 8, 'italic' => true), array('align' => 'end'));
	    $pa->addText("\n");
	    //$pa->addTextBreak();
	    return $pa;
    }

    private function _createSection($brakePage = false) {
        $sectionStyle = array(
            'breakType' => ($brakePage ? 'nextPage' : 'continuous'),
            'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.5),
            'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.5),
	        'pageNumberingStart' => 1,
        );

        return $this->phpWord->addSection($sectionStyle);
    }

    private function _newParagraph(&$section, $indentation = 0, $brakePage = false, $space = 0) {
        $style = array(
            'indent' => $indentation,
            'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip($space),
            'spaceBefore' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip($space),
	        'keepLines' => true,
            'pageBreakBefore' => $brakePage,
	        'lineHeigth' => 1.2
        );
        return $section->addTextRun($style);
    }

    private function _getTypeInfo($list) {
        $idno = $list->get('idno');
        $father = $list;
        while (($parent_id = $list->get('parent_id')) != null)  {
            $father = $list;
            $list = new ca_list_items($parent_id);
        }
        $isad = $father->get('idno');
        $isadLevels = $this->config->get('livelliIsad');
        return array('type' => $idno, 'isad' => $isadLevels[$isad]);
    }

    private function _getPreferredLabel($object) {
        $id = $object->get('object_id');
        $preferred_label = $object->getPreferredLabels();
        $preferred_label = reset($preferred_label[$id]);
        $preferred_label = $preferred_label[0]['name'];
        $numerazione_definitiva = $this->config->get('numero_definitivo');
        $template = <<<TEMPLATE
<ifdef code="{$numerazione_definitiva['num_rom']}"><ifdef code="{$numerazione_definitiva['prefix']}">^{$numerazione_definitiva['prefix']} </ifdef>^{$numerazione_definitiva['num_rom']}<ifdef code="{$numerazione_definitiva['bis']}">^{$numerazione_definitiva['bis']}</ifdef></ifdef><ifnotdef code="{$numerazione_definitiva['num_rom']}"><ifdef code="{$numerazione_definitiva['prefix']}">^{$numerazione_definitiva['prefix']}</ifdef><ifdef code="{$numerazione_definitiva['num_def']}">^{$numerazione_definitiva['num_def']}.</ifdef><ifdef code="{$numerazione_definitiva['bis']}">^{$numerazione_definitiva['bis']}</ifdef></ifnotdef>
TEMPLATE;

        $options = array(
            'convertCodesToDisplayText' => true,
            "template" => $template
        );
        $numerazione_definitiva = $object->get($numerazione_definitiva['container'], $options);

        $data = $this->config->get('data');
        $template = <<<TEMPLATE
<ifdef code="{$data['display']}">^{$data['display']}</ifdef>
TEMPLATE;

        $options = array(
            'convertCodesToDisplayText' => true,
            "template" => $template
        );
        $data = $object->get($data['container'], $options);
        $data = ($data != "") ? ', ' . $data : '';

        return $numerazione_definitiva . " " . $preferred_label; // . $data;
    }

    private function _printMetadataOld($t_display, $currentSection, $currentLivel, $object) {

	   
	    $content = "";
	    $va_placements = $t_display->getPlacements(array('returnAllAvailableIfEmpty' => true, 'table' => 'ca_objects', 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'no_tooltips' => true, 'format' => 'simple', 'settingsOnly' => true));

	    $paragraph = $currentSection;
	    $paragraph->addTextBreak();
	    foreach($va_placements as $id => $placement)   {
		    $value = $t_display->getDisplayValue($object, $id, array('convertCodesToDisplayText' => true, 'forReport' => true));

		    if ($value) {
			    $dom = new domDocument('1.0', 'utf-8');
			    $content = strip_tags(self::br2nl($value), '<b><i>');
			    @$dom->loadHTML($content);

			    $dom->preserveWhiteSpace = false;
			    $boldContain= $dom->getElementsByTagName('b'); 
			    if ($boldContain->length > 0) {
				    foreach ($boldContain as $bld) {
					    $paragraph->addText(self::encodeText(trim(strip_tags($bld->nodeValue))), array("bold" => true));
					    $paragraph->addText(' '.self::encodeText(trim(strip_tags($bld->nextSibling->nodeValue))) . " \n", array("bold" => false));
                        $paragraph->addTextBreak();
				    }
			    } else {
				    // Recupero la label dell'oggetto
				    $paragraph->addText(self::encodeText(trim($placement['display'])).": ", array("bold" => true));
				    $content = self::br2nl($value);
				    $paragraph->addText(self::encodeText(trim(strip_tags($content))) . "\n");
			    }
			    $paragraph->addTextBreak(1);
		    }
	    }
    }

    private function _printMetadata($t_display, $currentSection, $currentLivel, $object) {
        $va_placements = $t_display->getPlacements(array('returnAllAvailableIfEmpty' => true, 'table' => 'ca_objects', 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'no_tooltips' => true, 'format' => 'simple', 'settingsOnly' => true));
        $content = "";
        $paragraph = $currentSection;
        $paragraph->addTextBreak();
        foreach($va_placements as $id => $placement)   {
            $value = $t_display->getDisplayValue($object, $id, array('convertCodesToDisplayText' => true, 'forReport' => true));

            if($value){
                $content = strip_tags($value, '<b><i><br>');
                $dom = new domDocument('1.0', 'utf-8');
                @$dom->loadHTML($content);
  
                $dom->preserveWhiteSpace = false;

                $values = array();

                $brElements = $dom->getElementsByTagName('br');

                if($brElements->length > 0){
                    
                    $values = preg_split('/<br[^>]*>/i', $content);
                }else{
                    array_push($values, $content);
                }

                foreach($values as $key => $value){
                    if(!empty($value)){
                        $this->_printMetadataValue($value, $key, $paragraph, $placement);
                    }
                }
                
            }

            
        }
         
    }

    private function _printMetadataValue($value, $currentKey, $paragraph, $placement){
        $dom = new domDocument('1.0', 'utf-8');
        @$dom->loadHTML($value);

        $boldContain= $dom->getElementsByTagName('b');

        if ($boldContain->length > 0) {
            foreach ($boldContain as $bld) {
                $paragraph->addText(self::encodeText(trim(strip_tags($bld->nodeValue))), array("bold" => true));
                $paragraph->addText(' '.self::encodeText(trim(strip_tags($bld->nextSibling->nodeValue))) . " \n", array("bold" => false));
            }
        } else {
            // Recupero la label dell'oggetto
            if($currentKey == 0){
                $paragraph->addText(self::encodeText(trim($placement['display'])).": ", array("bold" => true));
            }
            $content = self::br2nl($value);
            $paragraph->addText(self::encodeText(trim(strip_tags($content))) . "\n");
        }
        $paragraph->addTextBreak();

    }

    private function _headerPage($section) {
	    $section->addText(self::encodeText("Indice dei nomi"), $this->styles['entity_header_font']);
	    $section->addText(self::encodeText("I numeri in grassetto accanto a ciascun lemma costituiscono il rimando al puntatore associato a ciascuna unità e riportato a fianco di ogni descrizione archivistica"));
	    $section->addTextBreak();
    }

    private function _printEntity($section, $entity) {
		$textRun = $section->createTextRun();
	    $textRun->addText(self::encodeText($entity['name']['displayname']), $this->styles['entity_row_name_font']);
	    foreach ($entity['index'] as $obj) {
		    $textRun->addText(", ".self::encodeText($obj), $this->styles['entity_row_index_font']);
	    }
	    $section->addTextBreak();
    }

    static function encodeText($text) {
        return utf8_decode(htmlspecialchars($text));
    }

    static function br2nl( $input ) {
        return preg_replace('/<br\s?\/?>/ius', "\n", str_replace("\n","",str_replace("\r","", htmlspecialchars_decode($input))));
    }
}

class Stack {
    public $stack = array();

    public function push($elem, $level, $type) {
        array_unshift($this->stack, array("obj" => $elem, "liv" => $level, "type" => $type));
    }

    public function pop()   {
        return array_shift($this->stack);
    }

    public function isEmpty()   {
        return count($this->stack) == 0;
    }
}
?>
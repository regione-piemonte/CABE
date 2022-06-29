<?php
/**
 * CSI Piemonte
 * Date: 26/07/18
 * 
 * Modifiche e Refactoring a partire dal file /promemoriaTreeObject/ajax/ajax.php presente nel repository https://github.com/pro-memoria/providence/tree/master/app/widgets creato da Promemoria srl (Turin - Italy) 
 * www.promemoriagroup.com
 * info@promemoriagroup.com
 */

//Rinomino la variabile $_SERVER['SCRIPT_FILENAME'] perché punti alla corretta cartella per l'esecuzione di setup.php
$base_path = dirname(dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))));
$array                      = explode( "/", $_SERVER['SCRIPT_FILENAME'] );
$_SERVER['SCRIPT_FILENAME'] = implode( "/", array_slice( $array, 0, count( $array ) - 4 ) );

require($base_path.'/setup.php');
define( 'ID_DATA', $_GET["field"] ); //created
define('ADMIN_GROUP_ID', 2);

$o_db          = new Db( NULL, NULL, false );
$opo_config = json_decode(file_get_contents(__CA_APP_DIR__.'/widgets/promemoriaTreeObject/conf/promemoriaTreeObjectAttr.json'));

// ottengo il group_id per l'utente readonly se esiste
$query = 'select * from ca_user_groups where code = "readonly"';
$qr_result = $o_db->query($query);

$readonly_group_id = null;
while ($qr_result->nextRow()) {
    $readonly_group_id = $qr_result->get("group_id");
    break;
}

// il codice di promemoria non tiene conto di alcuna securizzazione
// andranno criptati i parametri.

function encrypt_decrypt($action, $string) {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'csi-cola-af-key';
    $secret_iv = '98C81C326A369B1D3182C0B073384047';
    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}





$operation = $_GET["operation"];
$return    = array();

switch ( $operation ) {
    case "get_children":

        $cd = encrypt_decrypt('decrypt', $_GET['cd']);
        $cd = explode('|', $cd);
        $user_id = $cd[0];
        $u_user_id ="_".$user_id;
        $user_groups = parseGroups($cd[1]);
        
        // CSI AF 13/11/18S
        //$order = empty( $_GET["order"] ) ? "t.ordine, posizione" : $_GET["order"];
        //$verso = empty( $_GET["verso"] ) ? "ASC" : $_GET["verso"];
       	// CSI AF 13/11/18E

       

        //cerco il valore dell'itemid della data inserita nel file di configurazione per l'ordinamento
        $item_id   = "";
        $qr_result = $o_db->query( "select item_id FROM
            ca_list_items WHERE idno = '" . ID_DATA . "'" );
        while ( $qr_result->nextRow() ) {
            $item_id = $qr_result->get( "item_id" );
        }


        if (in_array(ADMIN_GROUP_ID, $user_groups)) {
        	
        	$query = "
                SELECT t.object_id as id, t.parent_id,t.type_id as type, t.rank, l.name as text, d.date as date, (SELECT COUNT(*) FROM ca_objects p WHERE t.object_id = p.parent_id and p.deleted = 0) hasChildren, 2 as access 
                                        FROM
                                        ca_objects t
                                        inner join
                                        ca_object_labels l on (t.object_id=l.object_id)
        								left join
                                        (
                                                SELECT av.value_decimal1 as date, a.row_id
                                                FROM
                                                ca_attributes a
                                                INNER JOIN
                                                ca_attribute_values  av ON av.attribute_id = a.attribute_id
                                                WHERE
                                                a.table_num = 57
                                                and av.element_id = (select m.element_id  from ca_metadata_elements m where m.element_code ='data')) d on (t.object_id = d.row_id)
                    where deleted=0 and l.is_preferred = 1 and ";
        } else {

        	$query = "SELECT t.object_id as id, t.parent_id,t.type_id as type, t.rank, l.name as text, d.date as date, (SELECT COUNT(*) FROM ca_objects p WHERE t.object_id = p.parent_id and p.deleted = 0) hasChildren
        			  , ca_acl.access
        			  FROM
                      ((ca_objects t inner join ca_object_labels l on (t.object_id=l.object_id) INNER JOIN ca_acl ON t.object_id = ca_acl.row_id AND ca_acl.table_num = 57))
                                        left join
                                        (
                                                SELECT av.value_decimal1 as date, a.row_id
                                                FROM
                                                ca_attributes a
                                                INNER JOIN
                                                ca_attribute_values  av ON av.attribute_id = a.attribute_id
                                                WHERE
                                                a.table_num = 57
                                                and av.element_id = (select m.element_id  from ca_metadata_elements m where m.element_code ='data')
                                        ) d on (t.object_id = d.row_id)
                    where deleted=0 and l.is_preferred = 1 and (ca_acl.user_id = {$user_id} ";

            if ($user_groups)   {
                // Se l'utente ha solo il gruppo read only, ha accesso a tutti i nodi
                if($readonly_group_id != null && in_array($readonly_group_id, $user_groups) && count($user_groups) == 1){
                    $query .= " OR 1=1 )";
                }else{
                    // altrimenti vengono visti solo i nodi accessibili  ai gruppi
                    $query .= " OR ca_acl.group_id IN (". implode(', ' , $user_groups) .") )";
                }
            } else {
                $query .= ") ";
            }
            $query .= "AND ca_acl.access IN (1, 2, 3) AND ";
        }
        if ( empty($_GET['id'])) {
            $query .= "t.parent_id is null";
        } else {
            $query .= "t.parent_id = " . $_GET['id'];
        }
        $query .= " GROUP BY id ";
        
        // CSI AF 13/11/18S
        //$query .= " ORDER BY $order $verso";
        $query .= " ORDER BY t.rank ASC";
        // CSI AF 13/1/18E
        
        $qr_result = $o_db->query( $query );
        $i         = 0;
        $o_db->beginTransaction();
        while ( $qr_result->nextRow() ) {
            // CSI AF 13/11/18S
            //if ( $order != "t.ordine, posizione" ) {
                //$o_db->query( "UPDATE ca_objects SET posizione=$i WHERE object_id=" . $qr_result->get( "id" ) );
            //}
            // CSI AF 13/11/18E
            $nodo           = new stdClass();
            $nodo->id = $qr_result->get( "id" );
            $nodo->children = $qr_result->get("hasChildren") > 0;

            $nome     = $qr_result->get( "text" );
            $type     = $qr_result->get( "type" );
            $objectId = $qr_result->get( "id" );
            
            // Se l'utente ha un gruppo solo lettura, ritorna accesso in sola lettura
            $nodo->access = (in_array($readonly_group_id, $user_groups)) ? 1 : $qr_result->get( "access" );

            $idnoResult =  $o_db->query("select i.idno from ca_list_items i where item_id = {$type}");
            $idnoResult->nextRow();
            $idno = $idnoResult->get('idno');

            
            // Scelta dell'icona
            switch($idno) {
                case 'isad_1':
                case 'subfonds':
                case 'series':
                case 'subseries':
                case 'complesso_fondi':
                case 'fondo':
                case 'subfondo':
                case 'super_fondo':
                case 'super_fondo':
                case 'isad_2':
                case 'livello':
                    $icon = 'fa fa-archive icon-color';
                break;
                case 'fototipo_ud':
                    $icon = 'fa fa-camera';
                break;
                case 'stampe_ud':
                    $icon = 'fa fa-file-image-o';
                break;
                case 'disegno_ud':
                    $icon = 'fa fa-pencil-square-o';
                break;
                case 'oggetto_ud':
                    $icon = 'fa fa-cubes';
                break;
                case 'bdi_doc_audio':
                case 'ai':
                    $icon = 'fa fa-music';
                break;
                case 'bdi_doc_video':
                case 'vi':
                case 'video_isad4':
                    $icon = 'fa fa-video-camera';
                break;
                case 'bdi_doc_foto':
                case 'fi':
                    $icon = 'fa fa-picture-o';
                break;
                case 'bibliografico_ud':
                    $icon = 'fa fa-book';
                break;
                case 'unita_doc':
                    $icon = 'fa fa-file-text';
                break;
                case 'fototipo_ua':
                    $icon = 'fa fa-camera icon-color';
                break;
                case 'oggetto_ua':
                    $icon = 'fa fa-cubes icon-color';
                break;
                case 'audiovideo_ua':
                    $icon = 'fa fa-camera-retro';
                break;
                case 'stampe_ua':
                    $icon = 'fa fa-file-image-o icon-color';
                break;
                case 'disegni_ua':
                    $icon = 'fa fa-pencil-square-o icon-color';
                break;
                case 'video_isad3':
                    $icon = 'fa fa-video-camera icon-color';
                break;
                case 'unita_ua':
                    $icon = 'fa fa-folder icon-color';
                break;
                case 'arch_ua':
                    $icon = 'fa fa-building-o';
                break;
                case 'fk_ua':
                    $icon = 'fa fa-tree';
                break;
                case 'ff':
                    $icon = 'fa fa-th';
                break;
                case 'oac_ua':
                    $icon = 'fa fa-paint-brush';
                break;
                case 'pst_ua':
                    $icon = 'fa fa-magnet';
                break;
                case 'mi_ua':
                    $icon = 'fa fa-diamont';
                break;
                case 'beni_arch':
                    $icon = 'fa fa-building-o icon-color';
                break;
                case 'folklore':
                    $icon = 'fa fa-tree icon-color';
                break;
                case 'oac':
                    $icon = 'fa fa-paint-brush icon-color';
                break;
                case 'pst':
                    $icon = 'fa fa-magnet icon-color';
                break;
                case 'disegno':
                    $icon = 'fa fa-eraser icon-color';
                break;
                case 'guizzi':
                    $icon = 'fa fa-microphone icon-color';
                break;
                case 'ente_aderente':
                    $icon = 'fa fa-home icon-color';
                break;
                 case 'cart_contenitore':
                    $icon = 'fa fa-folder icon-color';
                break;
                 case 'locale_sto':
                    $icon = 'fa fa-building icon-color';
                break;
                 case 'al34':
                    $icon = 'fa fa-bed icon-color';
                break;
                 case 'me_mat_edit':
                    $icon = 'fa fa-book icon-color';
                break;
                 case 'nu_aggreg':
                    $icon = 'fa fa-money icon-color';
                break;
                 case 'numism_ag':
                    $icon = 'fa fa-money icon-color';
                break;
                 case 'numism':
                    $icon = 'fa fa-money icon-color';
                break;
            }

            $nodo->icon = $icon;

            //Recupero tutte le informazioni in più che l'utente vuole inserire
            $tipologia = '';
            $text = '';
            $dataIniziale = "";
            $data = "";
            $num_provv = "";
            $num_puntamento = "";
            $num_def = "";
            $text = "";
            $metadataAttr = null;
            if (in_array(ADMIN_GROUP_ID, $user_groups))    {
                $metadataAttr = $opo_config->default;
            } else {

                if (isset($opo_config->users->$u_user_id)) {
                    $metadataAttr = $opo_config->users->$u_user_id;
                } else if (isset($opo_config->groups)) {
                    foreach ($user_groups as $group_id) {
                        if (isset($opo_config->groups->$group_id))
                            $metadataAttr = $opo_config->groups->$group_id;
                        break;
                    }
                }
            }
            $flag = true;
            foreach ((($metadataAttr == null ) ? $opo_config->default : $metadataAttr) as $metadato) {

                switch ($metadato) {
                    case 'genreform':
                        //iquery per recuperare la descrizione del type
                        $type_desc  = "";
                        $qr_result1 = $o_db->query( "SELECT name_singular FROM ca_list_item_labels WHERE item_id = $type" );
                        $qr_result1->nextRow();
                        $type_desc = $qr_result1->get( "name_singular" );
                        $text .= ' ' . $type_desc . ' ';
                break;
                    case 'datazione':
                        $aa = $o_db->query( "SELECT v.value_longtext1 as 'value' FROM ca_attribute_values v INNER JOIN ca_attributes a ON (a.attribute_id = v.attribute_id) WHERE (v.element_id = (select m.element_id from ca_metadata_elements m where m.element_code ='{$metadato}' )) AND a.table_num = 57 AND a.row_id = {$objectId}" );
                        if ( $aa->nextRow() ) {
                            $data = $aa->get( "value" );
                            $dataIniziale = ($data == 'undated') ? '' : $data;
                        }

                        if ($dataIniziale != '')    {
                            $text = $text . "<i>" . $dataIniziale . "</i>" . " ";
                        }
                        break;
                    case 'num_provv':
                        $attr_num_prov = $o_db->query("SELECT v.value_longtext1 as 'value' FROM ca_attribute_values v INNER JOIN ca_attributes a ON (a.attribute_id = v.attribute_id) WHERE v.element_id = (select m.element_id from ca_metadata_elements m where m.element_code ='{$metadato}' ) AND a.table_num = 57 AND a.row_id = {$objectId}");
                        if ($attr_num_prov->nextRow())  {
                            $num_provv = $attr_num_prov->get('value');
                        }
                        if ($num_provv != "")   {
                            $text .= "(" . $num_provv . ") ";
                        }
                        break;
                    case 'num_puntamento':
                        $attr_num_puntamento = $o_db->query("SELECT v.value_longtext1 as 'value' FROM ca_attribute_values v INNER JOIN ca_attributes a ON (a.attribute_id = v.attribute_id) WHERE v.element_id = (select m.element_id from ca_metadata_elements m where m.element_code ='{$metadato}' ) AND a.table_num = 57 AND a.row_id = {$objectId}");
                        if ($attr_num_puntamento->nextRow())  {
                            $num_puntamento = $attr_num_puntamento->get('value');
                        }
                        if ($num_puntamento != "")   {
                            $text .= "[" . $num_puntamento . "] ";
                        }
                        break;
                    case 'num_def_numero':
                        $attr_num_def = $o_db->query("SELECT v.value_longtext1 as 'value' FROM ca_attribute_values v INNER JOIN ca_attributes a ON (a.attribute_id = v.attribute_id) WHERE v.element_id = (select m.element_id from ca_metadata_elements m where m.element_code ='{$metadato}' ) AND a.table_num = 57 AND a.row_id = {$objectId}");
                        if ($attr_num_def && $attr_num_def->nextRow())  {
                            $num_def = $attr_num_def->get('value');
                        }
                        if ($num_def != "")   {
                            $text .= $num_def . " ";
                        }
                        $num_def = '';
                        break;
                    default:
                        $text2 = '';
                        $sep = " | ";

                        
                        if ($metadato != 'preferred_label') {
                            // CSI AF 03/08/18S
                            // GESTISCO LE LABELS DEI METADATI A TENDINA IN MODO CHE NON MOSTRI SOLO L'ID
                            $att_result = $o_db->query( "SELECT ca_attribute_values.* FROM (ca_attribute_values INNER JOIN ca_metadata_elements on ca_metadata_elements.element_id = ca_attribute_values.element_id) INNER JOIN ca_attributes on ca_attribute_values.attribute_id = ca_attributes.attribute_id where element_code = '{$metadato}' and ca_attributes.table_num = 57 and ca_attributes.row_id = {$objectId}" );
                            if ( $att_result->nextRow() ) {
                                $text2 = $att_result->get('value_longtext1');


                                $item_id = $att_result->get('item_id');

                                if(!empty($item_id)){
                                    $att_result = $o_db->query('SELECT * FROM ca_list_item_labels WHERE item_id = ' .$item_id);
                                    if ( $att_result->nextRow() ) {
                                        $text2 = $att_result->get('name_singular');
                                    }
                                }
                            }

                            // CSI AF 03/08/18E

                            if (($metadato == 'txSegnatura' || $metadato == 'txSegnOrig' || $metadato == 'segnature_originali') && $text2 != '') {
                                $text2 = "(" . $text2 . ")";
                            }
                        } else {
                            $flag = false;
                            $text2 = $nome;
                        }
                        if ($text2 == "")   $sep = "";
                        $text = $text . $text2 . $sep;
                        break;
                }

            }

            if (!$flag) {
                $text .= $name;
            }

	    // CSI AF 03(09/18S
	    // modifica per stilizzare le schede contenenti i media

	    $nodo->classes = array();

            if ($tipologia != '') $text = $tipologia . $text;

            if ((is_array($nodo->access) &&  in_array(1, $nodo->access)) || $nodo->access == 1) {
            	$nodo->classes[] = "Summary";
	    }

	    // ottengo se la scheda ha media collegati
	    $qr_res = $o_db->query("
		SELECT count(*) media
		FROM ca_objects_x_object_representations
		WHERE
			object_id = ?
		", $nodo->id);
	    if($qr_res->nextRow()){
		if($qr_res->get('media')){
			$nodo->media = true;
			$nodo->classes[] = 'csi-media';
		}
	    }

	    $nodo->text = '<span class="' . implode(' ', $nodo->classes) . '">' . (rtrim(trim($text), '|')). '</span>';
	    // CSI AF 03/09/18E

            $return[]    = $nodo;
            $i ++;
        }
        $o_db->commitTransaction();
        break;

    case "get_children_contestuale":
        $user_id = empty( $_POST['user_id'] ) ? 1 : $_POST["user_id"];
        $user_groups = parseGroups( $_POST['user_groups'] ) ;
        $current_id = empty( $_POST['current_id']) ? null : $_POST['current_id'];


        if (in_array(ADMIN_GROUP_ID, $user_groups)) {

            //query per recuperare gli oggetti
//LM 03/11/17S MI FACCIO RESTITUIRE ANCHE L'ACCESS PER OGNI RECORD IN MODO DA SAPERE SE E' O MENO IN SOLA LETTURA
        	$acc_str = ", 2 as access ";
        	
        	$query = "
              SELECT t.object_id as id, t.parent_id,t.type_id as type, t.rank, l.name as text, d.date as date, (SELECT COUNT(*) FROM ca_objects p WHERE t.object_id = p.parent_id and p.deleted = 0) hasChildren
        	  " . $acc_str . "
                    FROM
                    ca_objects t
                    inner join
                    ca_object_labels l on (t.object_id=l.object_id)
                    left join
                    (
                            SELECT av.value_decimal1 as date, a.row_id
                            FROM
                            ca_attributes a
                            INNER JOIN
                            ca_attribute_values  av ON av.attribute_id = a.attribute_id
                            WHERE
                            a.table_num = 57
                            and av.element_id = (select m.element_id  from ca_metadata_elements m where m.element_code ='data')
                    ) d on (t.object_id = d.row_id)
                    where deleted=0 and l.is_preferred = 1 and ";
        } else {
//LM 03/11/17S MI FACCIO RESTITUIRE ANCHE L'ACCESS PER OGNI RECORD IN MODO DA SAPERE SE E' O MENO IN SOLA LETTURA
        	$query = "SELECT t.object_id as id, t.parent_id,t.type_id as type, t.rank, l.name as text, d.date as date, (SELECT COUNT(*) FROM ca_objects p WHERE t.object_id = p.parent_id and p.deleted = 0) hasChildren
        			  , ca_acl.access
                      FROM
                      ((ca_objects t inner join ca_object_labels l on (t.object_id=l.object_id) INNER JOIN ca_acl ON t.object_id = ca_acl.row_id AND ca_acl.table_num = 57))
                            left join
                            (
                                    SELECT av.value_decimal1 as date, a.row_id
                                    FROM
                                    ca_attributes a
                                    INNER JOIN
                                    ca_attribute_values  av ON av.attribute_id = a.attribute_id
                                    WHERE
                                    a.table_num = 57
                                    and av.element_id = (select m.element_id  from ca_metadata_elements m where m.element_code ='data')
                            ) d on (t.object_id = d.row_id)
                    where deleted=0 and l.is_preferred = 1 and (ca_acl.user_id = {$user_id} ";

            if ($user_groups)   {
                if(in_array($readonly_group_id, $user_groups) && count($user_groups) == 1){
                    $query .= " or 1 = 1) ";
                }else{
                     $query .= " OR ca_acl.group_id IN (". implode(', ' , $user_groups) .") )";
                }
               
            } else {
                $query .= ") ";
            }
            $query .= "AND ca_acl.access IN (1, 2, 3) AND ";
        }
        if ( empty($_POST['id'])) {
            $query .= "t.parent_id is null";
        } else {
            $query .= "t.parent_id = " . $_POST['id'];
        }
        $query .= " GROUP BY id ";
        // CSI AF 13/11/18S
        //$query .= " ORDER BY t.ordine, posizione ASC";
        $query .= " ORDER BY t.rank ASC";
        // CSI AF 13/11/18E


        $qr_result = $o_db->query( $query );
        $i         = 0;
        $o_db->beginTransaction();

        $parent_path = getParentPath($current_id, array(), $o_db);

        while ( $qr_result->nextRow() ) {
            $nodo           = new stdClass();
            $nodo->id = $qr_result->get( "id" );
            $nodo->children = $qr_result->get("hasChildren") > 0;

            if (in_array($qr_result->get("id"), $parent_path)) {
                $nodo->state = array("opened" => true);
            }

            if ($qr_result->get("id") == $current_id)   {
                $nodo->state = array("selected" => true);
            }

            $nome     = $qr_result->get( "text" );
            $type     = $qr_result->get( "type" );
            $objectId = $qr_result->get( "id" );

            // Se l'utente ha un gruppo solo lettura, ritorna accesso in sola lettura
            $nodo->access = (in_array($readonly_group_id, $user_groups)) ? 1 : $qr_result->get( "access" );

            $idnoResult =  $o_db->query("select i.idno from ca_list_items i where item_id = {$type}");
            $idnoResult->nextRow();
            $idno = $idnoResult->get('idno');
            
            // Scelta dell'icona
            switch($idno) {
                case 'isad_1':
                case 'subfonds':
                case 'series':
                case 'subseries':
                case 'complesso_fondi':
                case 'fondo':
                case 'subfondo':
                case 'super_fondo':
                case 'super_fondo':
                case 'isad_2':
                case 'livello':
                    $icon = 'fa fa-archive icon-color';
                break;
                case 'fototipo_ud':
                    $icon = 'fa fa-camera';
                break;
                case 'stampe_ud':
                    $icon = 'fa fa-file-image-o';
                break;
                case 'disegno_ud':
                    $icon = 'fa fa-pencil-square-o';
                break;
                case 'oggetto_ud':
                    $icon = 'fa fa-cubes';
                break;
                case 'bdi_doc_audio':
                case 'ai':
                    $icon = 'fa fa-music';
                break;
                case 'bdi_doc_video':
                case 'vi':
                case 'video_isad4':
                    $icon = 'fa fa-video-camera';
                break;
                case 'bdi_doc_foto':
                case 'fi':
                    $icon = 'fa fa-picture-o';
                break;
                case 'bibliografico_ud':
                    $icon = 'fa fa-book';
                break;
                case 'unita_doc':
                    $icon = 'fa fa-file-text';
                break;
                case 'fototipo_ua':
                    $icon = 'fa fa-camera icon-color';
                break;
                case 'oggetto_ua':
                    $icon = 'fa fa-cubes icon-color';
                break;
                case 'audiovideo_ua':
                    $icon = 'fa fa-camera-retro';
                break;
                case 'stampe_ua':
                    $icon = 'fa fa-file-image-o icon-color';
                break;
                case 'disegni_ua':
                    $icon = 'fa fa-pencil-square-o icon-color';
                break;
                case 'video_isad3':
                    $icon = 'fa fa-video-camera icon-color';
                break;
                case 'unita_ua':
                    $icon = 'fa fa-folder icon-color';
                break;
                case 'arch_ua':
                    $icon = 'fa fa-building-o';
                break;
                case 'fk_ua':
                    $icon = 'fa fa-tree';
                break;
                case 'ff':
                    $icon = 'fa fa-th';
                break;
                case 'oac_ua':
                    $icon = 'fa fa-paint-brush';
                break;
                case 'pst_ua':
                    $icon = 'fa fa-magnet';
                break;
                case 'mi_ua':
                    $icon = 'fa fa-diamont';
                break;
                case 'beni_arch':
                    $icon = 'fa fa-building-o icon-color';
                break;
                case 'folklore':
                    $icon = 'fa fa-tree icon-color';
                break;
                case 'oac':
                    $icon = 'fa fa-paint-brush icon-color';
                break;
                case 'pst':
                    $icon = 'fa fa-magnet icon-color';
                break;
                case 'disegno':
                    $icon = 'fa fa-eraser icon-color';
                break;
                case 'guizzi':
                    $icon = 'fa fa-microphone icon-color';
                break;
                case 'ente_aderente':
                    $icon = 'fa fa-home icon-color';
                break;
                 case 'cart_contenitore':
                    $icon = 'fa fa-folder icon-color';
                break;
                 case 'locale_sto':
                    $icon = 'fa fa-building icon-color';
                break;
                 case 'al34':
                    $icon = 'fa fa-bed icon-color';
                break;
                 case 'me_mat_edit':
                    $icon = 'fa fa-book icon-color';
                break;
                 case 'nu_aggreg':
                    $icon = 'fa fa-money icon-color';
                break;
                 case 'numism_ag':
                    $icon = 'fa fa-money icon-color';
                break;
                 case 'numism':
                    $icon = 'fa fa-money icon-color';
                break;
            }

            $nodo->icon = $icon;

            //Recupero tutte le informazioni in più che l'utente vuole inserire
            $tipologia = '';
            $numerazione = '';
            $attr_num_def = $o_db->query("SELECT v.value_longtext1 as 'value' FROM ca_attribute_values v INNER JOIN ca_attributes a ON (a.attribute_id = v.attribute_id) WHERE v.element_id = (select m.element_id from ca_metadata_elements m
where m.element_code = 'num_def_numero') AND a.table_num = 57 AND a.row_id = {$objectId}");
            if ($attr_num_def->nextRow())  {
                $numerazione = $attr_num_def->get('value');
            }
            if ($numerazione == "") {
                $attr_num_puntamento = $o_db->query("SELECT v.value_longtext1 as 'value' FROM ca_attribute_values v INNER JOIN ca_attributes a ON (a.attribute_id = v.attribute_id) WHERE (v.element_id = (select m.element_id from ca_metadata_elements m
where m.element_code='nct') OR v.element_id = (select m.element_id from ca_metadata_elements m
where m.element_code='nctn')) AND a.table_num = 57 AND a.row_id = {$objectId}");
                if ($attr_num_puntamento->nextRow())  {
                    $numerazione = $attr_num_puntamento->get('value');
                }
            }
            $text = $tipologia . " " . $numerazione . " " . $nome;
	$nodo->classes = array();
//LM 06/11/17S per far capire il tipo di accesso mi creo una classe all'interno del tag SPAN
//            $nodo->text = "<span>".( trim($text) )."</span>";
			if ((is_array($nodo->access) &&  in_array(1, $nodo->access)) || $nodo->access == 1) {
				$nodo->classes[] = 'Summary';

}
//LM 06/11/17E

// CSI AF 03/09/18s
            // ottengo se la scheda ha Media collegati
               
            $qr_res = $o_db->query("
                SELECT count(*) media 
                FROM ca_objects_x_object_representations
                WHERE
                    object_id = ?
                ", $nodo->id);

            if ($qr_res->nextRow()) {
                if($qr_res->get('media')){
                    $nodo->media = true;
                    $nodo->classes[] = 'csi-media';
                }
            }

            $nodo->text = "<span class=\"" . implode(" ", $nodo->classes) ."\">".( rtrim(trim($text), '|') )."</span>";
            // CSI AF 03/09/18e


            $return[]    = $nodo;
            $i ++;
        }
        $o_db->commitTransaction();
        break;
    case "save_node":

        $return["result"] = "OK";
        break;
    case "autocomplete":
        $query = <<<QUERY
            SELECT `name`, ca_metadata_elements.element_code as 'code', ca_metadata_elements.datatype as 'datatype', ca_metadata_elements.element_id as 'id'
            FROM (`ca_editor_ui_bundle_placements` euibp
              INNER JOIN (`ca_metadata_elements`
                INNER JOIN `ca_metadata_element_labels`
                  ON (`ca_metadata_elements`.`element_id` = `ca_metadata_element_labels`.`element_id`)
                )
                ON (REPLACE(`bundle_name`, 'ca_attribute_', '') = `element_code`))
              LEFT JOIN ca_editor_ui_screen_type_restrictions euistr ON (euibp.screen_id = euistr.screen_id)
            WHERE euibp.screen_id IN
                  (SELECT `screen_id`
                   FROM `ca_editor_ui_screens`
                   WHERE ca_editor_ui_screens.`ui_id` =
                         (SELECT `ui_id`
                          FROM `ca_editor_uis`
                          WHERE `editor_code` = 'editor-schede'))
            AND `bundle_name` LIKE '%ca_attribute%' AND `ca_metadata_elements`.datatype != 22
            GROUP BY name, code
            ORDER BY name
QUERY;

        $result = $o_db->query($query);
        $result2 = $o_db->query("SELECT element_code as 'code' FROM ca_metadata_elements WHERE element_id IN (SELECT ca_metadata_elements.parent_id FROM ca_metadata_elements WHERE ca_metadata_elements.parent_id IS NOT NULL AND ca_metadata_elements.datatype = 22)");
        $elim = $result2->getAllRows();
        while ( $result->nextRow() ) {
            $code =  $result->get( "code" );
            if ($result->get( "datatype" ) == 0)    {
                $result2 = $o_db->query("SELECT element_code as 'code', name FROM ca_metadata_elements INNER JOIN `ca_metadata_element_labels` ON (`ca_metadata_elements`.`element_id` = `ca_metadata_element_labels`.`element_id`) WHERE parent_id = ".$result->get("id"));
                while ($result2->nextRow()) {
                    $return['data'][$result2->get( "code" )] = $result2->get( "name" ) . ' - [' . $result2->get( "code" ) . ']';
                }
            } else {
                $return['data'][$code] = $result->get( "name" ) . ' - [' . $code . ']';
            }
        }
    $return['data']["genreform"] = "Tipologia oggetto";

        $cd = encrypt_decrypt('decrypt', $_GET['cd']);
        $cd = explode('|', $cd);
        $user_id = $cd[0];
        $u_user_id ="_".$user_id;
        $user_groups = parseGroups($cd[1]);

        $select = array();
        if ($user_id != 1)  {
            if (isset($opo_config->users->$u_user_id))  {
                $select = $opo_config->users->$u_user_id;
            } else if (count($opo_config->groups) > 0) {
                foreach ($user_groups as $group_id) {
                    if (isset($opo_config->groups->$group_id)) {
                        $select = $opo_config->groups->$group_id;
                        break;
                    }
                }
            }

            if (empty($select))
                $select = $opo_config->default;
        } else {
            $select = $opo_config->default;
        }
        foreach ($select as $k => $met)   {
            if ($met == 'preferred_label')  $return['select'][] = 'preferred_label';
            else    $return['select'][] = $return['data'][$met];
        }
        break;
    case 'salvaOpzioni':
        $metadata = $_GET['metadata'];

        $cd = encrypt_decrypt('decrypt', $_GET['cd']);
        $cd = explode('|', $cd);
        $user_id = $cd[0];
        $user_groups = parseGroups($cd[1]);
       
        foreach ($metadata as $data) {
            $metadataAtt[] = $data;
        }

        

        switch ($user_id)   {
            case 1:
                $opo_config->default = $metadataAtt;
                break;
            case -1:
                $groups = (array)$opo_config->groups;
                foreach (explode(",", $group_ids) as $group_id)
                    $groups[$group_id] = $metadataAtt;
                $opo_config->groups = (object)$groups;
                break;
            default:
                $users = (array)$opo_config->users;
                $users["_" . $user_id] = $metadataAtt;
                $opo_config->users = (object) $users;
        }

        //Modifico il file di configurazione in modo che al caricamento ricarichi le informazioni
        file_put_contents( __CA_APP_DIR__.'/widgets/promemoriaTreeObject/conf/promemoriaTreeObjectAttr.json', json_encode($opo_config));
        break;
    default:
        break;
}


// funzioni di Utility

function parseGroups($groups){
    return (empty($groups)) ? null : explode(',', $groups);
}

function saveChildren( $children, $parent_id = "null" ) {
    global $o_db;
    /*foreach ( $children as $posizione => $nodo ) {
        $id = $nodo->id;
        ( $parent_id == 'null' ) ? $hier_object_id = $id : $hier_object_id = $parent_id;
        $o_db->query( "UPDATE ca_objects SET parent_id = $parent_id, hier_object_id = $hier_object_id, posizione = $posizione, ordine = ($posizione + 1)  WHERE object_id = $id" );
        if ( isset( $nodo->children ) ) {
            saveChildren( $nodo->children, $id );
        }
    }*/
}

function getParentPath($id, $parent = array(), $o_db) {
	if(!empty($id)){
	    $query = "SELECT parent_id FROM ca_objects WHERE deleted=0 AND object_id = {$id}";
	    $qr_result = $o_db->query($query);
	    $qr_result->nextRow();
	    $parent_id = $qr_result->get('parent_id');
	    if ($parent_id) {
	        $parent[] = $parent_id;
	        $parent = getParentPath($parent_id, $parent, $o_db);
	    }

	    return $parent;
	}
}

// Restituisci JSON output
echo json_encode( $return );


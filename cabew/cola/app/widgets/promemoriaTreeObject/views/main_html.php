<?php
/* ----------------------------------------------------------------------
 * app/widgets/promemoriaTreeObject/views/main_html.php :
 * created by Promemoria srl (Turin - Italy) www.promemoriagroup.com
 * version 2.0 - 16/02/2015
 * info@promemoriagroup.com
 *This widget allow to view objects in a hierarchical structure
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */

$po_request    = $this->getVar( 'request' );
$vs_widgets_id = $this->getVar( 'widgets_id' );
$field         = $this->getVar( 'field' );
$user          = $this->getVar( 'user' );
$user_id = $user->getUserID();
$user_groups_id = array_keys($user->getUserGroups());
$administrator = $po_request->user->canDoAction( "is_administrator" );


// CSI AF 02/08/18S
// Encription dei parametri sensibili
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

$cd = $user_id . '|' . implode(',', $user_groups_id);
$cd = encrypt_decrypt('encrypt', $cd);



// CSI AF 02/08/18E

// CSI AF 30/07/18S
$is_readonly = $this->getVar( 'is_readonly');
// disabilita il drag per tutti
$is_readonly = true;
// CSI AF 30/07/18E
?>
<link type = "text/css" rel = "stylesheet"
      href = "<?php print __CA_URL_ROOT__; ?>/app/widgets/promemoriaTreeObject/resources/jquery.contextMenu.css" >
<link rel="stylesheet" href="<?php print __CA_URL_ROOT__; ?>/app/widgets/promemoriaTreeObject/resources/themes/proton/style.min.css" />

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
<style type = "text/css" >
    .jstree-node li a {white-space: normal; height: auto; vertical-align: middle; width: 95%;}
    .option {float: right;color: rgba(130, 130, 130, 1);cursor: pointer;}
    .option span    {margin: -10px;margin-right: 5px;}
    .remove {color: #ff0000 ;cursor: pointer;margin-right: 5%;}
    .dialog {font-size: 14px;text-align: center;}
    .list   {list-style: none;text-align: start;}
    .list li {padding: 2%;border-bottom: 1px grey solid;display: flex;}
    #ObjectWidget   {min-height: 740px;}
    .search {text-align: center;margin: 2%;font-size: smaller;}
    .jstree-node .jstree-anchor {line-height: 24px!important;white-space: normal!important;height: auto!important;display: initial;}
    .jstree-anchor span {
        display: inline-block;
        width: 80%;
    }
    .jstree-proton .jstree-search {
        font-style: initial;
        background-color: rgb(255, 255, 119);
        font-weight: bold;
        color: darkblue;
        display: inline-block;
        text-transform: uppercase;
    }
    .showsummary 	{margin-left: 20px;}
    .ui-dialog{z-index: 101;}
    .icon-color {color: #265797!important;}
    .jstree-icon.fa {color: #265797;}
    .result h4  {
        text-align: center;
    }

    .jstree-proton .jstree-hovered {
        background: #E8E8E8;
        color: #000;
        border-radius: 3px;
        box-shadow: inset 0 0 1px rgba(108, 222, 152, 0.4);
        padding: 0.7%;
    }

    .jstree-proton .jstree-clicked {
        background: #8BD6D8;
        color: #000;
        border-radius: 3px;
        box-shadow: inset 0 0 1px rgba(108, 222, 152, 0.8);
        padding: 0.8%;
    }

    .result table {
        margin: 5% auto;
        font-size: 1.4em;
    }

    .result table td {
        background-color: rgba(238, 238, 238, 0.6);
        padding-right: 0px!important;
    }

    .unit {
        text-align: start;
        padding: 10px;
        border-bottom: 1px solid rgba(204, 204, 204, 0.8);
    }

    .unit span.heading {
        text-align: left;
        font-weight: 200;
        text-decoration: underline;
    }

    .unit span.summaryData {
        margin-left: 15px;
    }

    .unit.notDefined {
        display: none;
    }
    
    /* CSI AF 03/09/18S
    imposto lo stile per le schede che hanno media */
    .csi-media {
	font-weight: bold;
	color: dodgerblue;
    }
    /* CSI AF 03/09/18E */

</style >
<div id="ObjectWidget">
<script src = "<?php print __CA_URL_ROOT__; ?>/app/widgets/promemoriaTreeObject/resources/jstree.min.js" type = "text/javascript" ></script >
<div class = "dashboardwidgetsContentContainer" >
    <div class = "dashboardwidgetsScrollLarge" >
        <div class="search"><span class="fa fa-search"> </span> <input type="text" id="plugins4_q" placeholder="Cerca" /></div>
        <div id = "promemoria" style = "clear:both;height:100%;overflow-y:auto;overflow-x:hidden" >
        </div >
    </div >

    <div class="option">
        <span class="fa fa-cog fa-2x"></span>
    </div>

    <div class="dialog elementi" title="Elementi da visualizzare">
        <h3>Opzioni di visualizzazione <i class="fa fa-question" title="Gli elementi tipologia e numerazione verranno messi sempre prima del preferred label"></i></h3>
        <div class="ui-widget">
            <label for="tags">Aggiungi elementi: </label>
            <input id="tags" type="text" placeholder="Nome elemento">
            <ul class="list"></ul>
        </div>
    </div>

    <div class="dialog vista" title="Vista rapida">
        <div id="idNode" style="display:none"></div>
        <div class="result"></div>
    </div>
</div >
<script >
    jQuery(function ($) {

        $.ajax({
        	url: "<?php print __CA_URL_ROOT__; ?>/app/widgets/promemoriaTreeObject/ajax/ajax.php",
        	type: 'GET',
        	dataType: 'json',
        	data: {
        		operation: 'autocomplete',
        		cd: "<?php print $cd ?>"

//LM 02/11/17S CARICO VARIABILI PER IL PROFILO GLOBAL READER ONLY
                        , is_global_reader: '<?php print $is_global_reader; ?>'
                        , global_reader_user_id: '<?php print $g_ro_user_id; ?>'
                        , global_reader_group_id: '<?php print $g_ro_group_id; ?>'
//LM 02/11/17E 

	       	},
        })
        .done(function(data) {
        	console.log("done.function.data=", data);
            metadata = data.data;
            if (typeof data.select !== 'undefined' )
                selected = data.select;
            var res = [];
            $.each(metadata, function (i, val){
                //if (selected.indexOf(ui.item.value) < 0)
                res.push(val);
            });
            $.each(selected, function(i, val)    {
                if (val !== 'preferred_label')
                    var string = "<li><span id=\"" + val + "\" class='fa fa-remove remove'></span> " + val + "</li>";
                else
                    var string = "<li>Preferred Label</li>";
                jQuery('.list').append(string);
            });

            $('.remove').click(function ()  {
                var i = selected.indexOf($(this).attr('id'));
                if(i != -1) {
                    selected.splice(i, 1);
                }
                $(this).parent().remove();
            });

            $("#tags").autocomplete({
                source: res,
                select: function (e, ui)    {
                    console.log("autocomplete.ui", ui);
                    if (selected.indexOf(ui.item.value) < 0) {
                        var string = "<li><span id='" + ui.item.value + "' class='fa fa-remove remove'></span> " + ui.item.value + "</li>";
                        $('.list').append(string);

                        selected.push(ui.item.value);

                        $('.remove').click(function () {
                            var i = selected.indexOf($(this).attr('id'));
                            if (i != -1) {
                                selected.splice(i, 1);
                            }
                            $(this).parent().remove();
                        });
                    }

                }
            });
        })
        .fail(function() {
        	console.log("error");
        });

        jQuery(document).tooltip();
        //Luca
        //Settaggio delle opzioni

        var selected = ['preferred_label'];
        var metadata = ['preferred_label'];

        var dialog = $('.dialog.elementi').dialog({
            autoOpen: false,
            minHeight: 570,
            width: 470,
            modal: true,
            buttons: {
                "Salva": function ()    {
                    var key = []
                    $.each(selected, function (i, value){
                        if (value === 'preferred_label')    key.push(value);
                        else    key.push(Object.keys(metadata).filter(function(key) {return metadata[key] === value})[0]);
                    });

                    //Vado a salvare le informazioni appena aggiunte
                    $.ajax({
                        url: "<?php print __CA_URL_ROOT__; ?>/app/widgets/promemoriaTreeObject/ajax/ajax.php",
                        method: "GET",
                        data: {
                            'operation': 'salvaOpzioni',
                            'metadata': key,
                            "cd": "<?php print $cd; ?>"
                          	//LM 02/11/17S CARICO VARIABILI PER IL PROFILO GLOBAL READER ONLY
                            , "is_global_reader" : "<?php print $is_global_reader; ?>"
                            , "global_reader_user_id" : "<?php print $g_ro_user_id; ?>"
                            , "global_reader_group_id" : "<?php print $g_ro_group_id; ?>"
							//LM 02/11/17E
                        },
                        success: function() {
                            $('#logo').click();
                            //location.reload();
                        }
                    });
                },
                Cancel: function() {
                    dialog.dialog( "close" );
                }
            },
            close: function() {
            }
        });

        $('.option').click(function ()  {
            dialog.dialog('open');
            $( ".list" ).sortable({
                start : function(event, ui) {
                    var start_pos = ui.item.index();
                    ui.item.data('start_pos', start_pos);
                },
                update : function(event, ui) {
                    var index = ui.item.index();
                    var start_pos = ui.item.data('start_pos');

                    //Ordina selected
                    var tmp = selected[index];
                    selected[index] = selected[start_pos];
                    selected[start_pos] = tmp;
                },
                axis : 'y'
            });
            $( ".list" ).disableSelection();
        });

        var promemoria = $("#promemoria");
        promemoria.jstree({
            "plugins": ["dnd", "search", "state", "types"],
            "core" : {
                "animation" : 0,
                "check_callback" : true,
                "themes" : { 'name': 'proton', "stripes" : false, "responsive": true },
                "data": {
                    "url": "<?php print __CA_URL_ROOT__; ?>/app/widgets/promemoriaTreeObject/ajax/ajax.php",
                    "dataType" : "json",
                    "data": function (n) {
                        return {
                            "operation": "get_children",
                            "id": n.id != '#' ? n.id : 0,
                            "order" : (n.attr && n.attr("order")) ? n.attr("order") : '',
                            "verso": (n.attr && n.attr("verso")) ? n.attr("verso") : '',

                            "cd": "<?php print $cd ?>"

                          	//LM 02/11/17S CARICO VARIABILI PER IL PROFILO GLOBAL READER ONLY
                            , "is_global_reader" : "<?php print $is_global_reader; ?>"
                            , "global_reader_user_id" : "<?php print $g_ro_user_id; ?>"
                            , "global_reader_group_id" : "<?php print $g_ro_group_id; ?>"
							//LM 02/11/17E
                        };
                    }
                }
            },

            "dnd" : {"is_draggable" : function(node){ return <?php print ($is_readonly) ? 'false': 'true' ?>; }},

            /*"types": {
                "#" : {
                  "icon" : "fa fa-archive",
                  "valid_children": ["default"]
                },
                "default" : {
                  "icon" : "fa fa-archive",
                  "valid_children" : ["default","file"]
                },
                "file": {
                    "max_children": 0,
                    "icon": "fa fa-file-o",
                    "valid_children": []
                }
          }*/
        }).bind("move_node.jstree", function (e, data) {
            var post = promemoria.jstree().get_json();
            $.ajax({
                type: 'POST',
                url: "<?php print __CA_URL_ROOT__; ?>/app/widgets/promemoriaTreeObject/ajax/ajax.php?operation=save_node",
                data: {
                    "data": JSON.stringify(post)
                },
                success: function (r) {
                    console.log("Salvato");
                },
                error: function () {
                    console.log("Errore");
                },
                dataType: "json"
            });
        }).bind("load_node.jstree", function (e, node) {
//LM 02/11/17S IL LINK NON SARA' PIU' SEMPRE IN EDITING MA VARIERA' A SECONDA DEL RUOLO-PROFILO
//			$(e.target).find('.jstree-anchor').attr('href', "<?php print __CA_URL_ROOT__; ?>/index.php/editor/objects/ObjectEditor/Edit/object_id/" + node['node']['children_d'][0]);
                        $action = $(e.target).find('.jstree-anchor').find('.Summary');
                        if ($action.length == 0) {
                            $action = "Edit";
                        } else {
                            $action = "Summary";
                        }
                        $(e.target).find('.jstree-anchor').attr('href', "<?php print __CA_URL_ROOT__; ?>/index.php/editor/objects/ObjectEditor/" + $action + "/object_id/" + node['node']['children_d'][0]);

//LM 02/11/17E
        });
        	/*$('.showsummary').unbind().click(function (event)  {
                var id = $(this).parent().parent().attr('id');
                var title = $(this).parent().text();
                init(id, title);
            });*/

        $("#promemoria").on("dblclick", "a", function () {
//LM 02/11/17S IL LINK NON SARA' PIU' SEMPRE IN EDITING MA VARIERA' A SECONDA DEL RUOLO-PROFILO
 //           location.href = "<?php print __CA_URL_ROOT__; ?>/index.php/editor/objects/ObjectEditor/Edit/object_id/" + $(this).parent().attr("id");
                        //controllo se il nodo è in accesso in sola lettura o meno
                        $action = $(this).parent().find('.Summary');
                        if ($action.length == 0) {
                            $action = "Edit";
                        } else {
                            $action = "Summary";
                        }
           				location.href = "<?php print __CA_URL_ROOT__; ?>/index.php/editor/objects/ObjectEditor/" + $action + "/object_id/" + $(this).parent().attr("id");

//LM 02/11/17E
        });

        var to = false;
        $('#plugins4_q').keyup(function () {
            if(to) { clearTimeout(to); }
                to = setTimeout(function () {
                var v = $('#plugins4_q').val();
                promemoria.jstree(true).search(v);
            }, 250);
      });

        function init(id, text) {
            $result = $('.dialog.vista').find('.result');

            $.get('<?php print __CA_URL_ROOT__; ?>/index.php/editor/objects/ObjectEditor/Summary/object_id/' + id, "html")
                .done(function(data) {
                    $result.html('<h3>' + text + '</h3>');
                    $result.append($(data).find('table'));
                })
                .fail(function() {
                    $result.html('<span class="error">Errore caricamento Summary</span>');
                });

            $result.html('<i class="fa fa-spinner fa-3x fa-spin"></i>');
            dialogVista.dialog('open');
            keymove(id);
        }

/*      var dialogVista = $('.dialog.vista').dialog({
            autoOpen: false,
            minHeight: 570,
            width: "90%",
            modal: true,
            buttons: {
                "Apri": function ()    {
                    location.href = "<?php print __CA_URL_ROOT__; ?>/index.php/editor/objects/ObjectEditor/Edit/object_id/" + $(this).find('#idNode').text();
                }
            }
    	});

        function keymove(id)  {
            if (dialogVista.is(':visible'))  {
                $('.dialog.vista').unbind().keyup(function(e) {
                    var instance = $("#promemoria").jstree(true);
                    var node = instance.get_node(id.toString());

                    if (e.keyCode == 39 || e.keyCode == 40) {
                        node = instance.get_next_dom(node);
                        node = {id: node.attr('id'), text: node.text()};
                    } else if(e.keyCode == 37 || e.keyCode == 38)  {
                        node = instance.get_prev_dom(node);
                        node = {id: node.attr('id'), text: node.text()};
                    }

                    init(node.id, node.text);
                });
            }
        }*/
    });
</script >
</div>

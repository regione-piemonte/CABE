// SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
// SPDX-License-Identifier: GPL-3.0 
CSITools.controllers['CSICutAndPaste'] = {};
CSITools.controllers['CSICutAndPaste'].actions = {};



CSITools.controllers['CSICutAndPaste'].Init = function () {

}

CSITools.controllers['CSICutAndPaste'].ChangeSelectionHandler = function (selectedNodes) {
    console.log('CSICutAndPaste ChangeSelectionHandler', selectedNodes);

    var controller = 'CSIToolsActionControllerCSICutAndPaste';

    if (selectedNodes.length == 0) {
        if (CSITools.tree.jstree().can_paste() && CSITools.tree.jstree().get_buffer().mode == 'move_node') {

            jQuery('#' + controller + '-Cut').hide();
            jQuery('#' + controller + '-Undo').show();
            CSITools.enableController(controller);
        } else {
            jQuery('#' + controller + '-Cut').show();
            jQuery('#' + controller + '-Undo').hide();
            CSITools.disableController(controller);
        }

    } else {
        console.log('selezionati dei nodi');
        if (CSITools.tree.jstree().can_paste() && CSITools.tree.jstree().get_buffer().mode == 'move_node') {

            CSITools.disableAllControllers(controller, true);

            console.log('può incollare');

            CSITools.enableAction(controller + '-Paste')

        } else {
            CSITools.enableAction(controller + '-Cut');
            CSITools.disableAction(controller + '-Paste');
        }
    }

}

CSITools.controllers['CSICutAndPaste'].actions.Cut = function (selectedIDs) {
    console.log('CSICutAndPaste Cut', selectedIDs);

    jQuery('#CSIToolsActionControllerCSICutAndPaste-Cut').hide();
    jQuery('#CSIToolsActionControllerCSICutAndPaste-Undo').show();

    CSITools.disableAllControllers('CSIToolsActionControllerCSICutAndPaste', true);

    CSITools.enableAction('CSIToolsActionControllerCSICutAndPaste-Cut');
    CSITools.enableAction('CSIToolsActionControllerCSICutAndPaste-Undo');


    CSITools.tree.jstree().cut(selectedIDs);

    for (const element in selectedIDs) {
        const nodeId = selectedIDs[element];
        jQuery('#' + nodeId).hide();
    }

    CSITools.tree.jstree().deselect_all();

}

CSITools.controllers['CSICutAndPaste'].actions.Paste = function (selectedIDs) {
    console.log('CSICutAndPaste Paste', selectedIDs);
    if (selectedIDs.length < 1) {
        CSITools.notifyMessage('Selezionare almeno solo nodo', 'fa fa-exclamation-triangle', 'error');
    } else {
        nodeId = selectedIDs[0];
        CSITools.tree.jstree().paste(nodeId);
        CSITools.unlockAllControllers();
    }
}

CSITools.controllers['CSICutAndPaste'].actions.Undo = function (selectedIDs) {

    CSITools.unlockAllControllers();
    buffer = CSITools.tree.jstree().get_buffer();
    const nodes = buffer.node;
    //CSITools.tree.jstree().clear_buffer();
    CSITools.tree.jstree().deselect_all();

    for (const element in nodes) {
        const nodeId = nodes[element].id;
        jQuery('#' + nodeId).show();
    }

}

jQuery(function () {
    CSITools.tree.on('paste.jstree', function (e, data) {
        console.log(data.parent, data.node[0].id, data.mode);
        parentId = data.parent;
        childrenIds = data.node;
        CSITools.openLoadingLayer();
        CSITools.executeServerActionBehavior('CSICutAndPaste', 'Paste', { childrenIds, parentId });
    });
});

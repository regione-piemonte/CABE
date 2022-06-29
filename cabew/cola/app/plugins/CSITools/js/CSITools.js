// SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
// SPDX-License-Identifier: GPL-3.0
jQuery(function () {
    CSITools.disableAllControllers();
    jQuery(document).tooltip();
    CSITools.tree = jQuery('#cam_tree').jstree({
        "plugins": ["types", "state"],
        'core': {
            "check_callback": true,
            "multiple": true,
            'themes': {
                'name': 'proton',
                'responsive': true
            },
            'data': {
                "url": CSITools.rootUrl + "/service.php/CSITools/CSIArchiveManager/GetTree",
                "dataType": "json",
                "method": "POST",
                "data": function (n) {
                    return { "id": n.id !== '#' ? n.id : 0 }
                }
            },
            "types": {
                "default": {
                    "icon": "fa fa-archive"
                }
            },
        }
    });

    CSITools.tree.on('dblclick', '.jstree-node', CSITools.goToEdit);
    CSITools.tree.on('click', '.jstree-anchor', CSITools.selectNode);
    CSITools.tree.on('click', '.jstree-node .fa-plus', CSITools.openLoadingLayer);
    CSITools.tree.on('after_open.jstree', CSITools.closeLoadingLayer);
    CSITools.tree.on('after_close.jstree', CSITools.closeLoadingLayer);
    CSITools.tree.on('loaded.jstree', CSITools.closeLoadingLayer);

    CSITools.tree.on('select_node.jstree', CSITools.selectNodeHandler);
    CSITools.tree.on('deselect_node.jstree', CSITools.selectNodeHandler);

    CSITools.tree.on('refresh.jstree', CSITools.closeLoadingLayer);
    CSITools.tree.on('deselect_all.jstree', CSITools.deselectAllHandler);


    jQuery('.deselect-all').on('click', function () {
        CSITools.tree.jstree().deselect_all();
    });

    $('#leftNavSidebar .csitools-action').on('click', function (e) {
        e.preventDefault();
        let disabled = $(this).hasClass('disabled');

        if (!disabled) {
            var action = $(this).attr('data-action');
            action = action.replace(/__/g, "/");

            var requireParameters = $(this).attr('data-require-parameters');
            var controller = $(this).parent().attr('data-controller');
            var behavior = $(this).attr('data-behavior');

            var ids = CSITools.getSelectedNodes();

            if (requireParameters) {
                CSITools.loadConfigurationInterface(controller, action, { ids }, behavior);

            } else {
                CSITools.cleanConfigurationInterface();
                if (behavior == "server-side") {
                    window.open(CSITools.pluginUrl + controller + '/' + action + '/object_ids/' + ids.join(','));
                } else if (behavior == "custom") {
                    CSITools.controllers[controller].actions[action](ids);
                } else {
                    CSITools.openLoadingLayer();
                    CSITools.executeServerActionBehavior(controller, action, { ids });
                }
            }


        }
    });

    $(document).keydown(function (event) {
        var ch = event.which;
        if (ch == 27) {
            CSITools.cleanConfigurationInterface();
        }
    });
});

CSITools.deselectAllHandler = function () {
    jQuery('#cam_tree_selection .count').text(0);
    CSITools.changeSelectionHandler();
}

CSITools.enableAction = function (actionId) {
    jQuery('#' + actionId).removeClass('disabled');
}

CSITools.disableAction = function (actionId) {
    jQuery('#' + actionId).addClass('disabled');
}

CSITools.disableAllActions = function () {
    jQuery('.csitools-action').each(function () {
        jQuery(this).addClass('disabled');
    });
}

CSITools.enableController = function (controllerId) {
    if (!jQuery('#' + controllerId).hasClass('locked')) {
        jQuery('#' + controllerId).removeClass('disabled');
    }

}

CSITools.disableController = function (controllerId, lock) {
    jQuery('#' + controllerId).addClass('disabled');
    if (lock) {
        jQuery('#' + controllerId).addClass('locked');
    }
}

CSITools.unlockAllControllers = function () {
    jQuery('.CSIToolsActionController').each(function () {
        jQuery(this).removeClass('locked');
    });
}


CSITools.disableAllControllers = function (exception, lock) {

    jQuery('.CSIToolsActionController').each(function () {
        jQuery(this).addClass('disabled');

        if (lock) {
            jQuery(this).addClass('locked');
        }
    });

    if (exception != null) {
        jQuery('#' + exception).removeClass('disabled');
        jQuery('#' + exception).removeClass('locked');
    }
}

CSITools.enableAllControllers = function () {

    jQuery('.CSIToolsActionController').each(function () {
        if (!jQuery(this).hasClass('locked')) {
            jQuery(this).removeClass('disabled');
        }
    });
}



CSITools.initActions = function () {
    jQuery('.CSIToolsActionController').each(function () {
        let controller = jQuery(this).attr('data-controller');
        CSITools.controllers[controller].Init();
    });
}

CSITools.changeSelectionHandler = function () {
    let selectedNodes = CSITools.getSelectedNodes();

    jQuery('.csitools-action').each(function () {
        const currentActionScope = jQuery(this).attr('data-scope');
        const currentActionId = jQuery(this).attr('id');

        if (currentActionScope && selectedNodes.length > 0) {

            CSITools.executeServerActionBehavior('CSIArchiveManager', 'CheckScope', { scope: currentActionScope, ids: selectedNodes }, function (response) {
                (!response.status) ? CSITools.disableAction(currentActionId) : CSITools.enableAction(currentActionId);
            });
        }

    });

    jQuery('.CSIToolsActionController').each(function () {
        let currentController = jQuery(this).attr('data-controller');
        let currentId = jQuery(this).attr('id');
        let currentDefaultSelectionHandler = jQuery(this).attr('data-default-change-selection-handler');
        let currentAllowZeroSelection = jQuery(this).attr('data-allow-zero-selection');
        let currentAllowMultipleSelection = jQuery(this).attr('data-allow-multiple-selection');
        const currentScope = jQuery(this).attr('data-scope');

        CSITools.defaultChangeSelectionHandler(currentId, selectedNodes, currentAllowZeroSelection, currentAllowMultipleSelection, currentScope);
        if (!currentDefaultSelectionHandler) {
            CSITools.controllers[currentController].ChangeSelectionHandler(selectedNodes);
        }
    });


}

CSITools.defaultChangeSelectionHandler = function (controllerId, selectedNodes, allowZeroSelection, allowMultipleSelection, scope) {

    if (selectedNodes.length == 0) {
        (allowZeroSelection) ? CSITools.enableController(controllerId) : CSITools.disableController(controllerId);
    } else if (selectedNodes.length > 1) {
        (allowMultipleSelection) ? CSITools.enableController(controllerId) : CSITools.disableController(controllerId);
    } else {
        CSITools.enableController(controllerId);
    }

    if (scope && selectedNodes.length > 0) {

        CSITools.executeServerActionBehavior('CSIArchiveManager', 'CheckScope', { scope, ids: selectedNodes }, function (response) {
            if (!response.status) {
                CSITools.disableController(controllerId);
            }
        });
    }
}


CSITools.goToEdit = function (e) {
    window.open(CSITools.editUrl + jQuery(this).attr('id'), '_blank');
    e.stopPropagation();
}

CSITools.selectNode = function (e) {
    e.stopPropagation();
}


CSITools.defaultServerResponseHandler = function (response) {

    if (response.status) {
        CSITools.notifyMessage(response.message, 'fa fa-check', 'info');

    } else {
        CSITools.notifyMessage(response.message, 'fa fa-exclamation-triangle', 'error');
    }

    if (response.refresh) {
        CSITools.reloadTree();
    } else {
        CSITools.closeLoadingLayer();
    }



    CSITools.cleanConfigurationInterface();

}

CSITools.executeServerActionBehavior = function (controller, action, params, responseHandler = CSITools.defaultServerResponseHandler) {

    $.post(CSITools.pluginUrl + controller + '/' + action, params, responseHandler);
}



CSITools.loadConfigurationInterface = function (controller, action, params, behavior) {

    $.post(CSITools.pluginUrl + controller + '/ConfigurationInterface', params, function (response) {
        CSITools.disableAllControllers('CSIToolsActionController' + controller, true);
        jQuery('#CSIToolsConfigurationInterface').html(response);

        jQuery('#cam_tree_selection').hide();
        jQuery('#cam_tree_container').hide();

        const submitHandler = (behavior == 'server-side') ? CSITools.configurationInterfaceServerSideSubmitHandler : CSITools.configurationInterfaceSubmitHandler;

        jQuery('#CSIToolsConfigurationInterface form').on('submit', { controller, action, params }, submitHandler);
        jQuery('#CSIToolsConfigurationInterface .cancel').on('click', CSITools.cleanConfigurationInterface);
    });
}

CSITools.configurationInterfaceSubmitHandler = function (e) {
    e.preventDefault();

    let params = jQuery(this).serialize() + '&params=' + JSON.stringify(e.data['params']);
    CSITools.openLoadingLayer();
    CSITools.executeServerActionBehavior(e.data['controller'], e.data['action'], params);
}

CSITools.configurationInterfaceServerSideSubmitHandler = function (e) {
    e.preventDefault();

    let params = jQuery(this).serialize();

    let formInputs = '';
    jQuery.each(jQuery('input[type=text], select ,textarea', this), function (k) {
        formInputs += '/' + jQuery(this).attr('name') + '/' + jQuery(this).val();
    });


    const path = CSITools.pluginUrl + e.data['controller'] + '/' + e.data['action'] + formInputs + '/object_ids/' + e.data['params'].ids.join(',');
    console.log(path);
    window.open(path);
    CSITools.cleanConfigurationInterface();
}

CSITools.cleanConfigurationInterface = function () {
    jQuery('#CSIToolsConfigurationInterface').html('');
    jQuery('#cam_tree_selection').show();
    jQuery('#cam_tree_container').show();
    CSITools.unlockAllControllers();
    CSITools.changeSelectionHandler();
}


CSITools.getSelectedNodes = function () {
    var selected = CSITools.tree.jstree().get_selected();
    var data = selected.map(function (select) {
        return select;
    });

    return data;
}


CSITools.reloadTree = function () {
    CSITools.tree.jstree().refresh();
}

CSITools.notifyMessage = function (message, icon, level) {
    jQuery.notify({
        icon: icon,
        message: message
    },
        {
            type: level,
            delay: 7000,
            allow_dismiss: false,
            placement: {
                from: "bottom",
                align: "right"
            },
            offset: 60,
            template: '<div data-notify="container" class="csitools-popup csitools-popup-{0} col-xs-11 col-sm-3 alert  alert-{0}" role="alert">' +
                '<button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>' +
                '<span data-notify="icon"></span> <div class="noty-cont">' +
                '<span data-notify="title">{1}</span>' +
                '<span data-notify="message">{2}</span>' +
                '</div></div>'
        });
}


CSITools.selectNodeHandler = function (e, data) {
    jQuery('#cam_tree_selection .count').text(data.selected.length);
    CSITools.changeSelectionHandler();
}



CSITools.onActionClick = function (button) {
    console.log(button);
}

CSITools.openLoadingLayer = function () {
    jQuery('#csi-overlay').css('display', 'block');
}

CSITools.closeLoadingLayer = function () {
    jQuery('#csi-overlay').css('display', 'none');
}
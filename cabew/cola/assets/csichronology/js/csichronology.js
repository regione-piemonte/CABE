// SPDX-FileCopyrightText: Copyright 2022 | Regione Piemonte
// SPDX-License-Identifier: GPL-3.0 
jQuery(document).ready(function () {
    var cronBox = null;
    if (jQuery('#ca_attribute_ObjectEditorForm_cron_new').length) {
        cronBox = jQuery('#ca_attribute_ObjectEditorForm_cron_new');

        csiCronUpdateInterface(cronBox);

        cronBox.parent().find('.caItemList').on('DOMSubtreeModified', function () {
            var cronItems = cronBox.parent().find('.repeatingItem');

            cronItems.each(function (index) {
                box = this;
                if (!jQuery(box).hasClass('csi-updated')) {
                    csiCronUpdateInterface(cronBox);
                }
            });
        });
    }

    function csiCronGetFieldValue(field, type) {
        var val = field.parent().find(type).val();
        return (val == '' || val == undefined) ? null : val;
    }

    function csiCronUnsetField(field, type) {
        csiCronDisableField(field, type);
        field.parent().find(type).val('');
    }

    function csiCronDisableField(field, type) {
        field.parent().find(type).prop('disabled', 'disabled');
    }

    function csiCronEnableField(field, type) {
        field.parent().find(type).prop('disabled', false);
    }

    function csiCronManageInterface(box) {
        var sec_da = jQuery(box).find('._attribute_value_sec_da');
        var fraz_sec_da = jQuery(box).find('._attribute_value_fraz_sec_da');
        var data_da = jQuery(box).find('._attribute_value_data_da');

        var sec_a = jQuery(box).find('._attribute_value_sec_a');
        var fraz_sec_a = jQuery(box).find('._attribute_value_fraz_sec_a');
        var data_a = jQuery(box).find('._attribute_value_data_a');

        csiCronManageDataBlock(sec_da, fraz_sec_da, data_da);
        csiCronManageDataBlock(sec_a, fraz_sec_a, data_a);
    }

    function csiCronManageDataBlock(sec, fraz_sec, data) {
        var sec_value = csiCronGetFieldValue(sec, 'select');
        var fraz_sec_value = csiCronGetFieldValue(fraz_sec, 'select');
        var data_value = csiCronGetFieldValue(data, 'input');

        if (data_value) {
            csiCronUnsetField(sec, 'select');
            csiCronUnsetField(fraz_sec, 'select');
            fraz_sec.parent().find('select').css('border', '1px solid #ccc');
        } else {
            csiCronEnableField(sec, 'select');
            if (sec_value) {
                csiCronUnsetField(data, 'input');
                csiCronEnableField(fraz_sec, 'select');

                if (fraz_sec_value) {
                    fraz_sec.parent().find('select').css('border', '1px solid #ccc');
                } else {
                    fraz_sec.parent().find('select').css('border', '1px solid red');
                }
            } else {
                csiCronEnableField(data, 'input');
                csiCronUnsetField(fraz_sec, 'select');
                fraz_sec.parent().find('select').css('border', '1px solid #ccc');

                if (fraz_sec_value) {
                    csiCronUnsetField(fraz_sec, 'select');
                }
            }
        }
    }

    function csiCronUpdateInterface(cronBox) {
        var items = cronBox.parent().find('.repeatingItem');

        jQuery('._attribute_value_data_calc_da').parent().hide();
        jQuery('._attribute_value_data_calc_a').parent().hide();
        jQuery('._attribute_value_is_single').parent().hide();
        jQuery('._attribute_value_data_da_output').parent().hide();
        jQuery('._attribute_value_data_a_output').parent().hide();

        jQuery('#ca_attribute_ObjectEditorForm_cron_new').parent().find('select').css('width', '248px');
        jQuery('._attribute_value_data_da').parent().find('input').css('width', '130px');
        jQuery('._attribute_value_data_a').parent().find('input').css('width', '130px');
        jQuery('._attribute_value_fraz_sec_da').parents('table').css('padding-top', '50px');
        jQuery('._attribute_value_sec_a').parents('table').css('padding-bottom', '50px');

        items.each(function (index) {
            var box = this;
            if (!jQuery(box).hasClass('csi-updated')) {
                jQuery(box).addClass('csi-updated');

                var sec_da = jQuery(box).find('._attribute_value_sec_da').parent().parent();
                jQuery(box).find('._attribute_value_fraz_sec_da').parent().parent().parent().prepend(sec_da);

                data_da = jQuery(box).find('._attribute_value_data_da').parent().parent();
                jQuery(box).find('._attribute_value_fraz_sec_da').parent().parent().parent().find('td:last-child').remove();
                jQuery(box).find('._attribute_value_fraz_sec_da').parent().parent().parent().append(data_da);


                var sec_a = jQuery(box).find('._attribute_value_sec_a').parent().parent();
                jQuery(box).find('._attribute_value_fraz_sec_a').parent().parent().parent().prepend(sec_a);

                data_a = jQuery(box).find('._attribute_value_data_a').parent().parent();
                jQuery(box).find('._attribute_value_fraz_sec_a').parent().parent().parent().find('td:last-child').remove();
                jQuery(box).find('._attribute_value_fraz_sec_a').parent().parent().parent().append(data_a);

                csiCronManageInterface(box);

                jQuery(box).find('._attribute_value_sec_da').parent().find('select').change({
                    box: box
                }, function (event) {
                    csiCronManageInterface(event.data.box);
                });

                jQuery(box).find('._attribute_value_fraz_sec_da').parent().find('select').change({
                    box: box
                }, function (event) {
                    csiCronManageInterface(event.data.box);
                });

                jQuery(box).find('._attribute_value_data_da').parent().find('input').keyup({
                    box: box
                }, function (event) {
                    csiCronManageInterface(event.data.box);
                });

                jQuery(box).find('._attribute_value_sec_a').parent().find('select').change({
                    box: box
                }, function (event) {
                    csiCronManageInterface(event.data.box);
                });

                jQuery(box).find('._attribute_value_fraz_sec_a').parent().find('select').change({
                    box: box
                }, function (event) {
                    csiCronManageInterface(event.data.box);
                });

                jQuery(box).find('._attribute_value_data_a').parent().find('input').keyup({
                    box: box
                }, function (event) {
                    csiCronManageInterface(event.data.box);
                });

            }
        });
    }

    if (jQuery('#advancedSearchFormContainer').length) {
        jQuery('._attribute_value_data_calc_da').parent().parent().parent().hide();
        jQuery('._attribute_value_data_calc_a').parent().parent().parent().hide();
        jQuery('._attribute_value_is_single').parent().parent().parent().hide();
        jQuery('._attribute_value_data_da_output').parent().parent().parent().hide();
        jQuery('._attribute_value_data_a_output').parent().parent().parent().hide();

        jQuery('#advancedSearchFormContainer').on('DOMSubtreeModified', function () {
            jQuery('._attribute_value_data_calc_da').parent().parent().parent().hide();
            jQuery('._attribute_value_data_calc_a').parent().parent().parent().hide();
            jQuery('._attribute_value_is_single').parent().parent().parent().hide();
            jQuery('._attribute_value_data_da_output').parent().parent().parent().hide();
            jQuery('._attribute_value_data_a_output').parent().parent().parent().hide();
        });
    }
});


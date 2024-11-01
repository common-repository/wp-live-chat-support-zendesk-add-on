jQuery(document).ready(function () {

	jQuery("body").on("click", ".wplc_admin_convert_chat_to_zendesk_ticket", function() {
        jQuery("#wplc_admin_convert_chat_to_zendesk_ticket").hide();
        html = "<span class='wplc_zendesk_loading'><em>"+wplc_zendesk_string_loading+"</em></span>";
                    
        jQuery("#wplc_admin_convert_chat_to_zendesk_ticket").after(html);
        var cur_id = jQuery(this).attr("cid");
        var data = {
            action: 'wplc_zendesk_admin_convert_chat',
            security: wplc_zendesk_nonce,
            cid: cur_id
        };
        jQuery.post(ajaxurl, data, function(response) {
            returned_data = JSON.parse(response);
            console.log(returned_data.constructor);
            if (returned_data.constructor === Object) {
                if (returned_data.errorstring) {
                    jQuery(".wplc_zendesk_loading").hide();
                    jQuery("#wplc_admin_convert_chat_to_zendesk_ticket").after("<div class='error' style='display:block; clear:both; margin-top:10px; margin-bottom:10px;'><p>Error: "+returned_data.errorstring+"</p></div>");
                } else {
                    jQuery(".wplc_zendesk_loading").hide();

                    html = "<span class=''>"+wplc_zendesk_string_ticket_created+" (ID: "+returned_data.success+")</span>";
                    jQuery("#wplc_admin_convert_chat_to_zendesk_ticket").after(html);
                    jQuery("#wplc_admin_convert_chat_to_zendesk_ticket").hide();
                }
            }

        });


    });

});
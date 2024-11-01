<?php
/*
Plugin Name: WP Live Chat Support - Zendesk Add-on
Plugin URL: http://wp-livechat.com
Description: Easily convert your WP Live Chat Support chat sessions to tickets on Zendesk
Version: 1.0.01
Author: WP-LiveChat
Author URI: http://wp-livechat.com
Contributors: WP-LiveChat,CodeCabin_, NickDuncan, Jarryd Long, dylanauty
Text Domain: wp-live-chat-support-zendesk-add-on
Domain Path: /languages
*/

/*
* 1.0.01 - 2016-09-20
* Tested on WordPress 4.6.1
* Moved Settings to Tab
* Removed Button Icon, and Added Label
* 
* 1.0.00 - 2015-10-19
* Launch
*
* 
 */

if(!defined('WPLC_ZENDESK_PLUGIN_DIR')) {
	define('WPLC_ZENDESK_PLUGIN_DIR', dirname(__FILE__));
}

global $wplc_zendesk_version;
global $current_chat_id;
$wplc_zendesk_version = "1.0.01";

/* hooks */
add_action('wplc_hook_admin_visitor_info_display_after','wplc_zendesk_add_admin_button');
add_action('wplc_hook_admin_javascript_chat','wplc_zendesk_admin_javascript');

add_filter("wplc_filter_setting_tabs","wplc_zendesk_settings_tab_heading");
add_action("wplc_hook_settings_page_more_tabs","wplc_zendesk_settings_tab_content");

add_action('wplc_hook_admin_settings_save','wplc_zendesk_save_settings');

/* ajax callbacks */
add_action('wp_ajax_wplc_zendesk_admin_convert_chat', 'wplc_zendesk_callback');


/* init */
add_action("init","wplc_zendesk_first_run_check");




/**
* Check if this is the first time the user has run the plugin. If yes, set the default settings
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_zendesk_first_run_check() {
	if (!get_option("WPLC_ZENDESK_FIRST_RUN")) {
		/* set the default settings */
        $wplc_zendesk_data['wplc_zendesk_enable'] = 1;
        $wplc_zendesk_data['wplc_zendesk_user'] = false;
        $wplc_zendesk_data['wplc_zendesk_url'] = false;
        $wplc_zendesk_data['wplc_zendesk_api'] = false;
        $wplc_zendesk_data['wplc_zendesk_assignee'] = false;
      

        update_option('WPLC_ZENDESK_SETTINGS', $wplc_zendesk_data);
        update_option("WPLC_ZENDESK_FIRST_RUN",true);
	}
}


/**
* Adds the convert to ticket button to the visitor box in the active chat window
*
* @since       1.0.0
* @param       int $cid The current chat ID
* @return
*
*/
function wplc_zendesk_add_admin_button($cid) {

	$wplc_zendesk_settings = get_option("WPLC_ZENDESK_SETTINGS");
	$wplc_enable = $wplc_zendesk_settings['wplc_zendesk_enable'];
	if (isset($wplc_enable) && $wplc_enable == 1) {

    	echo "<a href=\"javascript:void(0);\" cid='".sanitize_text_field($cid)."' class=\"wplc_add_on_button_chat wplc_admin_convert_chat_to_zendesk_ticket button button-secondary\" title=\"".__("Convert to Zendesk support ticket","wp-live-chat-support-zendesk-add-on")."\" id=\"wplc_admin_convert_chat_to_zendesk_ticket\" style='float:left; margin-right:10px;'>".__("Zendesk Support Ticket","wp-live-chat-support-zendesk-add-on")."</a>";
	}
}




/**
* Adds the javascript calls to the chat window which handles the ajax requests
*
* @since       [1.0.0]
* @param       
* @return
*
*/
function wplc_zendesk_admin_javascript() {
	$wplc_zendesk_ajax_nonce = wp_create_nonce("wplc_zendesk_nonce");
    wp_register_script('wplc_zendesk_convert_admin', plugins_url('js/wplc_zendesk.js', __FILE__), null, '', true);
    wp_enqueue_script('wplc_zendesk_convert_admin');
    wp_localize_script( 'wplc_zendesk_convert_admin', 'wplc_zendesk_nonce', $wplc_zendesk_ajax_nonce);
	$wplc_zendesk_string_loading = __("Creating ticket...","wp-live-chat-support-zendesk-add-on");
    $wplc_zendesk_string_ticket_created = __("Ticket created","wp-live-chat-support-zendesk-add-on");
    $wplc_zendesk_string_error1 = sprintf(__("There was a problem creating the ticket. Please <a target='_BLANK' href='%s'>contact support</a>.","wp-live-chat-support-zendesk-add-on"),"http://wp-livechat.com/contact-us/?utm_source=plugin&utm_medium=link&utm_campaign=error_creating_ticket");
    wp_localize_script( 'wplc_zendesk_convert_admin', 'wplc_zendesk_string_ticket_created', $wplc_zendesk_string_ticket_created);
    wp_localize_script( 'wplc_zendesk_convert_admin', 'wplc_zendesk_string_error1', $wplc_zendesk_string_error1);
    wp_localize_script( 'wplc_zendesk_convert_admin', 'wplc_zendesk_string_loading', $wplc_zendesk_string_loading);

}





/**
* Ajax callback handler
*
* @since       	1.0.0
* @param       
* @return 		void
*
*/
function wplc_zendesk_callback() {
	$check = check_ajax_referer( 'wplc_zendesk_nonce', 'security' );
	if ($check == 1) {

        if ($_POST['action'] == "wplc_zendesk_admin_convert_chat") {
        	if (isset($_POST['cid'])) {
        		$cid = intval($_POST['cid']);
        		echo json_encode(wplc_zendesk_convert_chat(sanitize_text_field($cid)));
        	} else {
        		echo json_encode(array("error"=>"no CID"));
        	}
        	wp_die();
        }

        wp_die();
    }
    wp_die();
}





/**
* Converts the chat to the ticket
*
* @since 		1.0.0
* @param 		int $cid Chat ID
* @return 		array Returns either true or an error with a description of the error
*
*/
function wplc_zendesk_convert_chat($cid) {
	
	if (!$cid) { return array("errorstring"=>"no CID"); }


 	global $wpdb;
    global $wplc_tblname_chats;
    $results = $wpdb->get_results("SELECT * FROM $wplc_tblname_chats WHERE `id` = '$cid' LIMIT 1");
    
    foreach ($results as $result) {
         $email = $result->email;
         $name = $result->name;
    }
    if (!$email) { return array("error"=>"no email"); }



    $bad_ip = array (
        "127.0.0.1"
    );
    if(in_array($_SERVER['REMOTE_ADDR'], $bad_ip)){
        return array("errorstring"=>"Zendesk does not allow calls from 'localhost'.");
    }

    /* get zendesk api details */
    $wplc_zendesk_settings = get_option("WPLC_ZENDESK_SETTINGS");
    
    if(isset($wplc_zendesk_settings['wplc_zendesk_user']) && $wplc_zendesk_settings['wplc_zendesk_user']  != "") { $zduser = $wplc_zendesk_settings['wplc_zendesk_user']; } else {$zduser = false; }
    if(isset($wplc_zendesk_settings['wplc_zendesk_url']) && $wplc_zendesk_settings['wplc_zendesk_url']  != "") { $zdurl = $wplc_zendesk_settings['wplc_zendesk_url']; } else {$zdurl= false; }
    if(isset($wplc_zendesk_settings['wplc_zendesk_api']) && $wplc_zendesk_settings['wplc_zendesk_api']  != "") { $zdapi = $wplc_zendesk_settings['wplc_zendesk_api']; } else {$zdapi = false; }
    if(isset($wplc_zendesk_settings['wplc_zendesk_assignee']) && $wplc_zendesk_settings['wplc_zendesk_assignee']  != "") { $zdassignee = $wplc_zendesk_settings['wplc_zendesk_assignee']; } else {$zdassignee = false; }

    if (!$zduser || !$zdurl || !$zdapi) { return array("errorstring"=>"Please add your Zendesk API credentials in the settings page."); }

    $content = wplc_zendesk_get_transcript($cid);
    require_once("includes/codecabin.zendesk.class.php");
    $zd = new CodeCabinZendesk();
    $zd->setVar("ZDAPIKEY",$zdapi);
    $zd->setVar("ZDUSER",$zduser);
    $zd->setVar("ZDURL",$zdurl);
    

    if ($zdassignee) {
        $create = json_encode(  
            array(  
                'ticket' => array(  
                    "status" => "new",
                     'requester' => array(  
                         'name' => $name,  
                         'email' => $email 
                     ),  
                     'subject' => sprintf(__("Chat transcript with %s (%s)","wp-live-chat-support-zendesk-add-on"),$name,$email),
                     "description" => $content,
                     "assignee_id" => intval($zdassignee),
                )  
         ),  
         JSON_FORCE_OBJECT  
        );
    } else {
        $create = json_encode(  
            array(  
                'ticket' => array(  
                    "status" => "new",
                     'requester' => array(  
                         'name' => $name,  
                         'email' => $email 
                     ),  
                     'subject' => sprintf(__("Chat transcript with %s (%s)","wp-live-chat-support-zendesk-add-on"),$name,$email),
                     "description" => $content
                )  
         ),  
         JSON_FORCE_OBJECT  
        );        
    }
    $response = $zd->curlWrap("/tickets.json", $create, "POST");
    if (isset($response->error)) {
        return array("errorstring" => $response->error);
    }

    return array("success"=>$response->ticket->id);

}


/**
* Return the body of the chat transcript
*
* @since       	1.0.0
* @param       
* @return		string Transcript HTML
*
*/
function wplc_zendesk_get_transcript($cid) {
    if (intval($cid) > 0) { 
		return wplc_return_chat_messages(intval($cid),true,false);
	} else {
		return "0";
	}

}


/**
* Latch onto the default POST handling when saving live chat settings
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_zendesk_save_settings() {
	if (isset($_POST['wplc_save_settings'])) {
        if (isset($_POST['wplc_zendesk_enable'])) {
            $wplc_zendesk_data['wplc_zendesk_enable'] = esc_attr($_POST['wplc_zendesk_enable']);
        } else {
            $wplc_zendesk_data['wplc_zendesk_enable'] = 0;
        }
        if (isset($_POST['wplc_zendesk_assignee'])) {
            $wplc_zendesk_data['wplc_zendesk_assignee'] = esc_attr($_POST['wplc_zendesk_assignee']);
        } else {
            $wplc_zendesk_data['wplc_zendesk_assignee'] = false;
        }

        if (isset($_POST['wplc_zendesk_api'])) {
            $wplc_zendesk_data['wplc_zendesk_api'] = esc_attr($_POST['wplc_zendesk_api']);
        } else {
            $wplc_zendesk_data['wplc_zendesk_api'] = false;
        }

        if (isset($_POST['wplc_zendesk_user'])) {
            $wplc_zendesk_data['wplc_zendesk_user'] = esc_attr($_POST['wplc_zendesk_user']);
        } else {
            $wplc_zendesk_data['wplc_zendesk_user'] = false;
        }      

        if (isset($_POST['wplc_zendesk_url'])) {
            $wplc_zendesk_data['wplc_zendesk_url'] = esc_attr($_POST['wplc_zendesk_url']);
        } else {
            $wplc_zendesk_data['wplc_zendesk_url'] = false;
        }                  
        update_option('WPLC_ZENDESK_SETTINGS', $wplc_zendesk_data);

    }
}

/**
 * Add Settings Tab Heading
 *
 * @since        1.0.02
 * @param       
 * @return       void
*/
function wplc_zendesk_settings_tab_heading($tab_array){
    $tab_array['wplc_zendesk_tab'] = array(
      "href" => "#wplc_zendesk_tab",
      "icon" => 'fa fa-bug',
      "label" => __("Zedesk Support Ticket","wp-live-chat-support-slack-notifications")
    );
    return $tab_array;
}

/**
 * Add Settings Tab Content
 *
 * @since        1.0.02
 * @param       
 * @return       void
*/
function wplc_zendesk_settings_tab_content(){
    echo "<div id='wplc_zendesk_tab'>";
    echo wplc_zendesk_settings();
    echo "</div>";
}

/**
* Display the chat conversion settings section
*
* @since       	1.0.0
* @param       
* @return		void
*
*/
function wplc_zendesk_settings() {
	$wplc_zendesk_settings = get_option("WPLC_ZENDESK_SETTINGS");
	echo "<hr />";
	echo "<h3>".__("Chat To Ticket Conversion Settings (Zendesk)",'wp-live-chat-support-zendesk-add-on')."</h3>";
	echo "<table class='form-table' width='700'>";
	echo "	<tr>";
	echo "		<td width='400' valign='top'>".__("Enable conversion:","wp-live-chat-support-zendesk-add-on")."</td>";
	echo "		<td>";
	echo "			<input type=\"checkbox\" value=\"1\" name=\"wplc_zendesk_enable\" ";
	if(isset($wplc_zendesk_settings['wplc_zendesk_enable'])  && $wplc_zendesk_settings['wplc_zendesk_enable'] == 1 ) { echo "checked"; }
	echo " />";
	echo "		</td>";
	echo "	</tr>";

    echo "  <tr>";
    echo "      <td width='400' valign='top'>".__("Zendesk API URL","wp-live-chat-support-slack-notifications")."</td>";
    echo "      <td>";
    echo "          <input type='text' name=\"wplc_zendesk_url\" value='";
    if(isset($wplc_zendesk_settings['wplc_zendesk_url'])) { echo stripslashes($wplc_zendesk_settings['wplc_zendesk_url']); }
    echo "' />";
    echo " <small><em>".__( 'Example: https://yourcompany.zendesk.com/api/v2', 'wp-live-chat-support-slack-notifications' )."</em></small>";    
    echo "      </td>";
    echo "  </tr>";  

    echo "  <tr>";
    echo "      <td width='400' valign='top'>".__("Zendesk User","wp-live-chat-support-slack-notifications")."</td>";
    echo "      <td>";
    echo "          <input type='text' name=\"wplc_zendesk_user\" value='";
    if(isset($wplc_zendesk_settings['wplc_zendesk_user'])) { echo stripslashes($wplc_zendesk_settings['wplc_zendesk_user']); }
    echo "' />";
    echo " <small><em>".__( 'Example: john@doe.com', 'wp-live-chat-support-slack-notifications' )."</em></small>";    
    echo "      </td>";
    echo "  </tr>";   

    echo "  <tr>";
    echo "      <td width='400' valign='top'>".__("Zendesk API Key","wp-live-chat-support-slack-notifications")."</td>";
    echo "      <td>";
    echo "          <input type='text' name=\"wplc_zendesk_api\" value='";
    if(isset($wplc_zendesk_settings['wplc_zendesk_api'])) { echo stripslashes($wplc_zendesk_settings['wplc_zendesk_api']); }
    echo "' />";
    echo "      </td>";
    echo "  </tr>";   

    echo "  <tr>";
    echo "      <td width='400' valign='top'>".__("Zendesk Default Assignee","wp-live-chat-support-slack-notifications")."</td>";
    echo "      <td>";
    echo "          <input type='text' name=\"wplc_zendesk_assignee\" value='";
    if(isset($wplc_zendesk_settings['wplc_zendesk_assignee'])) { echo stripslashes($wplc_zendesk_settings['wplc_zendesk_assignee']); }
    echo "' />";
    echo " <small><em>".__( 'User ID. Can be left blank.', 'wp-live-chat-support-slack-notifications' )."</em></small>";    
    echo "      </td>";
    echo "  </tr>";  
     

	echo "</table>";
}
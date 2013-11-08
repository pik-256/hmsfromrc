<?php

/*
 * Author: thierry.schmit@gmail.com
 * Licence: CC by 3.0 deed
 * 
 * See README
 * See package.xml
 */

// if your change this value, please think to localization
define ("HMSFROMRC_SECTION", "hmsfromrc");
define ("ARBLOCK", "ar");
define ("FWBLOCK", "fw");

class hmsfromrc extends rcube_plugin
{
    public $task = "settings";
    public $rc;
	
    private $ar_enabled;
    private $ar_subject;
    private $ar_body;
    private $ar_ae_enabled;
    private $ar_ae_date;
	
    function init() {
        $this->rc = rcmail::get_instance();
        
        $this->include_script(HMSFROMRC_SECTION . '.js');
		
        //Configuration reading
        $this->load_config();
        if ( $this->rc->config->get('hmailserver_server_for_hmsrc', null) == null )  {
	        $this->load_config('config.inc.php.dist');
        }
		
        //localization loading
        $this->add_texts('localization/');
	
        $this->add_hook('preferences_sections_list', array($this, 'preferences_sections_list'));
        $this->add_hook('preferences_list', array($this, 'preferences_list'));
        $this->add_hook('preferences_save', array($this, 'preferences_save')); 
    }
	
    function preferences_sections_list($args) {
        //Create a new section in the preferences
        $args["list"][HMSFROMRC_SECTION] = array("id" => HMSFROMRC_SECTION, "section" => $this->gettext(HMSFROMRC_SECTION));	
	    
        return $args;
    }
	
    function preferences_list($p) {
	    //You must set a block or the section will not be displayed
        if ( $p['section'] == HMSFROMRC_SECTION ) {
            $this->loadData();
		
            // ================================================================
            //Auto reply
			
            $p['blocks'][ARBLOCK]['name'] = $this->gettext('autoreply');
			
            $ctrl_id = 'ztp_ar_enabled';
            $ctrl = new html_checkbox(array ('name' => '_ar_enabled', 'id' => $ctrl_id, 'value' => 1 ));
            $p['blocks']['ar']['options']['ar_enabled'] = array(
                'title' => html::label($ctrl_id, Q($this->gettext('ar_enabled'))),
                'content' => $ctrl->show($this->ar_enabled)
            );
			
            $ctrl_id = 'ztp_ar_subject';
            $ctrl = new html_inputfield(array ('type' => 'text', 'name' => '_ar_subject', 'id' => $ctrl_id));
            $p['blocks'][ARBLOCK]['options']['ar_subject'] = array(
                'title' => html::label($ctrl_id, Q($this->gettext('ar_subject'))),
                'content' => $ctrl->show($this->ar_subject)
            );
			
            $ctrl_id = 'ztp_ar_body';
            $ctrl = new html_textarea(array ('name' => '_ar_body', 'id' => $ctrl_id, 'rows' => 5, 'cols' => 50));
            $p['blocks'][ARBLOCK]['options']['ar_body'] = array(
                'title' => html::label($ctrl_id, Q($this->gettext('ar_body'))),
                'content' => $ctrl->show($this->ar_body)
            );
            
            $ctrl_id = 'ztp_ar_ae_enabled';
            $ctrl = new html_checkbox(array ('name' => '_ar_ae_enabled', 'id' => $ctrl_id, 'value' => 1 ));            
            $ctrl2_id = 'ztp_ar_ae_date';
            $ctrl2 = new html_inputfield(array ('name' => '_ar_ae_date', 'id' => $ctrl2_id));            
            $p['blocks'][ARBLOCK]['options']['ar_ae_enabled'] = array(
                'title' => html::label($ctrl_id, Q($this->gettext('ar_ae_enabled'))),
                'content' => $ctrl->show($this->ar_ae_enabled) . " " . $ctrl2->show($this->ar_ae_date)
            );
            
			
            // ================================================================
            //Forwarder
			
            //$p['blocks'][FWBLOCK]['name'] = $this->gettext('forwarder');
			
            //$ctrl_id = 'ztp_fw_enabled';
            //$ctrl = new html_checkbox(array ('name' => '_fw_enabled', 'id' => $ctrl_id));
            //$p['blocks'][FWBLOCK]['options']['fw_enabled'] = array(
            //    'title' => html::label($ctrl_id, Q($this->gettext('fw_enabled'))),
            //    'content' => $ctrl->show('1')
            //);
        }
		
        return $p;
    }
	
    function preferences_save($p) {
        try {
            $this->saveData(array(
                'ar_enabled' => get_input_value('_ar_enabled', RCUBE_INPUT_POST),
                'ar_subject' => get_input_value('_ar_subject', RCUBE_INPUT_POST),
                'ar_body' => get_input_value('_ar_body', RCUBE_INPUT_POST),
                'ar_ae_enabled' => get_input_value('_ar_ae_enabled', RCUBE_INPUT_POST),
                'ar_ae_date' => get_input_value('_ar_ae_date', RCUBE_INPUT_POST)
            ));
        } catch (Exception $e) {
            $p['abort'] = true;
            $p['result'] = false;
            $p['message'] = $e->getMessage();
        }
		
        return $p;
    }	
	
    function getHmsDb() {
        $dbConf = $this->rc->config->get('hmailserver_server_for_hmsrc');
        $dsn = $dbConf['Protocol']."://".$dbConf['Username'].":".$dbConf['Password']."@".$dbConf['Server']."/".$dbConf["Database"];
        $db = rcube_db::factory(
	        $dsn, "", false);
        $db->db_connect('w');
		
        return $db;
    }
	
    function saveData($args) {	    
        $db = $this->getHmsDb();	
		
        $db->query(
           'update hm_accounts set accountvacationmessageon = ?, accountvacationsubject = ?, accountvacationmessage = ?, accountvacationexpires = ?, accountvacationexpiredate = ? where accountaddress = ?',
            array($args['ar_enabled'] == null ? 0 : $args['ar_enabled'], $args['ar_subject'], $args['ar_body'], 
                  $args['ar_ae_enabled'] == null ? 0 : 1, $args["ar_ae_date"], 
                  $this->rc->user->get_username('mail'))
        );
    }
	
    function loadData() {
        $db = $this->getHmsDb();
		
        $db->query(
            'select accountvacationmessageon, accountvacationsubject, accountvacationmessage, accountvacationexpires, accountvacationexpiredate from hm_accounts where accountaddress = ?',
            array($this->rc->user->get_username('mail'))
        );
		
        $r = $db->fetch_array();
		
        $this->ar_enabled = $r[0];
        $this->ar_subject = $r[1];;
        $this->ar_body = $r[2];
        $this->ar_ae_enabled = $r[3];
        $this->ar_ae_date = substr($r[4], 0, 10);
    }
}

?>
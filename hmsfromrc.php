<?php

/*
 *
  Original Author: thierry schmit (thierry.schmit@gmail.com)
  Modifications: Hazar Karabay (hazarkarabay.com.tr)
  Licence: CC BY 4.0 (Attibution International) [http://creativecommons.org/licenses/by/4.0/]
 *
 */

// if your change this value, please think to localization
define("HMSFROMRC_SECTION", "hmsfromrc");
define("ARBLOCK", "ar");
define("FWBLOCK", "fw");

class hmsfromrc extends rcube_plugin {

	public $task = "settings";
	public $rc;
	private $ar_enabled;
	private $ar_subject;
	private $ar_body;
	private $ar_ae_enabled;
	private $ar_ae_date;
	private $fw_enabled;
	private $fw_address;
	private $fw_keeporiginal;

	function init() {
		$this->rc = rcmail::get_instance();

		$this->include_script(HMSFROMRC_SECTION . '.js');

		//Configuration reading
		$this->load_config();
		if ($this->rc->config->get('hmailserver_server_for_hmsrc', null) == null) {
			$this->load_config('config.inc.php.dist');
		}

		//localization loading
		$this->add_texts('localization/');

		$this->add_hook('preferences_list', array($this, 'preferences_list'));
		$this->add_hook('preferences_save', array($this, 'preferences_save'));
	}

	function preferences_list($p) {
		try {
			//You must set a block or the section will not be displayed
			if ($p['section'] == 'server') {
				$this->loadData();

				// ================================================================
				//Auto reply

				$p['blocks'][ARBLOCK]['name'] = $this->gettext('autoreply');

				$ctrl_id = 'ztp_ar_enabled';
				$ctrl = new html_checkbox(array('name' => '_ar_enabled', 'id' => $ctrl_id, 'value' => 1));
				$p['blocks'][ARBLOCK]['options']['ar_enabled'] = array(
					'title' => html::label($ctrl_id, Q($this->gettext('ar_enabled'))),
					'content' => $ctrl->show($this->ar_enabled)
				);

				$ctrl_id = 'ztp_ar_subject';
				$ctrl = new html_inputfield(array('type' => 'text', 'name' => '_ar_subject', 'id' => $ctrl_id));
				$p['blocks'][ARBLOCK]['options']['ar_subject'] = array(
					'title' => html::label($ctrl_id, Q($this->gettext('ar_subject'))),
					'content' => $ctrl->show($this->ar_subject)
				);

				$ctrl_id = 'ztp_ar_body';
				$ctrl = new html_textarea(array('name' => '_ar_body', 'id' => $ctrl_id, 'rows' => 5, 'cols' => 50));
				$p['blocks'][ARBLOCK]['options']['ar_body'] = array(
					'title' => html::label($ctrl_id, Q($this->gettext('ar_body'))),
					'content' => $ctrl->show($this->ar_body)
				);

				$ctrl_id = 'ztp_ar_ae_enabled';
				$ctrl = new html_checkbox(array('name' => '_ar_ae_enabled', 'id' => $ctrl_id, 'value' => 1));
				$ctrl2_id = 'ztp_ar_ae_date';
				$ctrl2 = new html_inputfield(array('name' => '_ar_ae_date', 'id' => $ctrl2_id));
				$p['blocks'][ARBLOCK]['options']['ar_ae_enabled'] = array(
					'title' => html::label($ctrl_id, Q($this->gettext('ar_ae_enabled'))),
					'content' => $ctrl->show($this->ar_ae_enabled) . " " . $ctrl2->show($this->ar_ae_date)
				);


				// ================================================================
				//Forwarder

				$p['blocks'][FWBLOCK]['name'] = $this->gettext('forwarder');

				$ctrl_id = 'ztp_fw_enabled';
				$ctrl = new html_checkbox(array('name' => '_fw_enabled', 'id' => $ctrl_id, 'value' => 1));
				$p['blocks'][FWBLOCK]['options']['fw_enabled'] = array(
					'title' => html::label($ctrl_id, Q($this->gettext('fw_enabled'))),
					'content' => $ctrl->show($this->fw_enabled)
				);

				$ctrl_id = 'ztp_fw_address';
				$ctrl = new html_inputfield(array('name' => '_fw_address', 'id' => $ctrl_id));
				$p['blocks'][FWBLOCK]['options']['fw_address'] = array(
					'title' => html::label($ctrl_id, Q($this->gettext('fw_address'))),
					'content' => $ctrl->show($this->fw_address)
				);

				$ctrl_id = 'ztp_fw_keeporiginal';
				$ctrl = new html_checkbox(array('name' => '_fw_keeporiginal', 'id' => $ctrl_id, 'value' => 1));
				$p['blocks'][FWBLOCK]['options']['fw_keeporiginal'] = array(
					'title' => html::label($ctrl_id, Q($this->gettext('fw_keeporiginal'))),
					'content' => $ctrl->show($this->fw_keeporiginal)
				);
			}
		} catch (Exception $e) {
			$p['abort'] = true;
			$p['result'] = false;
			$p['message'] = $e->getMessage();
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
				'ar_ae_date' => get_input_value('_ar_ae_date', RCUBE_INPUT_POST),
				'fw_enabled' => get_input_value('_fw_enabled', RCUBE_INPUT_POST),
				'fw_address' => get_input_value('_fw_address', RCUBE_INPUT_POST),
				'fw_keeporiginal' => get_input_value('_fw_keeporiginal', RCUBE_INPUT_POST),
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
		$dsn = $dbConf['Protocol'] . "://" . $dbConf['Username'] . ":" . $dbConf['Password'] . "@" . $dbConf['Server'] . "/" . $dbConf["Database"];
		$db = rcube_db::factory(
						$dsn, "", false);
		$db->db_connect('w');

		return $db;
	}

	function getHmsComServer($user) {
		$conf = $this->rc->config->get('hmailserver_server_for_hmsrc');
		$obBaseApp = new COM("hMailServer.Application", NULL, CP_UTF8);
		$obBaseApp->Connect();
		$obBaseApp->Authenticate($_SESSION['username'], $this->rc->decrypt($_SESSION['password']));
		$obDomain = $obBaseApp->Domains->ItemByName(substr($user, strpos($user, "@") + 1));
		return $obDomain->Accounts->ItemByAddress($user);
	}

	function saveData($args) {
		$conf = $this->rc->config->get('hmailserver_server_for_hmsrc');
		$userMail = $this->rc->user->get_username('mail');

		switch ($conf['Mode']) {
			case 'com':
				$this->saveDataByCom($userMail, $args);
				break;
			case 'sql':
				$this->saveDataBySql($userMail, $args);
				break;
			default:
				throw new Exception("hmsFromRC: Unknown Mode!");
				break;
		}
	}

	function saveDataByCom($user, $args) {
		$obAccount = $this->getHmsComServer($user);

		// Vacation Message
		if ($args['ar_enabled'] == "1") {
			$obAccount->VacationMessageIsOn = true;
			$obAccount->VacationSubject = $args['ar_subject'];
			$obAccount->VacationMessage = $args['ar_body'];

			if ($args['ar_ae_enabled'] == "1") {
				if (new DateTime() > new DateTime($args['ar_ae_date'])) {
					$obAccount->VacationMessageExpires = false;
					$alertPEBKAC = true;
				} else {
					$obAccount->VacationMessageExpires = true;
					$obAccount->VacationMessageExpiresDate = $args['ar_ae_date'];
				}
			} else {
				$obAccount->VacationMessageExpires = false;
			}
		} else {
			$obAccount->VacationMessageIsOn = false;
		}

		// Forwarding
		if ($args['fw_enabled'] == "1") {
			// This fails with internationalized domains. Also needs PHP >=5.2
			if (filter_var($args['fw_address'], FILTER_VALIDATE_EMAIL) === false) {
				throw new Exception($this->gettext('ar_fw_invalidaddress'));
			} else {
				$obAccount->ForwardEnabled = true;
				$obAccount->ForwardAddress = $args['fw_address'];
				$obAccount->ForwardKeepOriginal = ($args['fw_keeporiginal'] == 1);
			}
		} else {
			$obAccount->ForwardEnabled = false;
		}

		$obAccount->Save();

		if ($alertPEBKAC) {
			throw new Exception($this->gettext('ar_ae_warning'));
		}
	}

	function saveDataBySql($user, $args) {
		$db = $this->getHmsDb();

		$db->query(
				'update hm_accounts set accountvacationmessageon = ?, accountvacationsubject = ?, accountvacationmessage = ?, accountvacationexpires = ?, accountvacationexpiredate = ? where accountaddress = ?', array($args['ar_enabled'] == null ? 0 : $args['ar_enabled'], $args['ar_subject'], $args['ar_body'],
			$args['ar_ae_enabled'] == null ? 0 : 1, $args["ar_ae_date"],
			$user)
		);
	}

	function loadData() {
		$conf = $this->rc->config->get('hmailserver_server_for_hmsrc');
		$userMail = $this->rc->user->get_username('mail');

		switch ($conf['Mode']) {
			case 'com' :
				$this->loadDataByCom($userMail);
				break;
			case 'sql' :
				$this->loadDataBySql($userMail);
				break;
			default:
				throw new Exception("hmsFromRC: Unknown Mode!");
				break;
		}
	}

	function loadDataByCom($user) {
		$obAccount = $this->getHmsComServer($user);

		$this->ar_enabled = $obAccount->VacationMessageIsOn;
		$this->ar_subject = $obAccount->VacationSubject;
		$this->ar_body = $obAccount->VacationMessage;
		$this->ar_ae_enabled = $obAccount->VacationMessageExpires;
		$this->ar_ae_date = substr($obAccount->VacationMessageExpiresDate, 0, 10);

		$this->fw_enabled = $obAccount->ForwardEnabled;
		$this->fw_address = $obAccount->ForwardAddress;
		$this->fw_keeporiginal = $obAccount->ForwardKeepOriginal;
	}

	function loadDataBySql() {
		$db = $this->getHmsDb();

		$db->query(
				'select accountvacationmessageon, accountvacationsubject, accountvacationmessage, accountvacationexpires, accountvacationexpiredate from hm_accounts where accountaddress = ?', array($this->rc->user->get_username('mail'))
		);

		$r = $db->fetch_array();

		$this->ar_enabled = $r[0];
		$this->ar_subject = $r[1];
		$this->ar_body = $r[2];
		$this->ar_ae_enabled = $r[3];
		$this->ar_ae_date = substr($r[4], 0, 10);
	}

}

?>
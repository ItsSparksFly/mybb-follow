<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook("member_profile_end", "follow_profile");
$plugins->add_hook("misc_start", "follow_misc");
if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
	$plugins->add_hook("global_start", "follow_alerts");
}

function follow_info()
{
	global $lang;
	$lang->load('follow');
	
	return array(
		"name"			=> $lang->follow_name,
		"description"	=> $lang->follow_description,
		"website"		=> "https://github.com/itssparksfly",
		"author"		=> "sparks fly",
		"authorsite"	=> "https://sparks-fly.info",
		"version"		=> "1.0",
		"compatibility" => "18*"
	);
}

function follow_install()
{
    global $db, $cache;

    $db->query("CREATE TABLE ".TABLE_PREFIX."follow (
        `fid` int(11) NOT NULL AUTO_INCREMENT,
        `fromid` int(11) NOT NULL,
        `toid` int(11) NOT NULL,
        `name` varchar(155) NOT NULL,
        PRIMARY KEY (`fid`),
        KEY `lid` (`fid`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1");

     $setting_group = array(
        'name' => 'follow',
        'title' => $lang->follow_name,
        'description' => $lang->follow_description,
        'disporder' => 5, // The order your setting group will display
        'isdefault' => 0
    );

    $setting_array = array(
        'follow_username' => array(
            'title' => $lang->follow_username,
            'description' => $lang->follow_username_desc,
            'optionscode' => 'text',
            'value' => '', // Default
            'disporder' => 1
        ),
    );

    $gid = $db->insert_query("settinggroups", $setting_group);

    foreach($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }

    rebuild_settings();

     if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('follow_inplayquotes_new');
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('follow_inplaytracker_newthread'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);
        
        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('follow_inplaytracker_newreply'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
    }

    $member_profile_follow = [
        'title'        => 'member_profile_follow',
        'template'    => $db->escape_string(''),
        'sid'        => '-1',
        'version'    => '<a href="misc.php?action=follow&uid={$memprofile[\'uid\']}">{$lang->follow_follow}</a>',
        'dateline'    => TIME_NOW
    ];

    $member_profile_unfollow = [
        'title'        => 'member_profile_unfollow',
        'template'    => $db->escape_string(''),
        'sid'        => '-1',
        'version'    => '<a href="misc.php?action=unfollow&uid={$memprofile[\'uid\']}">{$lang->follow_unfollow}</a>',
        'dateline'    => TIME_NOW}
    ];

    $db->insert_query("templates", $member_profile_follow);
	$db->insert_query("templates", $member_profile_unfollow);

}

function follow_activate() {
    global $mybb, $db;

    include MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("member_profile", "#".preg_quote('{$formattedname}')."#i", '{$formattedname} {$follow_button}');
}

function follow_is_installed()
{
	global $db;
	if($db->table_exists("follow"))
	{
		return true;
	}

	return false;
}

function follow_uninstall()
{
	global $db, $cache;

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertTypeManager->deleteByCode('follow_inplaytracker_newthread');
		$alertTypeManager->deleteByCode('follow_inplaytracker_newreply');
        $alertTypeManager->deleteByCode('follow_inplayquotes_new');
	}

    $db->delete_query('settings', "name = 'follow_name'");
    $db->delete_query('settinggroups', "name = 'follow'");

    $db->query("DROP TABLE ".TABLE_PREFIX."follow");
    $db->delete_query("templates", "title IN ('member_profile_follow', 'member_profile_unfollow')");

}

function follow_deactivate() {
    global $db, $mybb;
    include MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("member_profile", "#".preg_quote('{$follow_button}')."#i", '', 0);
}

function follow_misc() {
    global $mybb, $db;

    if($mybb->input['action'] == "follow") {
        $toid = $mybb->get_input('uid');
        $fromid = $mybb->user['uid'];
        $name = $mybb->user['fid1'];

        $input_array = [
            "fromid" => (int)$fromid,
            "toid" => (int)$toid,
            "name" => $db->escape_string($name)
        ];

        $db->insert_query("follow", $input_array);
        redirect("member.php?action=profile&uid={$toid}");
    }

    if($mybb->input['action'] == "unfollow") {
        $toid = $mybb->get_input('uid');
        $fid = $mybb->settings['follow_name'];
        $name = $mybb->user['fid'.$fid];

        $db->delete_query("follow", "toid = '$toid' AND name = '$name'");
        redirect("member.php?action=profile&uid={$toid}");
    }
}

function follow_profile() {

    global $db, $lang, $templates, $mybb, $memprofile, $follow_button;
    $lang->load('follow');
    $follow_button = "";
    $fid = $mybb->settings['follow_name'];
    $name = $mybb->user['fid'.$fid];

    if($name == $memprofile['fid'.$fid]) {
        $follow_button = "";
    } else {
        $query = $db->simple_select("follow", "*", "name = '{$name}' AND toid='{$memprofile['uid']}'");
        $follow = $db->fetch_array($query);
        if($follow) {
            eval("\$follow_button = \"".$templates->get("member_profile_unfollow")."\";");
        } else {
            eval("\$follow_button = \"".$templates->get("member_profile_follow")."\";");
        }
    }
}

function follow_alerts() {

	global $mybb, $lang;
	$lang->load('follow');
	/**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_InplayquotesFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;
			$alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->follow_inplayquotes_new,
				$outputAlert['from_user'],
				$alertContent['username'],
	            $outputAlert['dateline']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->follow) {
	            $this->lang->load('follow');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
	        return $this->mybb->settings['bburl'] . '/misc.php?action=inplayquotes_overview&user=' . $alert->getObjectId();
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
			new MybbStuff_MyAlerts_Formatter_InplayquotesFormatter($mybb, $lang, 'follow_inplayquotes_new')
		);
	}

	/**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_InplaytrackerNewthreadFollowFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
            global $db;
            $alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->follow_inplaytracker_newthread,
                $outputAlert['from_user'],
                $alertContent['username'],
	            $outputAlert['dateline']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->follow) {
	            $this->lang->load('follow');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
	        return $this->mybb->settings['bburl'] . '/' . get_thread_link($alert->getObjectId());
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_InplaytrackerNewthreadFollowFormatter($mybb, $lang, 'follow_inplaytracker_newthread')
		);
    }
    
	/**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_InplaytrackerNewReplyFollowFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
            $alertContent = $alert->getExtraDetails();
	        return $this->lang->sprintf(
	            $this->lang->follow_inplaytracker_newreply,
                $outputAlert['from_user'],
                $alertContent['subject'],
	            $outputAlert['dateline']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->follow) {
	            $this->lang->load('follow');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
	        return $this->mybb->settings['bburl'] . '/' . get_thread_link($alert->getObjectId());
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_InplaytrackerNewReplyFollowFormatter($mybb, $lang, 'follow_inplaytracker_newreply')
		);
	}
}



?>
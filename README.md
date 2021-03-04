# Follow
Ermöglicht es Usern, anderen Charakteren zu folgen & somit via MyAlerts über ihre Inplay-Aktivität geupdatet zu werden. Dabei werden Alerts für neue Inplayszenen und -posts sowie neu eingereichte Inplayzitate erstellt. <b>Achtung</b>, nach der Installation müssen in den Plugin-Dateien von Inplayzitaten und Inplaytracker noch Änderungen vorgenommen werden, die weiter unten aufgeführt werden.

<b>Hinweise</b>
Der Alert wird immer an den Account geschickt, mit den initial gefolgt wurde. Daher ist es hilfreich, entweder <a href="https://github.com/katjalennartz/characterAlert">Character Alert</a> von risuena zu installieren oder <a href="https://storming-gates.de/showthread.php?tid=1000510">dieses Tutorial</a> (MyAlerts-Integration mit Accountswitcher) eingebaut zu haben.

# Datenbankänderungen

Neue Tabellen:
- follow

# Templateänderungen

Neue Templates:
- member_profile_follow
- member_profile_unfollow

In das Template member_profile wird die Variable {$follow_button} eingebaut - falls diese nicht automatisch eingesetzt werden konnte, muss sie von euch händisch ergänzt werden.

# Inplayquotes-Plugin mit Follow-Funktion verbinden

Über die Zeile
```
$insert_array = $db->insert_query("inplayquotes", $new_record);
```
Folgendes einfügen:
```
$query = $db->simple_select("follow", "fromid", "toid='$uid'");
while($follower = $db->fetch_array($query)) {
	if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		user = get_user($uid);
		$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('follow_inplayquotes_new');
		if ($alertType != NULL && $alertType->getEnabled()) {
			$alert = new MybbStuff_MyAlerts_Entity_Alert((int)$follower['fromid'], $alertType, (int)$uid);
			$alert->setExtraDetails([
				'username' => $user['username']
			]);
			MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
		}
	}	
}
```

# Inplaytracker-Plugin mit Follow-Funktion verbinden
Unter die Zeile
```
$db->update_query("threads", $new_record, "tid='{$tid}'");
```

Folgendes einfügen:
```
$query = $db->simple_select("follow", "fromid", "toid='$ownuid'");
$user = get_user($ownuid);
while($follower = $db->fetch_array($query)) {
	if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('follow_inplaytracker_newthread');
		if ($alertType != NULL && $alertType->getEnabled() && $ownuid != $follower['fromid']) {
			$alert = new MybbStuff_MyAlerts_Entity_Alert((int)$follower['fromid'], $alertType, (int)$tid);
			$alert->setExtraDetails([
				'username' => $user['username']
			]);
			MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
		}
	}
}
```

Unter die Zeile
```
foreach($partner_uids as $tag) {
```

Folgendes einfügen:
```
$query = $db->simple_select("follow", "fromid", "toid='$tag'");
$tuser = get_user($tag);
while($follower = $db->fetch_array($query)) {
	$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('follow_inplaytracker_newthread');
        if ($alertType != NULL && $alertType->getEnabled() && $ownuid != $partner_uid) {
        	$alert = new MybbStuff_MyAlerts_Entity_Alert((int)$follower['fromid'], $alertType, (int)$tid);
                $alert->setExtraDetails([
                	'username' => $tuser['username']
                ]);
        	MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
	}
}
```

Unter die Zeile 
```
$tags = explode(",", $partners);
```

Folgendes einfügen:
```
$query = $db->simple_select("follow", "fromid", "toid='$fromid'");
while($follower = $db->fetch_array($query)) {
	$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('follow_inplaytracker_newreply');
        if ($alertType != NULL && $alertType->getEnabled()) {
        $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$follower['fromid'], $alertType, (int)$thread['tid']);
        $alert->setExtraDetails([
        	'subject' => $thread['subject'],
                 'lastpost' => $last_post
        ]);
        MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
        }
}
```

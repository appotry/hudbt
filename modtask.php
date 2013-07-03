<?php
require "include/bittorrent.php";
dbconn();
require(get_langfile_path("",true));
loggedinorreturn();

function puke()
{
  global $CURUSER;
	$msg = "User ".$CURUSER["username"]." (id: ".$CURUSER["id"].") is hacking user's profile. IP : ".getip();
	write_log($msg,'mod');
	stderr("Error", "Permission denied. For security reason, we logged this action");
}

if (get_user_class() < $prfmanage_class)
	puke();

$action = $_POST["action"];
if ($action == "confirmuser")
{
	$userid = $_POST["userid"];
	$confirm = $_POST["confirm"];
	update_user($userid, 'status = ?, info = NULL', [$confirm]);
	header("Location: " . get_protocol_prefix() . "$BASEURL/unco.php?status=1");
	die;
}
if ($action == "edituser")
{
	$userid = $_POST["userid"];
	$class = 0 + $_POST["class"];
	$keeper_role = $_POST['keeper_role'];
	$vip_added = ($_POST["vip_added"] == 'yes' ? 'yes' : 'no');
	$vip_until = ($_POST["vip_until"] ? $_POST["vip_until"] : '0000-00-00 00:00:00');
	
	$warned = $_POST["warned"];
	$warnlength = 0 + $_POST["warnlength"];
	$warnpm = $_POST["warnpm"];
	$title = $_POST["title"];
	$avatar = $_POST["avatar"];
	$signature = $_POST["signature"];

	$enabled = $_POST["enabled"];
	$uploadpos = $_POST["uploadpos"];
	$downloadpos = $_POST["downloadpos"];
	$noad = $_POST["noad"];
	$noaduntil = $_POST["noaduntil"];
	$privacy = $_POST["privacy"];
	$forumpost = $_POST["forumpost"];
	$chpassword = $_POST["chpassword"];
	$passagain = $_POST["passagain"];
	
	$supportlang = $_POST["supportlang"];
	$support = $_POST["support"];
	$supportfor = $_POST["supportfor"];	
	
	$moviepicker = $_POST["moviepicker"];
	$pickfor = $_POST["pickfor"];
	$stafffor = $_POST["staffduties"];
	
	if (!is_valid_id($userid) || !is_valid_user_class($class))
		stderr("Error", "Bad user ID or class ID.");
	if (get_user_class() <= $class)
		stderr("Error", "You have no permission to change user's class to ".get_user_class_name($class,false,false,true).". BTW, how do you get here?");
	$res = sql_query("SELECT * FROM users WHERE id = ".sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
	$arr = _mysql_fetch_assoc($res) or puke();
	
	$curenabled = $arr["enabled"];
	$curparked = $arr["parked"];
	$curuploadpos = $arr["uploadpos"];
	$curdownloadpos = $arr["downloadpos"];
	$curforumpost = $arr["forumpost"];
	$curclass = $arr["class"];
	$curgroups = get_user_group($userid);
	$curwarned = $arr["warned"];
	
	$updateset[] = "stafffor = " . sqlesc($stafffor);
	$updateset[] = "pickfor = " . sqlesc($pickfor);
	$updateset[] = "picker = " . sqlesc($moviepicker);
	$updateset[] = "enabled = " . sqlesc($enabled);
	$updateset[] = "uploadpos = " . sqlesc($uploadpos);
	$updateset[] = "downloadpos = " . sqlesc($downloadpos);
	$updateset[] = "forumpost = " . sqlesc($forumpost);
	$updateset[] = "avatar = " . sqlesc($avatar);
	$updateset[] = "signature = " . sqlesc($signature);
	$updateset[] = "title = " . sqlesc($title);
	$updateset[] = "support = " . sqlesc($support);
	$updateset[] = "supportfor = " . sqlesc($supportfor);
	$updateset[] = "supportlang = ".sqlesc($supportlang);
	
	if(get_user_class()<=$cruprfmanage_class)
	{
		$modcomment = $arr["modcomment"];
	}
	if(get_user_class() >= $cruprfmanage_class)
	{
		$email = $_POST["email"];
		$username = $_POST["username"];
		$modcomment = $_POST["modcomment"];
		$downloaded = $_POST["downloaded"];
		$ori_downloaded = $_POST["ori_downloaded"];
		$uploaded = $_POST["uploaded"];
		$ori_uploaded = $_POST["ori_uploaded"];
		$bonus = $_POST["bonus"];
		$ori_bonus = $_POST["ori_bonus"];
		$invites = $_POST["invites"];
		if (($keeper_role!='none' && !isset($curgroups['keeper'])) || ($keeper_role != $curgroups['keeper']['role']) && isset($curgroups['keeper'])) {
		  switch($keeper_role){
		  case 'none':
		    $modcomment = date("Y-m-d") . " - Keeper role was removed by $CURUSER[username].\n". $modcomment;
		    $keeperRoleUpdate = "UPDATE users_usergroups SET removed_by = $CURUSER[id],removed_date = NOW() WHERE user_id = $userid AND usergroup_id = 1";
		    $msg = $lang_modtask_target[get_user_lang($userid)]['msg_your_keeper_role'].$lang_modtask_target[get_user_lang($userid)]['keeper_'.$curgroups['keeper']['role']] . $lang_modtask_target[get_user_lang($userid)]['msg_was_removed'] .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username'];
		    break;

		  case 'member':
		    if($curgroups['keeper']['role']=='boss'&&$curgroups['keeper']['removed']==NULL){
		      $keeperRoleUpdate = "UPDATE users_usergroups SET role = 'member',added_date = NOW(),added_by = $CURUSER[id] WHERE user_id = $userid AND usergroup_id = 1";
		      $modcomment = date("Y-m-d") . " - Demoted to Keeper Member from Keeper Boss by $CURUSER[username].\n". $modcomment;
		      $msg = $lang_modtask_target[get_user_lang($userid)]['msg_you_were_deomoted_to'].$lang_modtask_target[get_user_lang($userid)]['keeper_member'] .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username'];
		    }
		    else{
		      if($curgroups['keeper']['removed']!=NULL)
			$keeperRoleUpdate = "UPDATE users_usergroups SET role = 'member',added_date = NOW(),added_by = $CURUSER[id],removed_by = NULL,removed_date = NULL  WHERE user_id = $userid AND usergroup_id = 1";
		      else
			$keeperRoleUpdate = "INSERT INTO users_usergroups (user_id,usergroup_id,role,added_by,added_date) VALUES($userid,1,'member',$CURUSER[id],NOW())";
		      $modcomment = date("Y-m-d") . " - Promoted to Keeper Member by $CURUSER[username].\n". $modcomment;
		      $msg = $lang_modtask_target[get_user_lang($userid)]['msg_you_were_promoted_to'].$lang_modtask_target[get_user_lang($userid)]['keeper_member'] .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username'];
		    }
		    break;				
		    
		  case 'boss':
		    if($curgroups['keeper']['role']=='member'&&$curgroups['keeper']['removed']==NULL){
		      $keeperRoleUpdate = "UPDATE users_usergroups SET role = 'boss',added_date = NOW(),added_by = $CURUSER[id] WHERE user_id = $userid AND usergroup_id = 1";
		      $modcomment = date("Y-m-d") . " - Promoted to Keeper Boss from Keeper Member by $CURUSER[username].\n". $modcomment;
		    }
		    else{
		      if($curgroups['keeper']['removed']!=NULL)
			$keeperRoleUpdate = "UPDATE users_usergroups SET role = 'boss',added_date = NOW(),added_by = $CURUSER[id],removed_by = NULL,removed_date = NULL WHERE user_id = $userid AND usergroup_id = 1";
		      else
			$keeperRoleUpdate = "INSERT INTO users_usergroups (user_id,usergroup_id,role,added_by,added_date) VALUES($userid,1,'boss',$CURUSER[id],NOW())";
		      $modcomment = date("Y-m-d") . " - Promoted to Keeper Boss by $CURUSER[username].\n". $modcomment;
		    }
		    $msg = $lang_modtask_target[get_user_lang($userid)]['msg_you_were_promoted_to'].$lang_modtask_target[get_user_lang($userid)]['keeper_boss'] .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username'];
break;
		  }
		  $subject = $lang_modtask_target[get_user_lang($userid)]['msg_usergroup_change'];
		  send_pm(0, $userid, $subject, $msg);
		  sql_query($keeperRoleUpdate) or sqlerr(__FILE__, __LINE__);
		  
		}
		if ($arr['email'] != $email){
			$updateset[] = "email = " . sqlesc($email);
			$modcomment = date("Y-m-d") . " - Email changed from $arr[email] to $email by $CURUSER[username].\n". $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_email_change']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_email_changed_from'].$arr['email'].$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . $email .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username']);
			send_pm(0, $userid, $subject, $msg);
		}
		if ($arr['username'] != $username){
			$updateset[] = "username = " . sqlesc($username);
			$modcomment = date("Y-m-d") . " - Usernmae changed from $arr[username] to $username by $CURUSER[username].\n". $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_username_change']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_username_changed_from'].$arr['username'].$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . $username .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username']);
			send_pm(0, $userid, $subject, $msg);
			$Cache->delete_value('user_id_for_name_' . $arr['username']);
			$Cache->delete_value('user_id_for_name_' . $username);
		}
		if ($ori_downloaded != $downloaded){
			$updateset[] = "downloaded = " . sqlesc($downloaded);
			$modcomment = date("Y-m-d") . " - Downloaded amount changed from $arr[downloaded] to $downloaded by $CURUSER[username].\n". $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_downloaded_change']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_downloaded_changed_from'].mksize($arr['downloaded']).$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . mksize($downloaded) .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username']);
			send_pm(0, $userid, $subject, $msg);
		}
		if ($ori_uploaded != $uploaded){
			$updateset[] = "uploaded = " . sqlesc($uploaded);
			$modcomment = date("Y-m-d") . " - Uploaded amount changed from $arr[uploaded] to $uploaded by $CURUSER[username].\n". $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_uploaded_change']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_uploaded_changed_from'].mksize($arr['uploaded']).$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . mksize($uploaded) .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username']);
			send_pm(0, $userid, $subject, $msg);
		}
		if ($ori_bonus != $bonus){
			$updateset[] = "seedbonus = " . sqlesc($bonus);
			$modcomment = date("Y-m-d") . " - Bonus amount changed from $arr[seedbonus] to $bonus by $CURUSER[username].\n". $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_bonus_change']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_bonus_changed_from'].$arr['seedbonus'].$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . $bonus .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username']);
			send_pm(0, $userid, $subject, $msg);
		}
		if ($arr['invites'] != $invites){
			$updateset[] = "invites = " . sqlesc($invites);
			$modcomment = date("Y-m-d") . " - Invite amount changed from $arr[invites] to $invites by $CURUSER[username].\n". $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_invite_change']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_invite_changed_from'].$arr['invites'].$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . $invites .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username']);
			send_pm(0, $userid, $subject, $msg);
		}
	}
	if(get_user_class() == UC_STAFFLEADER)
	{
		$donor = $_POST["donor"];
		$donated = $_POST["donated"];
		$donated_cny = $_POST["donated_cny"];
		$this_donated_usd = $donated - $arr["donated"];
		$this_donated_cny = $donated_cny - $arr["donated_cny"];
		$memo = sqlesc(htmlspecialchars($_POST["donation_memo"]));
		
		if ($donated != $arr['donated'] || $donated_cny != $arr['donated_cny']) {
			$added = sqlesc(date("Y-m-d H:i:s"));
			sql_query("INSERT INTO funds (usd, cny, user, added, memo) VALUES ($this_donated_usd, $this_donated_cny, $userid, $added, $memo)") or sqlerr(__FILE__, __LINE__);
			$updateset[] = "donated = " . sqlesc($donated);
			$updateset[] = "donated_cny = " . sqlesc($donated_cny);
		}

		$updateset[] = "donor = " . sqlesc($donor);
	}
	
	if ($chpassword != "" && $chpassword !=  $arr['username'] && strlen($chpassword) <= 40 && strlen($chpassword) >= 6 AND $chpassword == $passagain) {
	    $sec = mksecret();
	    $passhash = md5($sec . $chpassword . $sec);
	    $updateset[] = "secret = " . sqlesc($sec);
	    $updateset[] = "passhash = " . sqlesc($passhash);
	}

	if ($curclass >= get_user_class())
		puke();

	if ($curclass != $class)
	{
		$what = ($class > $curclass ? $lang_modtask_target[get_user_lang($userid)]['msg_promoted'] : $lang_modtask_target[get_user_lang($userid)]['msg_demoted']);
		$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_class_change']);
		$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_you_have_been'].$what.$lang_modtask_target[get_user_lang($userid)]['msg_to'] . get_user_class_name($class) .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER['username']);
		send_pm(0, $userid, $subject, $msg);
		$updateset[] = "class = $class";
		$what = ($class > $curclass ? "Promoted" : "Demoted");
		$modcomment = date("Y-m-d") . " - $what to '" . get_user_class_name($class) . "' by $CURUSER[username].\n". $modcomment;
	}
	if ($class == UC_VIP)
	{
		$updateset[] = "vip_added = ".sqlesc($vip_added);
		if ($vip_added == 'yes')
			$updateset[] = "vip_until = ".sqlesc($vip_until);
		$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_your_vip_status_changed']);
		$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_vip_status_changed_by'].$CURUSER['username']);
		send_pm(0, $userid, $subject, $msg);
		$modcomment = date("Y-m-d") . " - VIP status changed by $CURUSER[username]. VIP added: ".$vip_added.($vip_added == 'yes' ? "; VIP until: ".$vip_until : "").".\n". $modcomment;
	}
	
	if ($warned && $curwarned != $warned)
	{
		$updateset[] = "warned = " . sqlesc($warned);
		$updateset[] = "warneduntil = '0000-00-00 00:00:00'";

		if ($warned == 'no')
		{
			$modcomment = date("Y-m-d") . " - Warning removed by $CURUSER[username].\n". $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_warn_removed']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_warning_removed_by'] . $CURUSER['username'] . ".");
		}

		send_pm(0, $userid, $subject, $msg);
	}
	elseif ($warnlength)
	{
		if ($warnlength == 255)
		{
			$modcomment = date("Y-m-d") . " - Warned by " . $CURUSER['username'] . ".\nReason: $warnpm.\n". $modcomment;
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_you_are_warned_by'].$CURUSER['username']."." . ($warnpm ? $lang_modtask_target[get_user_lang($userid)]['msg_reason'].$warnpm : ""));
			$updateset[] = "warneduntil = '0000-00-00 00:00:00'";
		}else{
			$warneduntil = date("Y-m-d H:i:s",(strtotime(date("Y-m-d H:i:s")) + $warnlength * 604800)); 
			$dur = $warnlength . $lang_modtask_target[get_user_lang($userid)]['msg_week'] . ($warnlength > 1 ? $lang_modtask_target[get_user_lang($userid)]['msg_s'] : "");
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_you_are_warned_for'].$dur.$lang_modtask_target[get_user_lang($userid)]['msg_by']  . $CURUSER['username'] . "." . ($warnpm ? $lang_modtask_target[get_user_lang($userid)]['msg_reason'].$warnpm : ""));
			$modcomment = date("Y-m-d") . " - Warned for $dur by " . $CURUSER['username'] .  ".\nReason: $warnpm.\n". $modcomment;
			$updateset[] = "warneduntil = '$warneduntil'";
		}
		$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_you_are_warned']);
		send_pm(0, $userid, $subject, $msg);
		$added = sqlesc(date("Y-m-d H:i:s"));
		$updateset[] = "warned = 'yes', timeswarned = timeswarned+1, lastwarned=$added, warnedby=$CURUSER[id]";
	}
	if ($enabled != $curenabled)
	{
		if ($enabled == 'yes') {
			$modcomment = date("Y-m-d") . " - Enabled by " . $CURUSER['username']. ".\n". $modcomment;
			if (get_single_value("users","class","WHERE id = ".sqlesc($userid)) == UC_PEASANT){
				$length = 30*86400; // warn users until 30 days
				$until = date("Y-m-d H:i:s",(strtotime(date("Y-m-d H:i:s")) + $length));
				update_user($userid, "enabled='yes', leechwarn='yes', leechwarnuntil=?", [$until]);
			}
			else{
			  update_user($userid, "enabled='yes', leechwarn='no'");
			}
		} else {
			$modcomment = date("Y-m-d") . " - Disabled by " . $CURUSER['username']. ".\n". $modcomment;		
		}
	}
	if ($arr['noad'] != $noad){
		$updateset[]='noad = '.sqlesc($noad);
		$modcomment = date("Y-m-d") . " - No Ad set to ".$noad." by ". $CURUSER['username']. ".\n". $modcomment;
	}
	if ($arr['noaduntil'] != $noaduntil){
		$updateset[]='noaduntil = '.sqlesc($noaduntil);
		$modcomment = date("Y-m-d") . " - No Ad Until set to ".$noaduntil." by ". $CURUSER['username']. ".\n". $modcomment;
	}
	if ($privacy == "low" OR $privacy == "normal" OR $privacy == "strong")
		$updateset[] = "privacy = " . sqlesc($privacy);
	
	if ($_POST["resetkey"] == "yes")
	{
		$newpasskey = md5($arr['username'].date("Y-m-d H:i:s").$arr['passhash']);
		$updateset[] = "passkey = ".sqlesc($newpasskey);
	}
	if ($forumpost != $curforumpost)
	{
		if ($forumpost == 'yes')
		{
			$modcomment = date("Y-m-d") . " - Posting enabled by " . $CURUSER['username'] . ".\n" . $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_posting_rights_restored']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_posting_rights_restored']. $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_you_can_post']);
			send_pm(0, $userid, $subject, $msg);
		}
		else
		{
			$modcomment = date("Y-m-d") . " - Posting disabled by " . $CURUSER['username'] . ".\n" . $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_posting_rights_removed']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_posting_rights_removed'] . $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_probable_reason']);
			send_pm(0, $userid, $subject, $msg);
		}
	}
	if ($uploadpos != $curuploadpos)
	{
		if ($uploadpos == 'yes')
		{
			$modcomment = date("Y-m-d") . " - Upload enabled by " . $CURUSER['username'] . ".\n" . $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_upload_rights_restored']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_upload_rights_restored'] . $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_you_upload_can_upload']);
			send_pm(0, $userid, $subject, $msg);
		}
		else
		{
			$modcomment = date("Y-m-d") . " - Upload disabled by " . $CURUSER['username'] . ".\n" . $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_upload_rights_removed']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_upload_rights_removed'] . $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_probably_reason_two']);
			send_pm(0, $userid, $subject, $msg);
		}
	}
	if ($downloadpos != $curdownloadpos)
	{
		if ($downloadpos == 'yes')
		{
			$modcomment = date("Y-m-d") . " - Download enabled by " . $CURUSER['username'] . ".\n" . $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_download_rights_restored']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_download_rights_restored']. $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_you_can_download']);
			send_pm(0, $userid, $subject, $msg);
		}
		else
		{
			$modcomment = date("Y-m-d") . " - Download disabled by " . $CURUSER['username'] . ".\n" . $modcomment;
			$subject = ($lang_modtask_target[get_user_lang($userid)]['msg_download_rights_removed']);
			$msg = ($lang_modtask_target[get_user_lang($userid)]['msg_your_download_rights_removed'] . $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_probably_reason_three']);
			send_pm(0, $userid, $subject, $msg);
		}
	} 
	
	$updateset[] = "modcomment = " . sqlesc($modcomment);
	
	update_user($userid, implode(", ", $updateset));
	$returnto = htmlspecialchars($_POST["returnto"]);
	header("Location: " . get_protocol_prefix() . "$BASEURL/$returnto");
	die;
}
puke();


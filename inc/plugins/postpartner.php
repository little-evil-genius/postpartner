<?php
//error_reporting ( -1 );
//ini_set ( 'display_errors', true );
// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// HOOKS
$plugins->add_hook('admin_config_settings_change', 'postpartner_settings_change');
$plugins->add_hook('admin_settings_print_peekers', 'postpartner_settings_peek');
$plugins->add_hook("misc_start", "postpartner_misc");
$plugins->add_hook("global_intermediate", "postpartner_global");
$plugins->add_hook("admin_user_users_delete_commit_end", "postpartner_user_delete");
$plugins->add_hook("fetch_wol_activity_end", "postpartner_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "postpartner_online_location"); 
// MyAlerts
if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
	$plugins->add_hook("global_start", "postpartner_alerts");
}

// Die Informationen, die im Pluginmanager angezeigt werden
function postpartner_info(){
	return array(
		"name"		=> "Postpartner Suche",
		"description"	=> "Mit diesem Plugin können User Postpartner-Suchen mit entsprechenden Szenenideen postet. Andere User können dann dann mit einem Klick ihr Interesse bekunden und der Suchende bekommt eine Meldung auf dem Index. Außerdem gibt es die Funktion von einem Postpartnerwürfel.",
		"website"	=> "https://github.com/little-evil-genius/postpartner",
		"author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "1.1",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function postpartner_install(){

    global $db, $cache, $mybb;

    // Datenbank-Tabelle Postpartners erstellen
	$db->query("CREATE TABLE ".TABLE_PREFIX."postpartners(
        `ppid` int(10) NOT NULL AUTO_INCREMENT,
        `uid` int(11) NOT NULL,
		`max_count` int(10) NOT NULL,
		`res_count` int(10) NOT NULL,
		`inplaydate` VARCHAR(500) NOT NULL,
		`searchdesc` VARCHAR(2500) NOT NULL,
        PRIMARY KEY(`ppid`),
        KEY `ppid` (`ppid`)
        )
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1    
    ");

    // Datenbank-Tabelle Postpartners Alerts erstellen
	$db->query("CREATE TABLE ".TABLE_PREFIX."postpartners_alerts(
        `paid` int(10) NOT NULL AUTO_INCREMENT,
        `ppid` int(11) NOT NULL,
		`searchUser` int(10) NOT NULL,
		`interestedUser` int(10) NOT NULL,
        PRIMARY KEY(`paid`),
        KEY `paid` (`paid`)
        )
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1    
    ");

    // EINSTELLUNGEN HINZUFÜGEN
    $setting_group = array(
        'name'          => 'postpartner',
        'title'         => 'Postpartnersuche',
        'description'   => 'Einstellungen für das Postpartnersuche',
        'disporder'     => 1,
        'isdefault'     => 0
    );
        
    $gid = $db->insert_query("settinggroups", $setting_group); 
        
    $setting_array = array(
        'postpartner_allow_groups' => array(
            'title' => 'Erlaubte Gruppen',
			'description' => 'Welche Gruppen dürfen neue Postpartnersuche veröffentlichen?',
			'optionscode' => 'groupselect',
			'value' => '4', // Default
			'disporder' => 1
        ),
		'postpartner_guest' => array(
			'title' => 'Gästeberechtigung',
			'description' => 'Dürfen Gäste die Postpartnersuche sehen?',
			'optionscode' => 'yesno',
			'value' => '2', // Default
			'disporder' => 2
		),
		'postpartner_guest_avatar' => array(
			'title' => 'Avatar verstecken',
			'description' => 'Dürfen Gäste die Avatare der Accounts sehen?',
			'optionscode' => 'yesno',
			'value' => '0', // Default
			'disporder' => 3
		),
		'postpartner_defaultavatar' => array(
			'title' => 'Standard-Avatar',
			'description' => 'Wie heißt die Bilddatei, für die Standard-Avatare? Damit der Avatar für jedes Design angepasst wird, sollte der Namen in allen Designs gleich sein.',
			'optionscode' => 'text',
			'value' => 'default_avatar.png', // Default
			'disporder' => 4
		),
		'postpartner_profilfeldsystem' => array(
			'title' => 'Profilfeldsystem',
			'description' => 'Werden klassische Profilfelder oder Steckbrieffelder von Risuenas Steckbrief-Plugin verwendet? Es kann auch ausgewählt werden, dass beides verwendet wird.',
			'optionscode' => 'select\n0=klassische Profilfelder\n1=Steckbrief-Plugin\n2=beide Varianten',
			'value' => '0', // Default
			'disporder' => 5
		),
		'postpartner_shortdesc' => array(
			'title' => 'Kurzbeschreibung',
			'description' => 'Wie lautet die FID/Identifikator von dem Profilfeld/Steckbrieffeld der Kurzbeschreibung? Wenn nicht gewünscht, dann einfach frei lassen.<br>
            <b>Hinweis:</b> Bei klassischen Profilfeldern eine Zahl eintragen. Bei dem Steckbrief-Plugin von Risuena muss der Name/Identifikator des Felds eingetragen werden.',
			'optionscode' => 'text',
			'value' => '2', // Default
			'disporder' => 6
		),
		'postpartner_postinglength' => array(
			'title' => 'Postinglänge',
			'description' => 'Wie lautet die FID/Identifikator von dem Profilfeld/Steckbrieffeld der Postinglänge? Wenn nicht gewünscht, dann einfach frei lassen.<br>
            <b>Hinweis:</b> Bei klassischen Profilfeldern eine Zahl eintragen. Bei dem Steckbrief-Plugin von Risuena muss der Name/Identifikator des Felds eingetragen werden.',
			'optionscode' => 'text',
			'value' => '5', // Default
			'disporder' => 7
		),
		'postpartner_postingfrequency' => array(
			'title' => 'Postingfrequenz',
			'description' => 'Wie lautet die FID/Identifikator von dem Profilfeld/Steckbrieffeld der Postingfrequenz? Wenn nicht gewünscht, dann einfach frei lassen.<br>
            <b>Hinweis:</b> Bei klassischen Profilfeldern eine Zahl eintragen. Bei dem Steckbrief-Plugin von Risuena muss der Name/Identifikator des Felds eingetragen werden.',
			'optionscode' => 'text',
			'value' => '6', // Default
			'disporder' => 8
		),
		'postpartner_postingperspective' => array(
			'title' => 'Postperspektive',
			'description' => 'Wie lautet die FID/Identifikator von dem Profilfeld/Steckbrieffeld der Postperspektive? Wenn nicht gewünscht, dann einfach frei lassen.<br>
            <b>Hinweis:</b> Bei klassischen Profilfeldern eine Zahl eintragen. Bei dem Steckbrief-Plugin von Risuena muss der Name/Identifikator des Felds eingetragen werden.',
			'optionscode' => 'text',
			'value' => '24', // Default
			'disporder' => 9
		),
		'postpartner_filteroption' => array(
			'title' => 'Filtermöglichkeit',
			'description' => 'Sollen die Postangebote filterbar sein nach den Profilfeldern Postinglänge, Postingfrequenz und Postperspektive?<br>
            <b>Hinweis:</b> Die Filtermöglichkeit wird nur angezeigt, wenn das jeweilige Feld ein feste Auswahlmöglichkeiten bietet.',
			'optionscode' => 'yesno',
			'value' => '1', // Default
			'disporder' => 10
		),
		'postpartner_dice' => array(
			'title' => 'Postpartnerwürfel',
			'description' => 'Sollen User die Möglichkeit haben sich aus den Angeboten eine zufällige Ausgabe anzuzeigen? Eine Art Würfel.',
			'optionscode' => 'yesno',
			'value' => '1', // Default
			'disporder' => 11
		),
        'postpartner_lists' => array(
            'title' => 'Listen PHP',
            'description' => 'Wie heißt die Hauptseite der Listen-Seite? Dies dient zur Ergänzung der Navigation. Falls nicht gewünscht einfach leer lassen.',
            'optionscode' => 'text',
            'value' => 'lists.php', // Default
            'disporder' => 12
        ),
		'postpartner_lists_type' => array(
			'title' => 'Listen Menü',
			'description' => 'Soll über die Variable {$lists_menu} das Menü der Listen aufgerufen werden?<br>Wenn ja, muss noch angegeben werden, ob eine eigene PHP-Datei oder das Automatische Listen-Plugin von sparks fly genutzt? ',
			'optionscode' => 'select\n0=eigene Listen/PHP-Datei\n1=Automatische Listen-Plugin\n2=keine Menü-Anzeige',
			'value' => '2', // Default
			'disporder' => 13
		),
        'postpartner_lists_menu' => array(
            'title' => 'Listen Menü Template',
            'description' => 'Damit das Listen Menü richtig angezeigt werden kann, muss hier einmal der Name von dem Tpl von dem Listen-Menü angegeben werden.',
            'optionscode' => 'text',
            'value' => 'lists_nav', // Default
            'disporder' => 14
        ),
        'postpartner_categorie' => array(
            'title' => 'Inplay-Kategorie',
            'description' => 'Wähle hier die Inplaykategorie aus.',
            'optionscode' => 'forumselectsingle',
            'value' => '10', // Default
            'disporder' => 15
        ),
    );
        
    foreach($setting_array as $name => $setting){
        $setting['name'] = $name;
        $setting['gid']  = $gid;
        $db->insert_query('settings', $setting);  
    }
    rebuild_settings();

	// Task hinzufügen
    $date = new DateTime(date("d.m.Y", strtotime('+1 day')));
    $postpartnerTask = array(
        'title' => 'Postpartnersuche',
        'description' => 'Löscht automatisch Postpartnersuchen, bei den Szenenkapazitäte ausgeschöpft ist.',
        'file' => 'postpartner',
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
        'nextrun' => $date->getTimestamp(),
        'logging' => 1,
        'locked' => 0
    );
    $db->insert_query('tasks', $postpartnerTask);

    $cache->update_tasks(); 

    // TEMPLATES ERSTELLEN
    // Template Gruppe für jedes Design erstellen
    $templategroup = array(
        "prefix" => "postpartner",
        "title" => $db->escape_string("Postpartnersuche"),
    );
    $db->insert_query("templategroups", $templategroup);

    $insert_array = array(
        'title'        => 'postpartner',
        'template'    => $db->escape_string('<html>
        <head>
           <title>{$mybb->settings[\'bbname\']} - {$lang->postpartner_navigation}</title>
           {$headerinclude}
        </head>
        <body>
           {$header}
           <table width="100%" border="0" align="center">
              <tr>
                 <td valign="top">
                    <div id="postpartner_lists">
                       {$lists_menu}
                       <div class="postpartner_lists-body">
                          <div class="postpartner_lists-headline">{$lang->postpartner_navigation}</div>
                          <div class="postpartner_lists-desc">{$lang->postpartner_overview_desc}</div>
                          {$postpartner_add}
                          {$postpartner_ownsearch}
                          <div class="postpartner_lists-headline">
							  {$counter_searchs}
                             {$postpartner_dice}
                           </div>
                           {$postpartner_dice_bit}
                           {$postpartner_filter}
                          {$postpartner_bit}	 
                           {$postpartner_none}
                       </div>
                    </div>
                 </td>
              </tr>
           </table>
           {$footer}
        </body>
     </html>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'postpartner_add',
        'template'    => $db->escape_string('<form method="post" action="misc.php?action=postpartner_add" id="postpartner_add">
        <div class="postpartner_add-headline">{$lang->postpartner_add_headline}</div>
        <div class="postpartner_add_table">
          <div class="postpartner_add_tableLeft">
             <div class="postpartner_add_tableCall">
                <div class="postpartner_add_tableCell">
                   <b>{$lang->postpartner_add_character}</b><br>
                   <span class="smalltext">{$lang->postpartner_add_character_desc}</span>
                </div>
                <div class="postpartner_add_tableCell">
                   <select name="character">
                      <option value="">{$lang->postpartner_add_character_select}</option>
                      {$accounts_select}
                   </select>
                </div>
             </div>
              
             <div class="postpartner_add_tableCall">
                <div class="postpartner_add_tableCell">
                   <b>{$lang->postpartner_add_maxcount}</b><br>
                   <span class="smalltext">{$lang->postpartner_add_maxcount_desc}</span>
                </div>
                <div class="postpartner_add_tableCell">
                   <input type="text" class="textbox" name="max_count" id="max_count" placeholder="4"> <br> 
                    <span class="smalltext">
                        <strong>{$lang->postpartner_add_maxcount_check}</strong> 
                        <input type="checkbox" class="checkbox" name="max_count" id="max_count" value="0"  style="vertical-align: sub;"> 
                    </span>
                </div>
             </div>
              
             <div class="postpartner_add_tableCall">
                <div class="postpartner_add_tableCell">
                   <b>{$lang->postpartner_add_inplay}</b><br>
                   <span class="smalltext">{$lang->postpartner_add_inplay_desc}</span>
                </div>
                <div class="postpartner_add_tableCell">
                    <input type="text" class="textbox" name="inplaydate" id="inplaydate" placeholder="{$lang->postpartner_add_inplay_value}" required="">  
                </div>
             </div>
              
          </div>
          <div class="postpartner_add_tableRight">
             <b>{$lang->postpartner_add_searchdesc}</b><br>
             <span class="smalltext">{$lang->postpartner_add_searchdesc_desc}</span><br>
             <textarea class="textarea" name="searchdesc" id="searchdesc" rows="5" cols="30" style="width: 95%"></textarea>
          </div>
       </div>
       <div style="text-align: center;margin-bottom: 10px;">
          <input type="hidden" name="action" value="postpartner_add">
          <input type="submit" value="{$lang->postpartner_add_button}" name="postpartner_add" class="button">    
       </div>
       </form>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'postpartner_character',
        'template'    => $db->escape_string('<div class="postpartner_search">
        <div class="postpartner_search-name">{$username}
            <span class="postpartner_search-count">{$scene_count}</span>
        </div>
        <div class="postpartner_search-box">
            <div class="postpartner_search-avatar">
                <img src="{$avatar_url}">
            </div>
            <div class="postpartner_search-infos">
                <div class="postpartner_search-postfacts">{$postfacts}</div>	
                <div class="postpartner_search-shortdesc">{$shortdesc}</div>	
            </div>	
        </div>
        <div class="postpartner_search-desc"><b>{$lang->postpartner_own_inplay} {$inplaydate}</b> • {$searchdesc}</div>	
        <div class="postpartner_search-options">
            {$options_links}
        </div>	
        </div>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'postpartner_dice',
        'template'    => $db->escape_string('<form id="chara_new" method="get" action="misc.php">
        <input type="hidden" name="action" value="postpartner" />
        <input type="hidden" name="randomDice" value="random" />
        <button type="submit">
            {$lang->postpartner_dice}
        </button>	
        </form>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'postpartner_dice_bit',
        'template'    => $db->escape_string('<div class="postpartner-dice">
        <div class="postpartner-dice-headline">{$lang->postpartner_dice_headline}</div>
        <div class="postpartner-dice-username">{$charactername}</div>
        <div class="postpartner-dice-options">
            <a href="misc.php?action=postpartner&amp;interestedUser={$dice[\'ppid\']}">{$postpartner_options_interested}</a>
            <a href="private.php?action=send&amp;uid={$dice[\'uid\']}">{$lang->postpartner_options_pn}</a>
        </div>
        </div>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'postpartner_edit',
        'template' => $db->escape_string('<html>
        <head>
      <title>{$mybb->settings[\'bbname\']} - {$postpartner_navigation_edit}</title>
      {$headerinclude}
      </head>
      <body>
      {$header}
      <table width="100%" border="0" align="center">
         <tr>
            <td valign="top">
               <div id="postpartner_lists">
                  {$lists_menu}
                  <div class="postpartner_lists-body">
                     <div class="postpartner_lists-headline">{$lang->postpartner_edit_headline}</div>
                       <form id="edit_postpartner" method="post" action="misc.php?action=postpartner_edit_do&ppid={$ppid}">
                       <div class="postpartner_edit_table">
      <div class="postpartner_edit_tableLeft">
         <div class="postpartner_edit_tableCall">
            <div class="postpartner_edit_tableCell">
               <b>{$lang->postpartner_add_character}</b>
            </div>
            <div class="postpartner_edit_tableCell">
               <div class="postpartner_edit_character">{$charactername}</div>
            </div>
         </div>
         <div class="postpartner_edit_tableCall">
            <div class="postpartner_edit_tableCell">
               <b>{$lang->postpartner_add_maxcount}</b><br>
               <span class="smalltext">{$lang->postpartner_add_maxcount_desc}<br>{$scene_count}
                   </span>
            </div>
            <div class="postpartner_edit_tableCell">
               <input type="text" class="textbox" name="max_count" id="max_count" value="{$max_count}"> <br>
                <span class="smalltext">
                    <strong>{$lang->postpartner_add_maxcount_check}</strong>
                    <input type="checkbox" class="checkbox" name="max_count" id="max_count" value="0" {$radio_checked} style="vertical-align: sub;">
                </span>
            </div>
         </div>
         <div class="postpartner_edit_tableCall">
            <div class="postpartner_edit_tableCell">
               <b>{$lang->postpartner_add_inplay}</b><br>
               <span class="smalltext">{$lang->postpartner_add_inplay_desc}</span>
            </div>
            <div class="postpartner_edit_tableCell">
                <input type="text" class="textbox" name="inplaydate" id="inplaydate" placeholder="{$lang->postpartner_add_inplay_value}" value="{$inplaydate}" required="">
            </div>
         </div>
      </div>
      <div class="postpartner_edit_tableRight">
         <b>{$lang->postpartner_add_searchdesc}</b><br>
         <span class="smalltext">{$lang->postpartner_add_searchdesc_desc}</span><br>
         <textarea class="textarea" name="searchdesc" id="searchdesc" rows="5" cols="30" style="width: 95%">{$searchdesc}</textarea>
      </div>
   </div>
   <div style="text-align: center;margin-bottom: 10px;">
                                 <input type="hidden" name="ppid" id="ppid" value="{$ppid}" class="textbox" />
                                 <input type="hidden" name="old_max" id="old_max" value="{$max_count}" class="textbox" />
                                 <input type="hidden" name="old_res" id="old_res" value="{$edit[\'res_count\']}" class="textbox" />
                                 <input type="hidden" name="uid" id="uid" value="{$edit[\'uid\']}" class="textbox" />
                                 <input type="submit" value="{$lang->postpartner_edit_button}" class="button" />
   </div>

   </form>
                  </div>
               </div>
            </td>
         </tr>
      </table>
      {$footer}
   </body>

   </html>'),
        'sid' => '-2',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'postpartner_filter',
        'template'    => $db->escape_string('<form id="chara_new" method="get" action="misc.php">
        <input type="hidden" name="action" value="postpartner" />
        <div class="postpartner-filter">
           <div class="postpartner-filter-headline">{$lang->postpartner_filter}</div>
           <div class="postpartner-filteroptions">
               {$filter_postinglength}
               {$filter_postingperspective}
               {$filter_postingfrequency}
           </div>
        </div>
        <center>
           <input type="submit" name="search_filter" value="{$lang->postpartner_filter_button}" id="submit" class="button">
        </center>
     </form>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'postpartner_header',
        'template'    => $db->escape_string('<div class="postpartner_alert">
        {$postpartner_headertext}<br>
        <a href="misc.php?action=postpartner&acceptRequest={$ppid}">
            {$lang->postpartner_header_accept}
        </a>
        &nbsp;
        {$rejectlink}
        </div>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'postpartner_header_rejectRequest',
        'template'    => $db->escape_string('<form action="misc.php?action=rejectRequest&paid={$paid}" method="post">   	
        <input type="hidden" name="paid" id="paid" value="{$paid}"/>  
        <input type="hidden" name="saveurl" value="{$saveurl}" />
        <div class="header_rejectRequest">
            <div class="thead">{$lang->postpartner_header_reject_headline}</div>
            <div class="postpartner_add_tableCall">
        <div class="postpartner_add_tableCell">
            <b>{$lang->postpartner_header_reject_pm}</b><br>
            <span class="smalltext">{$lang->postpartner_header_reject_pm_desc}</span>    
        </div>
              
        <div class="postpartner_add_tableCell">
            <textarea name="rejectreason" id="rejectreason"></textarea>
        </div>    
        </div>
            <center>
            <input type="submit" class="button" value="{$lang->postpartner_header_reject_button}">
            </center>
        </div>
        </form>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'postpartner_none',
        'template'    => $db->escape_string('<div style="text-align:center;margin:10px auto;">{$lang_postpartner_none}</div>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'postpartner_own',
        'template'    => $db->escape_string('<div class="postpartner_own-bit">
        <div class="postpartner_own-name">{$username}
            <span class="postpartner_own-count">{$scene_count}</span>
        </div>
        <div class="postpartner_own-search"><b>{$lang->postpartner_own_inplay} {$inplaydate}</b> • {$searchdesc}</div>
        <div class="postpartner_own-options">
            <a href="misc.php?action=postpartner&amp;deleteSearch={$ppid}">{$lang->postpartner_own_delete}</a> 
            <a href="misc.php?action=postpartner_edit&amp;ppid={$ppid}">{$lang->postpartner_own_edit}</a> 
            <a href="misc.php?action=postpartner&amp;plusScene={$ppid}">{$lang->postpartner_own_plusScene}</a></div>
            </div>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'postpartner_ownsearch',
        'template'    => $db->escape_string('<div class="postpartner_lists-subline">{$lang->postpartner_own_headline}</div>
        <div class="postpartner_own">
            {$postpartner_own}
            {$postpartner_own_none}    
        </div>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

    // STYLESHEET HINZUFÜGEN
    $css = array(
        'name' => 'postpartner.css',
        'tid' => 1,
        'attachedto' => '',
        "stylesheet" => '#postpartner_lists {
            width: 100%;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .postpartner_lists-body {
            width: 80%;
            box-sizing: border-box;
        }
        
        .postpartner_lists-headline {
            background: #0066a2 url(../../../images/thead.png) top left repeat-x;
            color: #ffffff;
            border-bottom: 1px solid #263c30;
            padding: 8px;
            -moz-border-radius-topleft: 6px;
            -moz-border-radius-topright: 6px;
            -webkit-border-top-left-radius: 6px;
            -webkit-border-top-right-radius: 6px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: nowrap;
        }
        
        .postpartner_lists-subline {
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            border-top: 1px solid #444;
            border-bottom: 1px solid #000;
            padding: 6px;
            font-size: 12px;
        }
        
        .postpartner_lists-desc {
            text-align: justify;
            line-height: 180%;
            padding: 20px 40px;
        }
        
        .postpartner_add-headline {
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            border-top: 1px solid #444;
            border-bottom: 1px solid #000;
            padding: 6px;
            font-size: 12px;
        }
        
        .postpartner_add_table {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: flex-start;
            padding: 10px;
        }
        
        .postpartner_add_tableLeft {
            width: 50%;
        }
        
        .postpartner_add_tableRight {
            width: 50%;
        }
        
        .postpartner_add_tableCall {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            flex-wrap: nowrap;
            gap: 10px;
            padding: 10px;
        }
        
        .postpartner_add_tableCell {
            width: 49%;
            text-align: justify;
        }
        
        .postpartner_own {
            display: flex;
            gap: 10px;
            padding: 5px 0;
            flex-wrap: wrap;
        }
        
        .postpartner_own-bit {
            width: 49.5%;
        }
        
        .postpartner_own-name {
            font-size: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .postpartner_own-count {
            /* float: right; */
            font-size: 12px;
        }
        
        .postpartner_own-search {
            text-align: justify;
            height: 100px;
            overflow: auto;
            padding-right: 5px;
            margin: 5px 0;
        }
        
        .postpartner_own-options {
            display: flex;
            justify-content: space-around;
        }
        
        .postpartner_search {
            box-sizing: border-box;
            padding: 10px;
        }
        
        .postpartner_search-name {
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            border-top: 1px solid #444;
            border-bottom: 1px solid #000;
            padding: 6px;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .postpartner_search-box {
            display: flex;
            margin: 10px 0;
            gap: 10px;
        }
        
        .postpartner_search-avatar {
            width: 120px;
        }
        
        .postpartner_search-avatar img {
            width: 120px;
        }
        
        .postpartner_search-infos {
            width: 100%;
        }
        
        .postpartner_search-postfacts {
            border-bottom: 1px solid #000;
            padding: 6px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .postpartner_search-shortdesc {
            text-align: justify;
            max-height: 100px;
            overflow: auto;
            padding-right: 5px;
        }
        
        .postpartner_search-options {
            display: flex;
            justify-content: space-around;
        }
        
        .postpartner-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            margin-bottom: 10px;
            align-content: flex-start;
        }
        
        .postpartner-filter-headline {
            width: 100%;
            text-align: left;
            box-sizing: border-box;
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            padding: 5px;
        }
        
        .postpartner-filteroptions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: flex-start;
        }
        
        .postpartner-dice {
            margin-bottom: 20px;
        }
        
        .postpartner-dice-headline {
            width: 100%;
            text-align: left;
            box-sizing: border-box;
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            padding: 5px;
        }
        
        .postpartner-dice-username {
            text-align: center;
            font-size: 25px;
            margin: 5px 0 10px 0;
        }
        
        .postpartner-dice-options {
            display: flex;
            justify-content: space-around;
        }
        
        .postpartner_edit-headline {
           background: #0f0f0f url(../../../images/tcat.png) repeat-x;
           color: #fff;
           border-top: 1px solid #444;
           border-bottom: 1px solid #000;
           padding: 6px;
           font-size: 12px;
        }
        
        .postpartner_edit_table {
           display: flex;
           gap: 10px;
           justify-content: center;
           align-items: flex-start;
           padding: 10px;
        }
        
        .postpartner_edit_tableLeft {
           width: 50%;
        }
        
        .postpartner_edit_tableRight {
           width: 50%;
        }
        
        .postpartner_edit_tableCall {
           display: flex;
           align-items: flex-start;
           justify-content: flex-start;
           flex-wrap: nowrap;
           gap: 10px;
           padding: 10px;
        }
        
        .postpartner_edit_tableCell {
           width: 49%;
        }
        
        .postpartner_edit_character {
            color: #333;
            padding: 3px;
            font-size: 13px;
            font-family: Tahoma, Verdana, Arial, Sans-Serif;
            margin-bottom: 5px;
            width: 98%;
        }
        
        .postpartner_alert {
            background: #FFF6BF;
            border: 1px solid #FFD324;
            text-align: center;
            padding: 5px 20px;
            margin-bottom: 15px;
            font-size: 11px;
            -moz-border-radius: 6px;
            -webkit-border-radius: 6px;
            border-radius: 6px;
        }
        
        .header_rejectRequest {
            width: 600px;
        }
        
        .header_rejectRequest textarea {
            width: 100%;
            height: 100px;
        }
        
        .header_rejectRequest input {
            margin-bottom: 10px;
        }',
        'cachefile' => $db->escape_string(str_replace('/', '', 'postpartner.css')),
        'lastmodified' => time()
    );
    
    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "postpartner.css"), "sid = '".$sid."'", 1);

    $tids = $db->simple_select("themes", "tid");
    while($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }

}
 
// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function postpartner_is_installed(){

    global $db, $cache, $mybb;
  
	if($db->table_exists("postpartners"))  {
		return true;
	}
	return false;
} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function postpartner_uninstall(){
	
    global $db, $cache;

    //DATENBANK LÖSCHEN
    if($db->table_exists("postpartners"))
    {
        $db->drop_table("postpartners");
    }
    if($db->table_exists("postpartners_alerts"))
    {
        $db->drop_table("postpartners_alerts");
    }
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'postpartner%'");
    $db->delete_query('settinggroups', "name = 'postpartner'");

    rebuild_settings();

    // TEMPLATGRUPPE LÖSCHEN
    $db->delete_query("templategroups", "prefix = 'postpartner'");

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title LIKE 'postpartner%'");

	// TASK LÖSCHEN
	$db->delete_query('tasks', "file='postpartner'");
	$cache->update_tasks();

	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

    // STYLESHEET ENTFERNEN
	$db->delete_query("themestylesheets", "name = 'postpartner.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	}
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function postpartner_activate(){

    global $db, $cache;
    
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    
    // VARIABLEN EINFÜGEN
	find_replace_templatesets('header', '#'.preg_quote('{$bbclosedwarning}').'#', '{$bbclosedwarning} {$postpartner_header}');

    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

        // Neue Suche eingetragen
        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('postpartner_new'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
    }
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function postpartner_deactivate(){

    global $db, $cache;

    require MYBB_ROOT."/inc/adminfunctions_templates.php";

    // VARIABLEN ENTFERNEN
    find_replace_templatesets("header", "#".preg_quote('{$postpartner_header}')."#i", '', 0);

    // MyALERT STUFF
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

        $alertTypeManager->deleteByCode('postpartner_new');
	}
}

#####################################
### THE BIG MAGIC - THE FUNCTIONS ###
#####################################

// ADMIN-CP PEEKER
function postpartner_settings_change(){
    
    global $db, $mybb, $postpartner_settings_peeker;

    $result = $db->simple_select('settinggroups', 'gid', "name='postpartner'", array("limit" => 1));
    $group = $db->fetch_array($result);
    $postpartner_settings_peeker = ($mybb->input['gid'] == $group['gid']) && ($mybb->request_method != 'post');
}

function postpartner_settings_peek(&$peekers){
    global $mybb, $postpartner_settings_peeker;

	if ($postpartner_settings_peeker) {
       $peekers[] = 'new Peeker($("#setting_postpartner_lists_type"), $("#row_setting_postpartner_lists_menu"), /^0/, false)';
    }
}

// POSTPARTNERSUCHE
function postpartner_misc(){

    global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer, $parser, $code_html, $postpartner_ownsearch, $postpartner_own, $filter_postingperspective, $postpartner_filter, $postpartner_bit;

    // EINSTELLUNGEN
	$allow_groups = $mybb->settings['postpartner_allow_groups'];
	$guest_setting = $mybb->settings['postpartner_guest'];
	$avatar_setting = $mybb->settings['postpartner_guest_avatar'];
	$defaultavatar_url = $mybb->settings['postpartner_defaultavatar'];
    $listsnav_setting = $mybb->settings['postpartner_lists']; 
    $liststype_setting = $mybb->settings['postpartner_lists_type']; 
	$listsmenu_setting = $mybb->settings['postpartner_lists_menu']; 
    $inplaycategorie = $mybb->settings['postpartner_categorie'];
    $postpartnerfilter = $mybb->settings['postpartner_filteroption'];
    $postpartner_dice = $mybb->settings['postpartner_dice'];
    $profilfeldsystem = $mybb->settings['postpartner_profilfeldsystem'];

    // PROFILFELDER 
    $shortdesc_setting = $mybb->settings['postpartner_shortdesc'];
	$postinglength_setting = $mybb->settings['postpartner_postinglength'];
	$postingfrequency_setting = $mybb->settings['postpartner_postingfrequency'];
	$postingperspective_setting = $mybb->settings['postpartner_postingperspective'];
    // Kurzbeschreibung:
    if (!empty($shortdesc_setting)) {
        // wenn Zahl => klassisches Profilfeld
        if (is_numeric($shortdesc_setting)) {
            $shortdescfid = "fid".$shortdesc_setting;
        } else {
            $shortdescfid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$shortdesc_setting."'"), "id");
        }
    }
    // Postinglänge:
    if (!empty($postinglength_setting)) {
        if (is_numeric($postinglength_setting)) {
            $postinglengthfid = "fid".$postinglength_setting;
        } else {
            $postinglengthfid = "";
        }
    }
    // Postingfrequenz:
    if (!empty($postingfrequency_setting)) {
        if (is_numeric($postingfrequency_setting)) {
            $postingfrequencyfid = "fid".$postingfrequency_setting;
        } else {
            $postingfrequencyfid = "";
        }
    }
    // Postperspektive:
    if (!empty($postingperspective_setting)) {
        if (is_numeric($postingperspective_setting)) {
            $postingperspectivefid = "fid".$postingperspective_setting;
        } else {
            $postingperspectivefid = "";
        }
    }

    // klassische Profilfelder
    if ($profilfeldsystem == 0) {
        $query_join = "LEFT JOIN ".TABLE_PREFIX."userfields uf ON uf.ufid = p.uid";
    } 
    // Katjas Steckbrief-Plugin
    else if ($profilfeldsystem == 1) {
        //ANFANG DES STRINGS BAUEN
        $selectstring = "LEFT JOIN (select um.uid as auid,";
        //FELDER DIE AKTIV SIND HOLEN
        $getfields = $db->simple_select("application_ucp_fields", "*", "active = 1");

        //DIE FELDER DURCHGEHEN
        while ($searchfield = $db->fetch_array($getfields)) {
            //weiter im Querie, hier modeln wir unsere Felder ders users (apllication_ucp_fields taballe) zu einer Tabellenreihe wie die FELDER um -> name der Spalte ist fieldname, wert wie gehabt value 
            $selectstring .= " max(case when um.fieldid ='{$searchfield['id']}' then um.value end) AS '{$searchfield['fieldname']}',";
        }

        $selectstring = substr($selectstring, 0, -1);
        $selectstring .= " from `" . TABLE_PREFIX . "application_ucp_userfields` as um group by uid) as fields ON auid = p.uid";

        $query_join = $selectstring;
    } 
    // beides
    else {
        //ANFANG DES STRINGS BAUEN
        $selectstring = "LEFT JOIN (select um.uid as auid,";
        //FELDER DIE AKTIV SIND HOLEN
        $getfields = $db->simple_select("application_ucp_fields", "*", "active = 1");

        //DIE FELDER DURCHGEHEN
        while ($searchfield = $db->fetch_array($getfields)) {
            //weiter im Querie, hier modeln wir unsere Felder ders users (apllication_ucp_fields taballe) zu einer Tabellenreihe wie die FELDER um -> name der Spalte ist fieldname, wert wie gehabt value 
            //SIEHE DAZU SCREEN DEN ICH DIR EBEN GEZEIGT
            $selectstring .= " max(case when um.fieldid ='{$searchfield['id']}' then um.value end) AS '{$searchfield['fieldname']}',";
        }

        $selectstring = substr($selectstring, 0, -1);
        $selectstring .= " from `" . TABLE_PREFIX . "application_ucp_userfields` as um group by uid) as fields ON auid = p.uid";

        $query_join = "LEFT JOIN ".TABLE_PREFIX."userfields uf ON uf.ufid = p.uid ".$selectstring;
    }

	// USER-ID
    $user_id = $mybb->user['uid'];
    $active_chara = $mybb->user['username'];

    // ACCOUNTSWITCHER
    // Haupt-UID
    $mainID = $db->fetch_field($db->simple_select("users", "as_uid", "uid = '".$user_id."'"), "as_uid");
    if(empty($mainID)) {
        $mainID = $user_id;
    }
    // Zusatzfunktion - CharakterUID-string
    $charas = postpartner_get_allchars($user_id);
    // hier den string bauen ich hänge hinten und vorne noch ein komma dran um so was wie 1 udn 100 abzufangen
    $charastring = ",".implode(",", array_keys($charas)).",";
   
    // SPRACHDATEI LADEN
    $lang->load("postpartner");
    
    // DAS ACTION MENÜ
	$mybb->input['action'] = $mybb->get_input('action');

    $filter_typen = array(
        "select",
        "radio",
        "multiselect",
        "select_multiple",
        "checkbox",
    );

    // DAS HTML UND CO ANGEZEIGT WIRD
    require_once MYBB_ROOT."inc/class_parser.php";;
    $parser = new postParser;
    $code_html = array(
        "allow_html" => 1,
        "allow_mycode" => 1,
        "allow_smilies" => 1,
        "allow_imgcode" => 1,
        "filter_badwords" => 0,
        "nl2br" => 1,
        "allow_videocode" => 0
    );

    // ÜBERSICHT
	if($mybb->input['action'] == "postpartner"){

		// Listenmenü
		if($liststype_setting != 2){
            // Jules Plugin
            if ($liststype_setting == 1) {
                $query_lists = $db->simple_select("lists", "*");
                while($list = $db->fetch_array($query_lists)) {
                    eval("\$menu_bit .= \"".$templates->get("lists_menu_bit")."\";");
                }
                eval("\$lists_menu = \"".$templates->get("lists_menu")."\";");
            } else {
                eval("\$lists_menu = \"".$templates->get($listsmenu_setting)."\";");
            }
        }

        // NAVIGATION
		if(!empty($listsnav_setting)){
            add_breadcrumb($lang->postpartner_navigation_lists, $listsnav_setting);
            add_breadcrumb($lang->postpartner_navigation, "misc.php?action=postpartner");
		} else{
            add_breadcrumb($lang->postpartner_navigation, "misc.php?action=postpartner");
		}

		// GÄSTE AUSSCHLIESSEN; WENN EINGESTELLT
		if ($guest_setting == 0 && $mybb->user['uid'] == 0) {
			error_no_permission();
			return;
		}

        // NEUE SUCHE AUFGEBEN
        // Nur den Gruppen, den es erlaubt ist, neue Suchen hinzuzufügen, können das Formular sehen
        if(is_member($allow_groups)) {
            // Account-Select
            $allcharacters_query = $db->query("SELECT uid,username FROM ".TABLE_PREFIX."users u
            WHERE (u.uid = '$mainID' OR u.as_uid = '$mainID')
            ORDER BY u.username ASC
            ");
            // CHARA ARRAY
            // uid => username
            $userids = [];
            while($uidlist = $db->fetch_array($allcharacters_query)) {
                $userids[$uidlist['uid']] = $uidlist['username'];
            }

            $accounts_select = ""; 
            foreach ($userids as $uid => $username) {
                $accounts_select .= "<option value='{$uid}'>{$username}</option>";
            }
            eval("\$postpartner_add = \"".$templates->get("postpartner_add")."\";");


            // DEINE SUCHEN
            $query_ownsearchs = $db->query("SELECT * FROM ".TABLE_PREFIX."postpartners p
            $query_join
            WHERE p.uid IN (SELECT uid FROM ".TABLE_PREFIX."users u WHERE u.uid = '$mainID' OR u.as_uid = '$mainID' AND $user_id != 0)
            ORDER BY (SELECT username FROM ".TABLE_PREFIX."users u WHERE u.uid = p.uid) ASC
            ");
            
            $lang_postpartner_none = $lang->postpartner_own_none;
            eval("\$postpartner_own_none = \"".$templates->get("postpartner_none")."\";");
            while ($ownsearchs = $db->fetch_array($query_ownsearchs)) {          
                $postpartner_own_none = "";

                // LEER LAUFEN LASSEN
                $ppid = "";
                $uid = "";
                $username = "";
                $scenesearch = "";
                $inplaydate = "";
                $max_count = "";
                $res_count = "";
          
                // MIT INFOS FÜLLEN
                $ppid = $ownsearchs['ppid'];
                $uid = $ownsearchs['uid'];
                $searchdesc = $parser->parse_message($ownsearchs['searchdesc'], $code_html);
                $inplaydate = $ownsearchs['inplaydate'];
                $max_count = $ownsearchs['max_count'];          
                $res_count = $ownsearchs['res_count'];

                // USER DATEN ZIEHEN
                $user['username'] = get_user($uid)['username'];            
                $username = build_profile_link($user['username'], $uid);

                // SZENENKAPAZITÄTEN
                if($max_count == 0) {
                    $scene_count = $lang->postpartner_scenecount_open;
                } else {
                    $scene_count = $lang->sprintf($lang->postpartner_scenecount, $res_count, $max_count);
                }

                eval("\$postpartner_own .= \"".$templates->get("postpartner_own")."\";");
            }
            
            eval("\$postpartner_ownsearch .= \"".$templates->get("postpartner_ownsearch")."\";");
        }

        // FILTER
        // Type Profilfeld Länge
        if (!empty($postinglength_setting)) {
            
            // wenn reine Zahl => klassisches Profilfeld
            if (is_numeric($postinglength_setting)) {
                $postinglengthType = $db->fetch_array($db->query("SELECT type FROM ".TABLE_PREFIX."profilefields WHERE fid = '".$postinglength_setting."'")); 
                $split_postinglength = explode("\n", $postinglengthType['type']);
                $postinglengthIf = $split_postinglength['0'];
            } 
            // wenn Wort => Katjas Steckbrief Plugin
            else {
                $postinglengthType = $db->fetch_field($db->query("SELECT fieldtyp FROM ".TABLE_PREFIX."application_ucp_fields WHERE fieldname = '".$postinglength_setting."'"), "fieldtyp"); 
                $postinglengthIf = $postinglengthType;
            }

        } else {
            $postinglengthIf = "";
        }

        // Type Profilfeld tempo
        if (!empty($postingfrequency_setting)) {
            
            // wenn reine Zahl => klassisches Profilfeld
            if (is_numeric($postingfrequency_setting)) {
                $postingfrequencyType = $db->fetch_array($db->query("SELECT type FROM ".TABLE_PREFIX."profilefields WHERE fid = '".$postingfrequency_setting."'")); 
                $split_postingfrequency = explode("\n", $postingfrequencyType['type']);
                $postingfrequencyIf = $split_postingfrequency['0'];
            } 
            // wenn Wort => Katjas Steckbrief Plugin
            else {
                $postingfrequencyType = $db->fetch_field($db->query("SELECT fieldtyp FROM ".TABLE_PREFIX."application_ucp_fields WHERE fieldname = '".$postingfrequency_setting."'"), "fieldtyp"); 
                $postingfrequencyIf = $postingfrequencyType;
            }
        
        } else {
            $postingfrequencyIf = "";
        }

        // Type Profilfeld Perspektive
        if (!empty($postingperspective_setting)) {
            
            // wenn reine Zahl => klassisches Profilfeld
            if (is_numeric($postingperspective_setting)) {
                $postingperspectiveType = $db->fetch_array($db->query("SELECT type FROM ".TABLE_PREFIX."profilefields WHERE fid = '".$postingperspective_setting."'")); 
                $split_postingperspective = explode("\n", $postingperspectiveType['type']);
                $postingperspectiveIf = $split_postingperspective['0'];
            } 
            // wenn Wort => Katjas Steckbrief Plugin
            else {
                $postingperspectiveType = $db->fetch_field($db->query("SELECT fieldtyp FROM ".TABLE_PREFIX."application_ucp_fields WHERE fieldname = '".$postingperspective_setting."'"), "fieldtyp"); 
                $postingperspectiveIf = $postingperspectiveType;
            }
        
        } else {
            $postingperspectiveIf = "";
        }

        // nur wenn Profilfelder Auswahl-Felder sind und Filter aktiviert
        if((in_array($postinglengthIf, $filter_typen) OR in_array($postingfrequencyIf, $filter_typen) OR in_array($postingperspectiveIf, $filter_typen)) AND $postpartnerfilter == 1){

            
            // QUERY KRAM
            // Filter Optionen
            $postinglength_filter = "%";
            $postingfrequency_filter = "%";
            $postingperspective_filter = "%";
            if($mybb->get_input('search_filter')) {
                $postinglength_filter = $mybb->get_input('filter_postinglength');
                $postingfrequency_filter = $mybb->get_input('filter_postingfrequency');
                $postingperspective_filter = $mybb->get_input('filter_postingperspective');
            }

            // Postinglänge => Überprüfen ob etwas angegeben
            if (!empty($postinglength_setting)) {
                // Überprüfen ob Auswahlmöglichkeit
                if(in_array($postinglengthIf, $filter_typen)) {
            
                    // wenn reine Zahl => klassisches Profilfeld
                    if (is_numeric($postinglength_setting)) {

                        $filteroptions_postinglength = "";
                        unset($split_postinglength['0']);
                        foreach ($split_postinglength as $lengthOption) {
                            $filteroptions_postinglength .= "<option value='".$lengthOption."'>".$lengthOption."</option>";
                        }
                        $filter_postinglength = "<select name=\"filter_postinglength\"><option value=\"%\">".$lang->postpartner_filter_postinglength."</option>".$filteroptions_postinglength."</select>";
                               
                        // Mehrfachauswahl
                        if ($postinglengthIf == "multiselect" OR $postinglengthIf == "checkbox") {
                            $postinglength_sql = "AND uf.".$postinglengthfid." like '%".$postinglength_filter."%'";
                        } else {
                            $postinglength_sql = "AND uf.".$postinglengthfid." like '".$postinglength_filter."'";
                        }

                    } 
                    // wenn Wort => Katjas Steckbrief Plugin
                    else {

                        $lengthOptions = $db->fetch_field($db->query("SELECT options FROM ".TABLE_PREFIX."application_ucp_fields WHERE fieldname = '".$postinglength_setting."'"), "options");
                        $lengthOptions_string = str_replace(", ", ",", $lengthOptions);
                        $split_postinglength = explode(",", $lengthOptions_string);
                        
                        $filteroptions_postinglength = "";
                        foreach ($split_postinglength as $lengthOption) {
                            $filteroptions_postinglength .= "<option value='".$lengthOption."'>".$lengthOption."</option>";
                        }

                        $filter_postinglength = "<select name=\"filter_postinglength\"><option value=\"%\">".$lang->postpartner_filter_postinglength."</option>".$filteroptions_postinglength."</select>";
                        
                        // Mehrfachauswahl
                        if ($postinglengthIf == "select_multiple" OR $postinglengthIf == "checkbox") {
                            $postinglength_sql = "AND IFNULL(fields.".$postinglength_setting.",'') like '%".$postinglength_filter."%'";
                        } else {
                            $postinglength_sql = "AND IFNULL(fields.".$postinglength_setting.",'') like '".$postinglength_filter."'";
                        }
                    }

                } else {
                    $filter_postinglength = "";
                    $postinglength_sql = "";
                }
            } else {
                $filter_postinglength = "";
                $postinglength_sql = "";
            }

            // Postingfrequenz => Überprüfen ob etwas angegeben
            if (!empty($postingfrequency_setting)) {
                // Überprüfen ob Auswahlmöglichkeit
                if(in_array($postingfrequencyIf, $filter_typen)) {
            
                    // wenn reine Zahl => klassisches Profilfeld
                    if (is_numeric($postingfrequency_setting)) {

                        $filteroptions_postingfrequency = "";
                        unset($split_postingfrequency['0']);
                        foreach ($split_postingfrequency as $frequencyOption) {
                            $filteroptions_postingfrequency .= "<option value='".$frequencyOption."'>".$frequencyOption."</option>";
                        }           
                        $filter_postingfrequency = "<select name=\"filter_postingfrequency\"><option value=\"%\">".$lang->postpartner_filter_postingfrequency."</option>".$filteroptions_postingfrequency."</select>";
        
                        // Mehrfachauswahl
                        if ($postingfrequencyIf == "multiselect" OR $postingfrequencyIf == "checkbox") {
                            $postingfrequency_sql = "AND uf.".$postingfrequencyfid." like '%".$postingfrequency_filter."%'";
                        } else {
                            $postingfrequency_sql = "AND uf.".$postingfrequencyfid." like '".$postingfrequency_filter."'";
                        }
                    } 
                    // wenn Wort => Katjas Steckbrief Plugin
                    else {

                        $frequencyOptions = $db->fetch_field($db->query("SELECT options FROM ".TABLE_PREFIX."application_ucp_fields WHERE fieldname = '".$postingfrequency_setting."'"), "options"); 
                        $frequencyOptions_string = str_replace(", ", ",", $frequencyOptions);
                        $split_postingfrequency = explode(",", $frequencyOptions_string);
                        
                        $filteroptions_postingfrequency = "";
                        foreach ($split_postingfrequency as $frequencyOption) {
                            $filteroptions_postingfrequency .= "<option value='".$frequencyOption."'>".$frequencyOption."</option>";
                        }

                        $filter_postingfrequency = "<select name=\"filter_postingfrequency\"><option value=\"%\">".$lang->postpartner_filter_postingfrequency."</option>".$filteroptions_postingfrequency."</select>";
                        
                        // Mehrfachauswahl
                        if ($postingfrequencyIf == "select_multiple" OR $postingfrequencyIf == "checkbox") {
                            $postingfrequency_sql = "AND IFNULL(fields.".$postingfrequency_setting.",'') like '%".$postingfrequency_filter."%'";
                        } else {
                            $postingfrequency_sql = "AND IFNULL(fields.".$postingfrequency_setting.",'') like '".$postingfrequency_filter."'";
                        }
                    }

                } else {
                    $filter_postingfrequency = "";
                    $postingfrequency_sql = "";
                }
            }  else {
                $filter_postingfrequency = "";
                $postingfrequency_sql = "";
            }
            
            // Postperspektive => Profilfeld FID angegeben
            if (!empty($postingperspective_setting)) {
    
                // Überprüfen ob Auswahlbox
                if(in_array($postingperspectiveIf, $filter_typen)) {
            
                    // wenn reine Zahl => klassisches Profilfeld
                    if (is_numeric($postingperspective_setting)) {

                        $filteroptions_postingperspective = "";
                        unset($split_postingperspective['0']);
                        foreach ($split_postingperspective as $perspectiveOption) {
                            $filteroptions_postingperspective .= "<option value='".$perspectiveOption."'>".$perspectiveOption."</option>";
                        }           
                        $filter_postingperspective = "<select name=\"filter_postingperspective\"><option value=\"%\">".$lang->postpartner_filter_postingperspective."</option>".$filteroptions_postingperspective."</select>";
        
                        // Mehrfachauswahl
                        if ($postingperspectiveIf == "multiselect" OR $postingperspectiveIf == "checkbox") {
                            $postingperspective_sql = "AND uf.".$postingperspectivefid." like '%".$postingperspective_filter."%'";
                        } else {
                            $postingperspective_sql = "AND uf.".$postingperspectivefid." like '".$postingperspective_filter."'";
                        }
                    } 
                    // wenn Wort => Katjas Steckbrief Plugin
                    else {

                        $perspectiveOptions = $db->fetch_field($db->query("SELECT options FROM ".TABLE_PREFIX."application_ucp_fields WHERE fieldname = '".$postingperspective_setting."'"), "options");
                        $perspectiveOptions_string = str_replace(", ", ",", $perspectiveOptions);
                        $split_postingperspective = explode(",", $perspectiveOptions_string);
                        
                        $filteroptions_postingperspective = "";
                        foreach ($split_postingperspective as $perspectiveOption) {
                            $filteroptions_postingperspective .= "<option value='".$perspectiveOption."'>".$perspectiveOption."</option>";
                        }

                        $filter_postingperspective = "<select name=\"filter_postingperspective\"><option value=\"%\">".$lang->postpartner_filter_postingperspective."</option>".$filteroptions_postingperspective."</select>";
                        
                        // Mehrfachauswahl
                        if ($postingperspectiveIf == "select_multiple" OR $postingperspectiveIf == "checkbox") {
                            $postingperspective_sql = "AND IFNULL(fields.".$postingperspective_setting.",'') like '%".$postingperspective_filter."%'";
                        } else {
                            $postingperspective_sql = "AND IFNULL(fields.".$postingperspective_setting.",'') like '".$postingperspective_filter."'";
                        }
                    }

                } else {
                    $filter_postingperspective = "";
                    $postingperspective_sql = "";
                }
            } else {
                $filter_postingperspective = "";
                $postingperspective_sql = "";
            }

            // TPL FÜR DEN FILTER
            eval("\$postpartner_filter .= \"".$templates->get("postpartner_filter")."\";");

        } else {
            $postpartner_filter = "";
        }

        // SZENENANGEBOTE
        $query_searchs = $db->query("SELECT * FROM ".TABLE_PREFIX."postpartners p 
        $query_join
        WHERE p.uid NOT IN (SELECT uid FROM ".TABLE_PREFIX."users u WHERE u.uid = '$mainID' OR u.as_uid = '$mainID' AND $user_id != 0) 
        AND p.ppid NOT IN (SELECT ppid FROM ".TABLE_PREFIX."postpartners_alerts pa WHERE p.ppid = pa.ppid AND pa.interestedUser = '$user_id')           
        AND p.uid NOT IN (SELECT interestedUser FROM ".TABLE_PREFIX."postpartners_alerts pa WHERE pa.searchUser = '$user_id')
        $postinglength_sql
        $postingfrequency_sql
        $postingperspective_sql
        ORDER BY (SELECT username FROM ".TABLE_PREFIX."users u WHERE u.uid = p.uid) ASC
        ");

        $searchs_count = 0;
        $counter_searchs = $lang->sprintf($lang->postpartner_overview_searchs_count_plural, $searchs_count);

        $lang_postpartner_none = $lang->postpartner_searchs_none;
        eval("\$postpartner_none = \"".$templates->get("postpartner_none")."\";");
        while ($searchs = $db->fetch_array($query_searchs)) {
            $postpartner_none = "";
            $searchs_count ++;	
            if ($searchs_count > 1) {
                $counter_searchs = $lang->sprintf($lang->postpartner_overview_searchs_count_plural, $searchs_count);
            } else {
                $counter_searchs = $lang->sprintf($lang->postpartner_overview_searchs_count_singular, $searchs_count);
            }

            // LEER LAUFEN LASSEN
            $ppid = "";
            $uid = "";
            $username = "";
            $shortdesc = "";
            $postinglength = "";
            $postingfrequency = "";
            $postingperspective = "";
            $scenesearch = "";
            $inplaydate = "";
            $max_count = "";
            $res_count = "";
            $postfacts = "";

            // MIT INFOS FÜLLEN
            $ppid = $searchs['ppid'];
            $uid = $searchs['uid'];
            if(!empty($shortdesc_setting)){
                // klassisches Profilfeld
                if (is_numeric($shortdesc_setting)) {
                    $shortdesc = $searchs[$shortdescfid];
                } 
                // Steckbrief-Plugin
                else {
                    $shortdesc = $searchs[$shortdesc_setting];
                }
            }
            $searchdesc = $parser->parse_message($searchs['searchdesc'], $code_html);
            $inplaydate = $searchs['inplaydate'];
            $max_count = $searchs['max_count'];
            $res_count = $searchs['res_count'];

            // USER DATEN ZIEHEN
            $userDB = get_user($uid);
            $profilelink = format_name($userDB['username'], $userDB['usergroup'], $userDB['displaygroup']);
            $username = build_profile_link($profilelink, $uid); 

            // SZENENKAPAZITÄTEN
            if($max_count == 0) {
                $scene_count = $lang->postpartner_scenecount_open;
            } else {
                $scene_count = $lang->sprintf($lang->postpartner_scenecount, $res_count, $max_count);
            }

            // POSTING VORLIEBEN
            if(!empty($postinglength_setting)){
                // klassisches Profilfeld
                if (is_numeric($postinglength_setting)) {
                    $postinglength = $searchs[$postinglengthfid];
                } 
                // Steckbrief-Plugin
                else {
                    $postinglength = $searchs[$postinglength_setting];
                }
            }
            if(!empty($postingfrequency_setting)){
                // klassisches Profilfeld
                if (is_numeric($postingfrequency_setting)) {
                    $postingfrequency = $searchs[$postingfrequencyfid];
                } 
                // Steckbrief-Plugin
                else {
                    $postingfrequency = $searchs[$postingfrequency_setting];
                }
            }
            if(!empty($postingperspective_setting)){
                // klassisches Profilfeld
                if (is_numeric($postingperspective_setting)) {
                    $postingperspective = $searchs[$postingperspectivefid];
                } 
                // Steckbrief-Plugin
                else {
                    $postingperspective = $searchs[$postingperspective_setting];
                }
            }

            $postfacts_string = $postinglength.";".$postingfrequency.";".$postingperspective;
            // Zu einem Array umwandeln
            $postfacts_array = explode(";", $postfacts_string);
            // Leere Elemente entfernen
            $postfacts_array = array_filter($postfacts_array);
            // In String umwandeln
            $postfacts = implode(" &bull; ", $postfacts_array);

            // AVATARE
            // Einstellung für Gäste Avatare ausblenden
            if ($avatar_setting == 1){
                // Gäste und kein Avatar - Standard-Avatar
                if ($mybb->user['uid'] == '0' || $userDB['avatar'] == '') {
                    $avatar_url  = $theme['imgdir']."/".$defaultavatar_url;
                } else {
                    $avatar_url  = $userDB['avatar'];
                }
            } else {
                // kein Avatar - Standard-Avatar
                if ($userDB['avatar'] == '') {
                    $avatar_url  = $theme['imgdir']."/".$defaultavatar_url;
                } else {
                    $avatar_url  = $userDB['avatar'];
                }
            }

            // OPTIONEN 
            if ($user_id == 0) {
                $options_links = "";
            } else {
                $options_links = "<a href=\"misc.php?action=postpartner&amp;interestedUser=".$ppid."\">".$lang->sprintf($lang->postpartner_options_interested, $active_chara)."</a>
                <a href=\"private.php?action=send&uid=".$uid."\">".$lang->postpartner_options_pn."</a>";
            }
            
            eval("\$postpartner_bit .= \"".$templates->get("postpartner_character")."\";");
        }

        // RANDOM POSTPARTNER AUSWÜRFELN
        if ($postpartner_dice == 1 AND $user_id != 0) {
            // WÜRFEL
            eval("\$postpartner_dice = \"".$templates->get("postpartner_dice")."\";");

            // AUSGABE
            if ($mybb->get_input('randomDice')) {

                $dicequery = $db->query("SELECT * FROM ".TABLE_PREFIX."postpartners p
                $query_join
                LEFT JOIN ".TABLE_PREFIX."users u
                ON u.uid = p.uid
                WHERE p.uid NOT IN (SELECT uid FROM ".TABLE_PREFIX."users u WHERE u.uid = '$mainID' OR u.as_uid = '$mainID')
                AND ppid NOT IN (SELECT ppid FROM ".TABLE_PREFIX."postpartners_alerts pa WHERE pa.ppid = p.ppid AND pa.searchUser NOT IN (SELECT uid FROM ".TABLE_PREFIX."users u WHERE u.uid = '$mainID' OR u.as_uid = '$mainID'))
                ORDER BY rand()
                LIMIT 1");
    
                $dice = $db->fetch_array($dicequery);
                $charactername = build_profile_link($dice['username'], $dice['uid']);
                $owncharacter = get_user($user_id)['username'];

                $postpartner_options_interested = $lang->sprintf($lang->postpartner_options_interested, $owncharacter);

                eval("\$postpartner_dice_bit = \"".$templates->get("postpartner_dice_bit")."\";");
            } else {
                $postpartner_dice_bit = "";   
            }

        } else {
            $postpartner_dice = "";
        }

        // OPTIONEN
        // Suche löschen
        $deleteSearch = $mybb->get_input('deleteSearch'); 
        if ($deleteSearch) {
        
            $check = $db->fetch_array($db->query("SELECT uid FROM ".TABLE_PREFIX."postpartners WHERE ppid = '".$deleteSearch."'"));
            $pos = strpos($charastring, ",".$check['uid'].",");

            if($pos !== false) {
                $charactername = get_user($check['uid'])['username'];

                $db->delete_query("postpartners", "ppid = '".$deleteSearch."'");

                $db->delete_query("postpartners_alerts", "ppid = '".$deleteSearch."'");

                redirect("misc.php?action=postpartner", $lang->sprintf($lang->postpartner_redirect_deleteSearch, $charactername));
            } else {
                redirect("misc.php?action=postpartner", $lang->postpartner_redirect_deleteSearch_error);
            }

        }

        // Szene wurde erstellt
        $plusScene = $mybb->get_input('plusScene'); 
        if ($plusScene) {
        
            $check = $db->fetch_array($db->query("SELECT uid, max_count FROM ".TABLE_PREFIX."postpartners WHERE ppid = '".$plusScene."'"));
            $pos = strpos($charastring, ",".$check['uid'].",");

            if($pos !== false AND $check['max_count'] != 0) {

                $db->query("UPDATE ".TABLE_PREFIX."postpartners 
                SET res_count = res_count - 1 
                WHERE ppid = '".$plusScene."'
                ");
                $charactername = get_user($check['uid'])['username'];
                redirect("misc.php?action=postpartner", $lang->sprintf($lang->postpartner_redirect_plusScene, $charactername));

            } else {
                redirect("misc.php?action=postpartner", $lang->postpartner_redirect_plusScene_error);

            }

        }

        // Interesse bekunden
        $interestedUser = $mybb->get_input('interestedUser'); 
        if ($interestedUser AND $user_id != 0) {

            $new_ppAlert = array(
                "ppid" => (int)$interestedUser,
                "searchUser" => (int)$db->fetch_field($db->query("SELECT uid FROM ".TABLE_PREFIX."postpartners WHERE ppid = '".$interestedUser."'"), "uid"),
                "interestedUser" => (int)$user_id,
            );  
    
            $db->insert_query("postpartners_alerts", $new_ppAlert);
            redirect("misc.php?action=postpartner", $lang->postpartner_redirect_interestedUser);

        }

        // Szenen Interesse annehmen - Weiterleitung Inplaybereich
        $acceptRequest = $mybb->get_input('acceptRequest'); 
        if ($acceptRequest) {
    
            $check_search = $db->fetch_array($db->query("SELECT uid, max_count FROM ".TABLE_PREFIX."postpartners WHERE ppid = '".$acceptRequest."'")); 
            $pos = strpos($charastring, ",".$check_search['uid'].",");

            $interestedUser = $db->fetch_field($db->query("SELECT interestedUser FROM ".TABLE_PREFIX."postpartners_alerts WHERE ppid = '".$acceptRequest."'"), "interestedUser");
            $check_intrested = $db->fetch_array($db->query("SELECT ppid, max_count FROM ".TABLE_PREFIX."postpartners WHERE uid = '".$interestedUser."'")); 
 
            if($pos !== false) {
    
                // Szenenkapazität updaten - eigene
                if($check_search['max_count'] != 0) {
                    $db->query("UPDATE ".TABLE_PREFIX."postpartners 
                    SET res_count = res_count - 1 
                    WHERE ppid = '".$acceptRequest."'
                    ");
                }

                // Szenenkapazität updaten - Anfrager
                if($check_intrested['max_count'] != 0) {
                    $db->query("UPDATE ".TABLE_PREFIX."postpartners 
                    SET res_count = res_count - 1 
                    WHERE ppid = '".$check_intrested['ppid']."'
                    ");
                }
 
                // Alert löschen 
                $db->delete_query('postpartners_alerts', "ppid = ".$acceptRequest."");

                $interestedName = get_user($interestedUser)['username'];

                redirect("forumdisplay.php?fid=".$inplaycategorie, $lang->sprintf($lang->postpartner_redirect_acceptRequest, $interestedName));
            } else {
                redirect("index.php", $lang->postpartner_redirect_acceptRequest_error);
            }
        }

		// TEMPLATE FÜR DIE SEITE
		eval("\$page = \"".$templates->get("postpartner")."\";");
		output_page($page);
		die();
    }

    // NEUE SUCHE HINZUFÜGEN
    if($mybb->input['action'] == "postpartner_add") {

        $charactername = get_user($mybb->get_input('character'))['username'];

        $new_postpartner = array(
            "uid" => (int)$db->escape_string($mybb->get_input('character')),
            "max_count" => (int)$mybb->get_input('max_count', MyBB::INPUT_INT),
            "res_count" => (int)$mybb->get_input('max_count', MyBB::INPUT_INT),
            "inplaydate" => $db->escape_string($mybb->get_input('inplaydate')),
            "searchdesc" => $db->escape_string($mybb->get_input('searchdesc'))
        );  
        		
        // MyALERTS STUFF		
        if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
			
            // Alle Hauptaccounts
            $user_query = $db->simple_select("users", "uid", "as_uid = '0' AND uid != '".$mainID."'");
            $alluids = "";
            while ($user = $db->fetch_array($user_query)) {
                $alluids .= $user['uid'].",";			
            }

            // letztes Komma vom UID-String entfernen			
            $alluids_string = substr($alluids, 0, -1);

            // UIDs in Array für Foreach
            $alluids_array = explode(",", $alluids_string);
			
            // Foreach um die einzelnen Partners durchzugehen
            foreach ($alluids_array as $user_id) {
                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('postpartner_new');
                if ($alertType != NULL && $alertType->getEnabled()) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$user_id, $alertType);
                    $alert->setExtraDetails([
                        'username' => $charactername,
                        'from' => $mybb->get_input('character')
                    ]);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                }
            }	
        }

        $db->insert_query("postpartners", $new_postpartner);

        redirect("misc.php?action=postpartner", $lang->sprintf($lang->postpartner_redirect_add, $charactername));
    }

    // SUCHE BEARBEITEN
    if($mybb->input['action'] == "postpartner_edit") {

        $ppid =  $mybb->get_input('ppid', MyBB::INPUT_INT);

        $edit_query = $db->query("SELECT * FROM ".TABLE_PREFIX."postpartners
        WHERE ppid = '".$ppid."'     
        ");
     
        $edit = $db->fetch_array($edit_query);

		// Listenmenü
		if($liststype_setting != 2){
            // Jules Plugin
            if ($liststype_setting == 1) {
                $query_lists = $db->simple_select("lists", "*");
                while($list = $db->fetch_array($query_lists)) {
                    eval("\$menu_bit .= \"".$templates->get("lists_menu_bit")."\";");
                }
                eval("\$lists_menu = \"".$templates->get("lists_menu")."\";");
            } else {
                eval("\$lists_menu = \"".$templates->get($listsmenu_setting)."\";");
            }
        }

        // GÄSTE UND NICHT ERSTELLER => ERROR
        $pos = strpos($charastring, ",".$edit['uid'].",");
        if($mybb->user['uid'] == 0 OR $pos === false) {
            error_no_permission();
        } 

        // LEER LAUFEN LASSEN
        $max_count = "";
        $res_count = "";
        $inplaydate = "";
        $searchdesc = "";
        $charactername = "";

        // MIT INFOS FÜLLEN
        $max_count = $edit['max_count'];
        $inplaydate = $edit['inplaydate'];
        $searchdesc = $edit['searchdesc'];
        $charactername = get_user($edit['uid'])['username'];

        if ($max_count == 0) {
            $max_count = "";
            $radio_checked = "checked=\"checked\"";
            $scene_count = "";
        } else {
            $max_count = $max_count;
            $radio_checked = "";
            $res_count = $max_count - $edit['res_count'];

            // Singular
            if ($res_count == 1){
                $scene_count = $lang->sprintf($lang->postpartner_scenecount_edit_singular, $res_count);
            } else {
                // Plural
                $scene_count = $lang->sprintf($lang->postpartner_scenecount_edit_singular, $res_count);
            }
        }

        $postpartner_navigation_edit = $lang->sprintf($lang->postpartner_navigation_edit, $charactername);

        // NAVIGATION
		if(!empty($listsnav_setting)){
            add_breadcrumb($lang->postpartner_navigation_lists, $listsnav_setting);
            add_breadcrumb($lang->postpartner_navigation, "misc.php?action=postpartner");
            add_breadcrumb($lang->sprintf($lang->postpartner_navigation_edit, $charactername), "misc.php?action=postpartner_edit");
		} else{
            add_breadcrumb($lang->postpartner_navigation, "misc.php?action=postpartner");
            add_breadcrumb($lang->sprintf($lang->postpartner_navigation_edit, $charactername), "misc.php?action=postpartner_edit");
		}

		// TEMPLATE FÜR DIE SEITE
		eval("\$page = \"".$templates->get("postpartner_edit")."\";");
		output_page($page);
		die();
    }

    // BEARBEITUNG SPEICHERN
    if($mybb->input['action'] == "postpartner_edit_do"){

        $ppid = $mybb->get_input('ppid'); 
        $old_max = $mybb->get_input('old_max'); 
        $old_res = $mybb->get_input('old_res'); 
        $new_max = $mybb->get_input('max_count'); 
        $charactername = get_user($mybb->get_input('uid'))['username'];

        // Vergleichen
        if ($old_max !== $new_max) {
           
            if($new_max == 0) {
                $max_count = $mybb->get_input('max_count');
                $res_count = 0;
            } else {

                if ($old_max == 0) {
                    $max_count = $mybb->get_input('max_count');
                    $res_count = $mybb->get_input('max_count');
                } else {
                    $diff_count = $old_max - $new_max;
                    $res_count = $old_res - $diff_count;
                    $max_count = $mybb->get_input('max_count');
                }
            }

        } else {
            $max_count = $mybb->get_input('max_count');
            $res_count = $old_res;
        }

        $update_postsearch = array(
            "max_count" => $max_count,
            "res_count" => $res_count,
            "inplaydate" => $db->escape_string($mybb->get_input('inplaydate')),
            "searchdesc" => $db->escape_string($mybb->get_input('searchdesc')),
        );

        $db->update_query("postpartners", $update_postsearch, "ppid = '".$ppid."'");

        redirect("misc.php?action=postpartner", $lang->sprintf($lang->postpartner_redirect_edit, $charactername));    

    }

    // DAMIT DIE PN SACHE FUNKTIONIERT
    require_once MYBB_ROOT."inc/datahandlers/pm.php";
    $pmhandler = new PMDataHandler();

    // kein Interesse => Grund als PN senden
    $rejectRequest = $mybb->get_input('rejectRequest');
    if($mybb->input['action'] == "rejectRequest") {
        $rejectRequest = $mybb->get_input('paid');

        // Datenbank Abfrage
        $alerts_query = $db->query("SELECT * FROM ".TABLE_PREFIX."postpartners_alerts  
        WHERE paid = '".$rejectRequest."'
        ");
        $privat = $db->fetch_array($alerts_query);
        $searchUser = $privat['searchUser'];
        $interestedUser = $privat['interestedUser'];
        $searchUsername = get_user($searchUser)['username'];
        $interestedUsername = get_user($interestedUser)['username'];

        // Ablehnungsgrund
        $rejectreason =  $db->escape_string($mybb->get_input('rejectreason'));

        $pm_change = array(
            "subject" => $lang->postpartner_pm_subject,
            "message" => $lang->sprintf($lang->postpartner_pm_message, $searchUsername, $rejectreason),
            //to: wer muss die anfrage bestätigen
            "fromid" => $searchUser,
            //from: wer hat die anfrage gestellt
            "toid" => $interestedUser,
            "icon" => "",
            "do" => "",
            "pmid" => "",
        );

        $pm_change['options'] = array(
            'signature' => '0',
            'savecopy' => '0',
            'disablesmilies' => '0',
            'readreceipt' => '0',
        );
        // $pmhandler->admin_override = true;
        $pmhandler->set_data($pm_change);
        if (!$pmhandler->validate_pm())
            return false;
        else {
            $pmhandler->insert_pm();
        }

        // Alert löschen
        $db->delete_query("postpartners_alerts", "paid = '".$rejectRequest."'");

        redirect($mybb->get_input('saveurl'), $lang->sprintf($lang->postpartner_redirect_rejectRequest, $interestedUsername));
    }
}

// INDEX BENACHRICHTIGUNG
function postpartner_global(){

    global $db, $mybb, $lang, $templates, $postpartner_header;
  
    // SPRACHDATEI
    $lang->load('postpartner');

    // EINSTELLUNG
    $inplaycategorie = $mybb->settings['postpartner_categorie'];
  
    // USER ID
    $user_id = $mybb->user['uid'];

	$saveurl = $_SERVER['REQUEST_URI']; 

    // ACCOUNTSWITCHER
    // Haupt-UID
    $mainID = $db->fetch_field($db->simple_select("users", "as_uid", "uid = '".$user_id."'"), "as_uid");
    if(empty($mainID)) {
        $mainID = $user_id;
    }
    // Zusatzfunktion - CharakterUID-string
    $charas = postpartner_get_allchars($user_id);
    //hier den string bauen ich hänge hinten und vorne noch ein komma dran um so was wie 1 udn 100 abzufangen
    $charastring = ",".implode(",", array_keys($charas)).",";

    $query_index = $db->query("SELECT * FROM ".TABLE_PREFIX."postpartners_alerts pa
    WHERE pa.searchUser IN (SELECT uid FROM ".TABLE_PREFIX."users u WHERE u.uid = '$mainID' OR u.as_uid = '$mainID')
    AND $user_id != 0
    ORDER BY (SELECT username FROM ".TABLE_PREFIX."users u WHERE u.uid = pa.searchUser) ASC
    ");
    while ($index = $db->fetch_array($query_index)) {

        // LEER LAUFEN LASSEN
        $paid = "";
        $ppid = "";
        $searchUser = "";
        $interestedUser = "";

        $reject_Request = "";
        $reject = "";
        $rejectlink = "";
        
        // MIT INFOS FÜLLEN
        $searchUser = get_user($index['searchUser'])['username'];
        $interestedUser = get_user($index['interestedUser'])['username'];
        $paid = $index['paid'];
        $ppid = $index['ppid'];

        // POPUP-FENSTER						
        eval("\$reject_Request .= \"".$templates->get("postpartner_header_rejectRequest")."\";");

        $reject = "<a onclick=\"$('#request_{$paid}').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== 'undefined' ? modal_zindex : 9999) }); return false;\" style=\"cursor: pointer;\">".$lang->postpartner_header_rejectlink."</a>";
        $rejectlink = "{$reject}<div class=\"modal\" id=\"request_{$paid}\" style=\"display: none;width:auto;\">{$reject_Request}</div>";
					
        $postpartner_headertext = $lang->sprintf($lang->postpartner_header_text, $interestedUser, $searchUser);

        eval("\$postpartner_header .= \"".$templates->get("postpartner_header")."\";");
    }
}

// USER WIRD GELÖSCHT
function postpartner_user_delete(){

    global $db, $cache, $mybb, $user;
    
    // UID gelöschter Chara
    $deleteChara = (int)$user['uid'];

    // POSTPARTNERSUCHEN VON DEM USER LÖSCHEN
    $db->delete_query('postpartners', "uid = ".$deleteChara."");

    // POSTPARTNER ALERTS LÖSCHEN
    $db->delete_query('postpartners_alerts', "searchUser = ".$deleteChara."");
    $db->delete_query('postpartners_alerts', "interestedUser = ".$deleteChara."");

}

// ONLINE ANZEIGE - WER IST WO
function postpartner_online_activity($user_activity) {

	global $parameters, $user;

	$split_loc = explode(".php", $user_activity['location']);
	if ($split_loc[0] == $user['location']) {
		$filename = '';
	} else {
		$filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
	}

	switch ($filename) {
		case 'misc':
			if ($parameters['action'] == "postpartner") {
				$user_activity['activity'] = "postpartner";
			}
			if ($parameters['action'] == "postpartner_edit") {
				$user_activity['activity'] = "postpartner_edit";

				$parameters['ppid'] = (int)$parameters['ppid'];
				$user_activity['ppid'] = $parameters['ppid'];
			}
            break;
	}


	return $user_activity;
}
function postpartner_online_location($plugin_array) {

	global $mybb, $theme, $lang, $db;
    
    // SPRACHDATEI LADEN
    $lang->load("postpartner");

	if ($plugin_array['user_activity']['activity'] == "postpartner") {
		$plugin_array['location_name'] = $lang->postpartner_online_location;
	}

	if ($plugin_array['user_activity']['activity'] == "postpartner_edit") {
		
		$characteruid = $db->fetch_field($db->simple_select("postpartners", "uid", "ppid = '".$plugin_array['user_activity']['ppid']."'"), "uid");
        $charactername = get_user($characteruid)['username'];

		$plugin_array['location_name'] = $lang->sprintf($lang->postpartner_online_location_edit, $charactername);
	}


	return $plugin_array;
}

// ACCOUNTSWITCHER HILFSFUNKTION
function postpartner_get_allchars($user_id) {
	global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer;

	//für den fall nicht mit hauptaccount online
	$as_uid = intval($mybb->user['as_uid']);

	$charas = array();
	if ($as_uid == 0) {
	  // as_uid = 0 wenn hauptaccount oder keiner angehangen
	  $get_all_users = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE (as_uid = $user_id) OR (uid = $user_id) ORDER BY username");
	} else if ($as_uid != 0) {
	  //id des users holen wo alle an gehangen sind 
	  $get_all_users = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE (as_uid = $as_uid) OR (uid = $user_id) OR (uid = $as_uid) ORDER BY username");
	}
	while ($users = $db->fetch_array($get_all_users)) {
  
	  $uid = $users['uid'];
	  $charas[$uid] = $users['username'];
	}
	return $charas;  
}

// MyALERTS
function postpartner_alerts() {

	global $mybb, $lang;

	$lang->load('postpartner');

	/**
	* Alert formatter for my custom alert type.
	*/
	class MybbStuff_MyAlerts_Formatter_postpartnernewFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter {
		
        /**
		* Format an alert into it's output string to be used in both the main alerts listing page and the popup.
		*
		* @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
		*
		* @return string The formatted alert string.
		*/
		public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert) {
			global $db;
			$alertContent = $alert->getExtraDetails();
			$userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
			$user = get_user($userid);
			$alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
			return $this->lang->sprintf(
				$this->lang->postpartner_new,
				$alertContent['username'],
				$alertContent['from']
			);
	
        }

        /**
        * Init function called before running formatAlert(). Used to load language files and initialize other required
        * resources.
        *
        * @return void
        */
        public function init() {
            if (!$this->lang->postpartner_new) {
                $this->lang->load('postpartner');
            }	
        }

        /**
        * Build a link to an alert's content so that the system can redirect to it.
        *
        * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
        *
        * @return string The built alert, preferably an absolute link.
        */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert) {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/misc.php?action=postpartner';
        }
    }

    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
            new MybbStuff_MyAlerts_Formatter_postpartnernewFormatter($mybb, $lang, 'postpartner_new')
        );
    }

}

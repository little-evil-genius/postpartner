<?php
function task_postpartner($task){

    global $db, $mybb;

    $allnull_query = $db->query("SELECT * FROM ".TABLE_PREFIX."postpartners p
    WHERE max_count != 0
    AND res_count = 0
    ");

    while($nulllist = $db->fetch_array($allnull_query)) {
        $ppid = $nulllist['ppid'];    
        $ppids[] = $nulllist['ppid'];
    }

    foreach ($ppids as $ppid) {
        $db->delete_query('postpartners', "ppid = ".$ppid);
        $db->delete_query('postpartners_alerts', "ppid = ".$ppid);
    }
    
    add_task_log($task, "Alle Postpartnersuchen mit ausgeschöpfter Szenenkapaziät wurden entfernt.");

}
?>

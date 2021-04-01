<?php

WriteInLog('###############################################');
WriteInLog('### [4/6] Discussions read status migration ###');
WriteInLog('###############################################');

$query = RunQuery($dbVanilla, "SELECT * FROM {$dbVanillaPrefix}UserDiscussion");
$statuses = $query->fetchAll(PDO::FETCH_ASSOC);
WriteInLog('Migrating '.$query->rowCount().' statuses...');

$statusesMigrated = 0;
$statusesIgnored = 0;

foreach ($statuses as $status) {
    $participantsList = [];

    $statusData = [
        ':user_id' => (int) $status['UserID'],
        ':discussion_id' => (int) $status['DiscussionID'],
        ':last_read_post_number' => (int) $status['CountComments'],
        ':last_read_at' => $status['DateLastViewed'],
    ];
    try {
        $query = $dbFlarum->prepare("INSERT INTO {$dbFlarumPrefix}discussion_user(user_id,discussion_id,last_read_post_number,last_read_at) VALUES(:user_id,:discussion_id,:last_read_post_number,:last_read_at)");
        $query->execute($statusData);
        $statusesMigrated += $query->rowCount();
    } catch (Exception $e) {
        ++$statusesIgnored;
    }
}

WriteInLog('Done, results :');
WriteInLog("{$statusesMigrated} status(es) migrated successfully", 'SUCCESS');
WriteInLog("{$statusesIgnored} status(es) ignored", 'SUCCESS');

<?php

WriteInLog('###################################');
WriteInLog('### [4/6] User groups migration ###');
WriteInLog('###################################');

$query = RunQuery($dbVanilla, "SELECT * FROM `{$dbVanillaPrefix}Role`");
$groups = $query->fetchAll(PDO::FETCH_ASSOC);

WriteInLog('Migrating '.$query->rowCount().' user groups...');
$groupsMigrated = 0;

foreach ($groups as $group) {
    $icon = null;

    // Admin 1->1
    if ($group['Name'] == 'Administrator') {
        $icon = 'wrench';
    }

    $groupData = [
        ':id' => $group['RoleID'],
        ':name_singular' => $group['Name'],
        ':name_plural' => $group['Name'].'s',
        ':color' => GetRandomColor(),
        ':icon' => $icon,
    ];

    $groupsQuery = RunPreparedQuery($dbFlarum, $groupData, "INSERT INTO `{$dbFlarumPrefix}groups`(`id`, `name_singular`, `name_plural`, `color`, `icon`) VALUES(:id, :name_singular, :name_plural, :color, :icon)");
    $groupsMigrated += $groupsQuery->rowCount();

    // Pivot table
    $queryGroupUserData = RunPreparedQuery($dbVanilla, [':RoleID' => $data['RoleID']], "SELECT * FROM {$dbVanillaPrefix}UserRole WHERE RoleID = :RoleID");
    $groupUserData = $queryGroupUserData->fetchAll(PDO::FETCH_ASSOC);
    foreach ($groupUserData as $data) {
        RunPreparedQuery($dbFlarum, [
            ':user_id' => $data['UserID'],
            ':group_id' => $data['RoleID'],
        ], "INSERT INTO {$dbFlarumPrefix}group_user(user_id, group_id) VALUES(:user_id, :group_id)");
    }

    WriteInLog("+ Group '".$group['Name']."' done");
}

WriteInLog('Done, results :');
WriteInLog("{$groupsMigrated} user groups migrated successfully", 'SUCCESS');

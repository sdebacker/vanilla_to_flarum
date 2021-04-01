<?php

WriteInLog('#############################');
WriteInLog('### [1/6] Users migration ###');
WriteInLog('#############################');

$query = RunQuery($dbVanilla, "SELECT UserID, Name, Password, Email, Photo, DateLastActive, DateFirstVisit, CountDiscussions, CountComments, DiscoveryText FROM {$dbVanillaPrefix}User");
$users = $query->fetchAll(PDO::FETCH_ASSOC);

WriteInLog('Migrating '.$query->rowCount().' users...');

$usersIgnored = 0;
$usersCleaned = 0;
$usersMigrated = 0;
$signatureMigrated = 0;
$avatarMigrated = 0;

foreach ($users as $user) {
    if (!IsNullOrEmptyString($user['Email'])) {
        // Case of a Deleted User
        if ($user['Name'] === '[Deleted User]') {
            $user['Name'] = 'deleted_user_'.md5($user['Email']);
        }

        // The username must contain only letters, numbers and dashes.
        if (!preg_match('/^[a-zA-Z0-9-_]+$/', $user['Name'])) {
            $username = Slugify($user['Name'], [
                'separator' => '',
                'regexp' => '/[^A-Za-z0-9_-]+/',
                'lowercase_after_regexp' => true,
            ]);
            $query = RunPreparedQuery($dbVanilla, [':username' => $username], "SELECT UserID FROM {$dbVanillaPrefix}User WHERE Name = :username");
            $row = $query->fetch(PDO::FETCH_ASSOC);

            if ($row['id']) {
                ++$usersIgnored;
                WriteInLog("Unable to clean username '".$user['Name']."', try to fix this account manually. Proposed nickname : '".$username."' (already exists in fluxbb database)", 'ERROR');
                continue;
            }
            ++$usersCleaned;
            WriteInLog("User '".$user['Name']."' cleaned (incorrect format). New nickname : '".$username."'", 'WARN');
        //SendNotificationToUser($user['email'], $user['Name'], $username);
        } else {
            $username = $user['Name'];
        }

        $userData = [
            ':id' => $user['UserID'],
            ':username' => $username,
            ':email' => $user['Email'],
            ':is_email_confirmed' => 1,
            ':password' => '',
            ':avatar_url' => $user['Photo'] !== '' && $user['Photo'] !== 'null' ? $user['Photo'] : null,
            ':joined_at' => $user['DateFirstVisit'],
            ':last_seen_at' => $user['DateLastActive'],
            ':comment_count' => $user['CountComments'] ?? 0,
            ':discussion_count' => $user['CountDiscussions'] ?? 0,
        ];

        $query = RunPreparedQuery($dbFlarum, $userData, "INSERT INTO {$dbFlarumPrefix}users(id,username,email,is_email_confirmed,password,avatar_url,joined_at,last_seen_at,comment_count,discussion_count) VALUES(:id,:username,:email,:is_email_confirmed,:password,:avatar_url,:joined_at,:last_seen_at,:comment_count,:discussion_count)");
        $usersMigrated += $query->rowCount();
    } else {
        ++$usersIgnored;
        WriteInLog("User '".$user['Name']."' ignored (no mail address)", 'WARN');
    }
}

WriteInLog('Done, results :');
WriteInLog("{$usersMigrated} user(s) migrated successfully", 'SUCCESS');
WriteInLog("{$usersIgnored} user(s) ignored (guest account + those without mail address + accounts not cleaned)", 'SUCCESS');
WriteInLog("{$usersCleaned} user(s) cleaned (incorrect format)", 'SUCCESS');
WriteInLog("{$signatureMigrated} signature(s) cleaned and migrated successfully", 'SUCCESS');
WriteInLog("{$avatarMigrated} avatar(s) migrated successfully", 'SUCCESS');

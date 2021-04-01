<?php

WriteInLog('##################################');
WriteInLog('### [2/6] Categories migration ###');
WriteInLog('##################################');

$query = RunQuery($dbVanilla, "SELECT CategoryID, Name, Sort, UrlCode, Description, CountDiscussions FROM `${dbVanillaPrefix}Category`");
$categories = $query->fetchAll(PDO::FETCH_ASSOC);

WriteInLog('Migrating '.$query->rowCount().' categories...');
$categoriesMigrated = 0;

foreach ($categories as $category) {
    if ($category['CategoryID'] < 0) {
        $lastId = RunQuery($dbVanilla, "SHOW TABLE STATUS LIKE '${dbVanillaPrefix}Category'");
        $size = $lastId->fetch(PDO::FETCH_ASSOC);
        $category['CategoryID'] = $size['Auto_increment'];
    }
    $categorieData = [
            ':id' => $category['CategoryID'],
            ':name' => $category['Name'],
            ':slug' => $category['UrlCode'],
            ':description' => $category['Description'],
            ':color' => GetRandomColor(),
            ':position' => $category['Sort'],
            ':discussion_count' => $category['CountDiscussions'],
        ];

    $query = RunPreparedQuery($dbFlarum, $categorieData, "INSERT INTO ${dbFlarumPrefix}tags(id,name,slug,description,color,position,discussion_count) VALUES(:id,:name,:slug,:description,:color,:position,:discussion_count)");
    $categoriesMigrated += $query->rowCount();

    WriteInLog("+ Category '".$category['Name']."' done");
}

WriteInLog('Done, results :');
WriteInLog("$categoriesMigrated categories migrated successfully", 'SUCCESS');

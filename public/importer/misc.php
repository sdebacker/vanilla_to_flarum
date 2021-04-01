<?php

WriteInLog('###########################');
WriteInLog('### [6/6] Miscellaneous ###');
WriteInLog('###########################');

$query = RunQuery($dbFlarum, "SELECT id FROM {$dbFlarumPrefix}users");
$users = $query->fetchAll(PDO::FETCH_ASSOC);

WriteInLog('Starting the update posts counters and counters discussions...');

foreach ($users as $user) {
    $userId = $user['id'];

    // count the number of posts
    $query = RunPreparedQuery($dbFlarum, [':user_id' => $userId], "SELECT COUNT(id) AS nb_posts FROM {$dbFlarumPrefix}posts WHERE user_id = :user_id");
    $nb_posts = $query->fetchAll(PDO::FETCH_ASSOC);

    // count the number of discussions
    $query = RunPreparedQuery($dbFlarum, [':start_user_id' => $userId], "SELECT COUNT(id) AS nb_discussions FROM {$dbFlarumPrefix}discussions WHERE start_user_id = :start_user_id");
    $nb_discussions = $query->fetchAll(PDO::FETCH_ASSOC);

    $userData = [
        ':discussions_count' => (int) ($nb_discussions[0]['nb_discussions']),
        ':comments_count' => (int) ($nb_posts[0]['nb_posts']),
        ':id' => $userId,
    ];

    RunPreparedQuery($dbFlarum, $userData, "UPDATE {$dbFlarumPrefix}users SET discussions_count = :discussions_count, comments_count = :comments_count WHERE id = :id");
}

WriteInLog('Done', 'SUCCESS');

WriteInLog('Starting the update of the table tags...');

$query = RunQuery($dbFlarum, "SELECT id FROM {$dbFlarumPrefix}tags");
$tags = $query->fetchAll(PDO::FETCH_ASSOC);

foreach ($tags as $tag) {
    $tagId = $tag['id'];

    // Update last message in the current tag
    $query = RunPreparedQuery(
        $dbFlarum,
        [':tag_id' => $tagId],
        "SELECT posts.discussion_id, posts.time
        FROM {$dbFlarumPrefix}discussions_tags AS discussions_tags
        INNER JOIN {$dbFlarumPrefix}posts AS posts
          ON discussions_tags.discussion_id = posts.discussion_id
        WHERE discussions_tags.tag_id = :tag_id
        ORDER BY posts.time DESC
        LIMIT 1"
    );
    $last_discussion = $query->fetchAll(PDO::FETCH_ASSOC);

    $tagData = [
        ':tagId' => $tagId,
        ':last_time' => empty($last_discussion[0]['time']) ? null : $last_discussion[0]['time'],
        ':last_discussion_id' => $last_discussion[0]['discussion_id'],
    ];

    RunPreparedQuery($dbFlarum, $tagData, "UPDATE {$dbFlarumPrefix}tags SET last_discussion_id = :last_discussion_id, last_time = :last_time WHERE id = :tagId");

    // Update tags.discussions_count
    $query = RunPreparedQuery($dbFlarum, [':tag_id' => $tagId], "SELECT COUNT(*) AS count FROM {$dbFlarumPrefix}discussions_tags WHERE tag_id = :tag_id");
    $discussions_count = $query->fetchAll(PDO::FETCH_ASSOC);

    $tagData = [
        ':discussions_count' => (int) ($discussions_count[0]['count']),
        ':id' => $tagId,
    ];

    RunPreparedQuery($dbFlarum, $tagData, "UPDATE {$dbFlarumPrefix}tags SET discussions_count = :discussions_count WHERE id = :id");
}

WriteInLog('Done', 'SUCCESS');

WriteInLog('Converting vanilla http(s) links...');

$query = RunQuery($dbFlarum, "SELECT id, content FROM {$dbFlarumPrefix}posts");
$posts = $query->fetchAll(PDO::FETCH_ASSOC);

foreach ($posts as $post) {
    $content = $post['content'];
    $postId = $post['id'];

    $content = s9e\TextFormatter\Unparser::unparse($content);
    $content = ConvertLinkFluxbb($content);
    $content = TextFormatter::parse($content);

    $postData = [
        ':content' => $content,
        ':id' => $postId,
    ];

    RunPreparedQuery($dbFlarum, $postData, "UPDATE {$dbFlarumPrefix}posts SET content = :content WHERE id = :id");
}

WriteInLog('Done', 'SUCCESS');

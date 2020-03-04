<?php

use League\HTMLToMarkdown\HtmlConverter;
use s9e\TextFormatter\Bundles\Fatdown as TextFormatter;

WriteInLog('########################################');
WriteInLog('### [3/5] Discussions and posts migration ###');
WriteInLog('########################################');

$query = RunQuery($dbVanilla, "SELECT * FROM ${dbVanillaPrefix}Discussion");
$discussions = $query->fetchAll(PDO::FETCH_ASSOC);
WriteInLog('Migrating '.$query->rowCount().' discussions...');

$discussionsMigrated = 0;
$postsMigrated = 0;
$discussionsIgnored = 0;

foreach ($discussions as $discussion) {
    $participantsList = [];

    $discussionData = [
        ':id' => $discussion['DiscussionID'], // Topic ID
        ':title' => $discussion['Name'], // Topic title
        ':comment_count' => $discussion['CountComments'], // Number of posts in the topic counting the first post
        ':participant_count' => count($participantsList), // Number of participants in this topic
        ':post_number_index' => $discussion['CountComments'], // Number of items in the topic
        ':created_at' => $discussion['DateInserted'], // Topic creation date
        ':user_id' => $discussion['InsertUserID'], // ID of the user who created the topic
        ':first_post_id' => null, // First post ID
        ':last_posted_at' => $discussion['DateLastComment'], // Last post date
        ':last_posted_user_id' => $discussion['LastCommentUserID'], // ID of the user who posted last
        ':last_post_id' => null, // Last post ID
        ':last_post_number' => $discussion['CountComments'], // Index of the last element of the topic
        ':slug' => Slugify($discussion['Name'], ['separator' => '-', 'lowercase' => true]), // Topic url slug part (human-readable keywords)
        ':is_approved' => 1, // Approve all migrated discussions
        ':is_locked' => $discussion['Closed'], // Is the topic locked ?
        ':is_sticky' => $discussion['Announce'], // Is the topic pinned ?
    ];

    // TODO: After inserting posts, update first_post_id, last_post_id and participant_count

    $query = RunPreparedQuery($dbFlarum, $discussionData, "INSERT INTO ${dbFlarumPrefix}discussions(id,title,comment_count,participant_count,post_number_index,created_at,user_id,first_post_id,last_posted_at,last_posted_user_id,last_post_id,last_post_number,slug,is_approved,is_locked,is_sticky) VALUES(:id,:title,:comment_count,:participant_count,:post_number_index,:created_at,:user_id,:first_post_id,:last_posted_at,:last_posted_user_id,:last_post_id,:last_post_number,:slug,:is_approved,:is_locked,:is_sticky)");
    $discussionsMigrated += $query->rowCount();

    // Posts
    $query = RunPreparedQuery($dbVanilla, [':DiscussionID' => $discussion['DiscussionID']], "SELECT * FROM ${dbVanillaPrefix}Comment WHERE DiscussionID = :DiscussionID ORDER BY CommentID");
    $posts = $query->fetchAll(PDO::FETCH_ASSOC);

    $currentPostNumber = 1;

    // Treat all posts from current topic
    foreach ($posts as $post) {
        ++$currentPostNumber;
        $userId = $post['InsertUserID'];

        if (!in_array($userId, $participantsList)) {
            $participantsList[] = $userId;
        }

        $content = $post['Body'];

        // foreach ($smileys as $smiley) {
        //     $quotedSmiley = preg_quote($smiley[1], '#');
        //     $match = '#(?<=\s|^)('.$quotedSmiley.')(?=\s|$)#'; // a space is required before and after the pattern
        //     $content = preg_replace($match, '[img]/assets/images/smileys/'.$smiley[0].'[/img]', $content);
        // }

        $content = (new HtmlConverter())->convert($content);
        $content = TextFormatter::parse(ReplaceUnsupportedMarks($content));

        $postData = [
            ':id' => $post['CommentID'],
            ':discussion_id' => $post['DiscussionID'],
            ':number' => $currentPostNumber,
            ':created_at' => $post['DateInserted'],
            ':user_id' => $userId,
            ':type' => 'comment',
            ':content' => $content,
            ':edited_at' => $post['DateUpdated'] ?: null,
            ':edited_user_id' => $post['UpdateUserID'] ? GetUserID($post['UpdateUserID']) : null,
            ':ip_address' => null,
            ':is_approved' => 1,
        ];

        $query = RunPreparedQuery($dbFlarum, $postData, "INSERT INTO ${dbFlarumPrefix}posts(id,discussion_id,number,created_at,user_id,type,content,edited_at,edited_user_id,ip_address,is_approved) VALUES(:id,:discussion_id,:number,:created_at,:user_id,:type,:content,:edited_at,:edited_user_id,:ip_address,:is_approved)");
        $postsMigrated += $query->rowCount();
    }

    //
    // Topic/tags link
    //

    $query = RunPreparedQuery($dbVanilla, [':CategoryID' => $discussion['CategoryID']], "SELECT * FROM ${dbVanillaPrefix}Category WHERE CategoryID = :CategoryID");
    $row = $query->fetch(PDO::FETCH_ASSOC);
    // Link the topic with a primary tag (vanilla category)
    if ($row['CategoryID'] == '-1') {
        // Set it to Vanilla’s Root category.
        $queryRootCategoryId = RunQuery($dbFlarum, "SELECT id FROM `${dbFlarumPrefix}tags` WHERE `slug`='root'");
        $RootCategory = $queryRootCategoryId->fetch(PDO::FETCH_ASSOC);
        $row['CategoryID'] = $RootCategory['id'];
    }

    RunPreparedQuery($dbFlarum, [
        ':discussion_id' => $discussion['DiscussionID'],
        ':tag_id' => $row['CategoryID'],
    ], "INSERT INTO ${dbFlarumPrefix}discussion_tag(discussion_id, tag_id) VALUES(:discussion_id, :tag_id)");
}
foreach ($discussions as $discussion) {
    // Insert the first post in the table posts.

    $content = $discussion['Body'];
    // foreach ($smileys as $smiley) {
    //     $quotedSmiley = preg_quote($smiley[1], '#');
    //     $match = '#(?<=\s|^)('.$quotedSmiley.')(?=\s|$)#'; // a space is required before and after the pattern
    //     $content = preg_replace($match, '[img]/assets/images/smileys/'.$smiley[0].'[/img]', $content);
    // }

    $content = (new HtmlConverter())->convert($content);
    $content = TextFormatter::parse(ReplaceUnsupportedMarks($content));

    $firstPostData = [
        ':discussion_id' => $discussion['DiscussionID'],
        ':number' => 1,
        ':created_at' => $discussion['DateInserted'],
        ':user_id' => $discussion['InsertUserID'],
        ':type' => 'comment',
        ':content' => $content,
        ':edited_at' => $discussion['DateUpdated'] ?: null,
        ':edited_user_id' => $discussion['UpdateUserID'] ? GetUserID($discussion['UpdateUserID']) : null,
        ':ip_address' => null,
        ':is_approved' => 1,
    ];

    $query = RunPreparedQuery($dbFlarum, $firstPostData, "INSERT INTO ${dbFlarumPrefix}posts(discussion_id,number,created_at,user_id,type,content,edited_at,edited_user_id,ip_address,is_approved) VALUES(:discussion_id,:number,:created_at,:user_id,:type,:content,:edited_at,:edited_user_id,:ip_address,:is_approved)");

    $firstPostId = $dbFlarum->lastInsertId();

    // Update the first_post_id in the discussions table.
    $query = RunPreparedQuery($dbFlarum, [
        ':id' => $discussion['DiscussionID'],
        ':first_post_id' => $firstPostId,
    ], "UPDATE ${dbFlarumPrefix}discussions SET first_post_id=:first_post_id WHERE id=:id");
}

WriteInLog('Done, results :');
WriteInLog("$discussionsMigrated topic(s) migrated successfully", 'SUCCESS');
WriteInLog("$postsMigrated post(s) migrated successfully", 'SUCCESS');
WriteInLog("$discussionsIgnored topic(s) ignored (moved discussions)", 'SUCCESS');

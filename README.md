# vanilla_to_flarum
Convert the database of Vanilla Forum to Flarum.

Currently you can import users, groups, discussions, posts and tags.

There are some more work to be done :

- When importing the groups, we loose the possibility to manage the forum settings
- After inserting posts, the columns `first_post_id`, `last_post_id` and `participant_count` need to be updated.
- The `misc.php` importer should be adapted to Vanilla forums.

This script is an adaptation of some parts of https://github.com/mondediefr/fluxbb_to_flarum.

# Vanilla to Flarum database converter
Convert the database of Vanilla Forum to Flarum.

Currently you can import users, groups, discussions, posts and tags.

There are some more work to be done :

- When importing the groups, we loose the possibility to manage the forum settings
- After inserting posts, the columns `last_post_id` and `participant_count` should be updated.
- The `misc.php` importer is not working yet, it should be adapted to Vanilla forums.
- The format (HTML, BB, Markdown) of the messages have to be correctly converted.
- The internal links in the posts have to be converted.
- The Following discussions could be transfered.
- Keep the mark as read status

This script is an adaptation of some parts of https://github.com/mondediefr/fluxbb_to_flarum.

## Usage

- Download this repository to a folder accessible by a navigator.
- Enter this repository and run the command `composer install`.
- Duplicate and rename `.env.example` to `.env` and fill it.
- Open your browser and navigate to this importer.

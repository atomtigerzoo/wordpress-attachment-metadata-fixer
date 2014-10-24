## Wordpress attachment metadata fixer

**PHP class for fixing missing or corrupted `_wp_attachment_metadata` image sizes in a Wordpress database.**

### Why this class?

  The following happend: after adding a new custom image size in a Wordpress installation and running [Regenerate Thumbnails](https://wordpress.org/plugins/regenerate-thumbnails/) _(no offense intended here!)_ the `wp_postmeta` table in the database was missing 90% of all custom image size references. I searched hours and tried different solutions and other plugins - nothing worked. So I wrote this class to make the neccessary changes to the database table to get the custom sizes back.

If you are missing your custom image or thumbnail sizes in the database (please check first - **this is *not* an image generation/re-generation script**) this class might come in handy!

### How to use it?

First off: ***Don't operate on your live database. Don't use this without proper backups of your database!*** Please! I told you!

1) Backup your database and make sure it's complete and ok. Store the backup in a safe place.

2) Dump the `wp_postmeta` table.

3) Create a new database with the name 'image-fix' (also create a) suitable user/login for this database. DON'T USE YOUR LIVE DATABASE!!

4) Import the previous dump of the wp_postmeta table.

5) Grab a copy of the PHP class and save it to your server in a directory only you know (or secure it somehow).

6) Take a look at the source, change the configuration to your needs.

7) Open 'wp-image-metadata-fixer.php' in a browser or fromt he command line.

8) If you are happy with the results printed out and everything is ok, you can change the `$test` variable to `false` to make changes to the database (Again: don't use a live database!)

If you don't have to many entries or your hosting is very weak the script will change all database entries. Call it again to see if all entries are changed.

9) Confirm that you still have your backup of the live database.

10) Dump the table wp_postmeta from the image-fix database and import it in your live database.

I hope the script helped and everything worked out fine.


### Bugs, Errors, Improvements

If you have any improvements to my code please sumbit a pull-request. I will be happy to include it if I like it :)

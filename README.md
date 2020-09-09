## EPG for ott-play by alex

As we all know about this great lightweight app for watching IPTV streams, and we all love it, BUT, it always had one problem - **EPG** 
We patiently waited for developer of this app to add this crucial feature, of having your own EPG, so we decided to _add_ this missing feature using DNS hack.

This PHP script, using Laravel framework helps you to grab and store your own or public EPG xml files on database or your choice.

## How to use it?

- Run this app using [Nginx](https://github.com/nuboxas/epg-multi/blob/master/nginx.config), Apache or any other web server
- Setup your database, where all EPG will be stored
- Add provider(s) info to your database
- Adjust scheduler when to update all of EPG
- Setup Redis connection (for caching EPG) _optional_
- Change DNS record, to point _epg.ott-play.com_ to your server

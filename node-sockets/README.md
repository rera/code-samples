Portfolio Template
============

* NODE.JS
* SOCKET.IO
* SOCKETSTREAM
* JADE
* STYLUS
* JQUERY
* HOGAN
* REDIS
* MONGODB

### APIs
* TWILIO
* GROOVESHARK
* TINYSONG
* LASTFM


SMS-powered, collaborative playlist for streaming music at parties or events. This is a Node.js app built on Express using SocketStream for realtime syncing. 

On the server side:  
1. Receives SMS texts via the Twilio API
2. Parses into a hashtag event name and an artist/track name 
3. Gets a Grooveshark song id using their TinySong API
4. Gets album art and track details using the Last.fm API
5. Publishes to a mongodb store for syncing
_* code for these steps *NOT* published in this repo_

Example server code available in this repo includes the session manager that handles client requests.

Client side uses SocketStream via jQuery-heavy JS to pull down a track list, and play requested tracks using the Grooveshark API. The UI is built with Jade templates and Stylus, and the DOM is manipulated with Hogan templates (tracks added/removed). 

View it [live](http://responsive.is/juke.io) on responsive.is.

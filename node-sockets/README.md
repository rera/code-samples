Node Sockets
============

Juke.io is an SMS-powered, collaborative playlist for streaming music at parties or events. This is a Node.js app built on Express using SocketStream for realtime syncing. Server code available in this repo includes the session manager that handles client requests. Client side uses SocketStream via jQuery-heavy JS to pull down a track list, and play requested tracks using the Grooveshark API. The UI is built with Jade templates and Stylus, and the DOM is manipulated with Hogan templates (tracks added/removed). 

##### Server-side behind the scenes:

1. Receives SMS texts via the Twilio API
2. Parses into a hashtag event name and an artist/track name
3. Gets a Grooveshark song id using their TinySong API
4. Gets album art and track details using the Last.fm API
5. Publishes to a mongodb store for syncing

_*code for these steps not published in this repo_ 

[View it live](http://responsive.is/juke.io) using responsive.is.

_Send an SMS to 1-817-60-MUSIC (68742) with \#demo and a song name to see it work (eg: "#demo White Sky")._

### Stack
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

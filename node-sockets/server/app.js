var config = require('../middleware/config'),
	http = require('http'),
	gs = require('grooveshark');

exports.actions = function(req, res, ss){ 
	//console.log(req); 
	
	req.use('session');
	
	return { 
		
		// init connection to event
		init: function(event){ 
			req.session.channel.subscribe(event);
		},
		
		// create new event
		create: function(data){ 
			res(data);
		},
		
		// get tracks for event
		tracks: function(){ 
			var event = req.session.channel.list()[0];
			require('mongodb').connect( config.mongo.url, function(err, conn) {
				if(err) {
					console.log(err);
				} else {
					var fourhoursago = new Date();
					fourhoursago.setHours(fourhoursago.getHours()-4);
					
					conn.collection('queue', function(err, coll){
						coll.find( { 'event': event, 'played': false, 'dt': { "$gte": fourhoursago } }, { sort:'dt' }, function(err, cursor){
							cursor.toArray(function(err, items) {
								res(items);
							});
						});
					});
				}
			});
		},
		
		// get stream track
		stream: function(track){
			var client = new gs(config.grooveshark.key, config.grooveshark.secret);
			client.authenticate(config.grooveshark.username, config.grooveshark.password, function(err) {
				client.request('getCountry', { /*ip: req.clientIp*/ }, function(err, status, body) {
					if(!err) {
						client.request('getSubscriberStreamKey', { songID: track, country: body }, function(err, status, body) {//'getStreamKeyStreamServer'
							if(!err) {
								res(body);
							} else {
								res(err);
							}
						});
					}
				});
			});
		},
		
		// send thirty second notification
		thirty: function(data){
			var client = new gs(config.grooveshark.key, config.grooveshark.secret);
			client.authenticate(config.grooveshark.username, config.grooveshark.password, function(err) {
				client.request('markStreamKeyOver30Secs', { streamKey: data.streamKey, streamServerID: data.serverID }, function(err, status, body) {
					if(!err) {
						res('markStreamKeyOver30Secs');
					} else {
						res(err);
					}
				});
			});
		},
		
		// mark track as played for event
		played: function(data){ 
			var mongo = require('mongodb'),
			BSON = mongo.BSONPure,
			id = new BSON.ObjectID(data.id),
			time = data.time;
			
			if(time > 30) {
				var client = new gs(config.grooveshark.key, config.grooveshark.secret);
				client.authenticate(config.grooveshark.username, config.grooveshark.password, function(err) {
					client.request('markSongComplete', { songID: data.songID, streamKey: data.streamKey, streamServerID: data.serverID }, function(err, status, body) {
						if(!err) {
							console.log('markSongComplete');
						} else {
							console.log(err);
						}
					});
				});
			}
			
			mongo.connect( config.mongo.url, function(err, conn) {
				if(err) {
					console.log(err);
				} else {
					conn.collection('queue', function(err, coll){
						if(err) {
							console.log(err);
						} else {
							coll.update( { _id: id }, { played: true }, { safe: true }, function(err){
								if(err) {
									console.log(err);
								} else {
									res('success');
								}
							});
						}
					});
				}
			});
		},
				
		sendAlert: function(){ 
			ss.publish.all('systemAlert', 'The server is about to be shut down' ); 
		} 
	} 
} 
// Client Code

var a = [],
	streamKey = '',
	serverID = '',
	event = $('input#event').val(),
	notice = false,
	lightTheme = null;
		
buildTrack = function(item) {
	return ss.tmpl['track'].render(item);
}
playTrack = function() {
	var first = $('ul#tracks li').attr('data-track');
	ss.rpc('app.stream', first, function(result){
		console.log(result);
		streamKey = result.StreamKey;
		serverID = result.StreamServerID;
		a[0].load(result.url);
		a[0].play();
	});
}
getTracks = function() {
	notice = false;
	ss.rpc('app.tracks', function(result){ 
		console.log(result);
		$('ul#tracks').html('');
		var html = [];
		$.each(result, function(i,item){
			var temp = buildTrack(item);
			html.push(temp);
		});
		$('ul#tracks').html(html.join(''));
		
		if($('ul#tracks li').length>0) {
			playTrack();
		}
	});
}
	
audiojs.events.ready(function() {
	a = audiojs.createAll({
		trackEnded: function() {
			var data = {
				id: $('ul#tracks li').attr('id'),
				time: $('audio').first()[0].duration,
				streamKey: streamKey,
				serverID: serverID,
				songID: $('ul#tracks li').attr('data-track')
			};
			a[0].pause();
			notice = false;
			ss.rpc('app.played', data, function(result) {
				if(result == 'success')
					window.getTracks();
			});
		}
	});
	$('audio').each(function() {
		$(this).bind('timeupdate', function() {
			var time = $('audio').first()[0].currentTime;
			if(time > 30 && !notice) {
				notice = true;
				var data = {
					streamKey: streamKey,
					serverID: serverID
				};
				ss.rpc('app.thirty', data, function(result) {
					console.log(result);
				});
			}
		});
	});
});
	
	
// Socket server and event registration and handlers

ss.server.on('disconnect', function(){
	console.log('Connection down :-(');
});

ss.server.on('reconnect', function(){
	console.log('Connection back up :-)');
	if($('audio')[0].paused) {
		getTracks();
	}
});

ss.event.on('add', function(item, channel){
	console.log('event: add');
	toastr.info(item.song + ' by ' + item.artist + ' added to #' + item.event);
	var temp = buildTrack(item);
	$('ul#tracks').append(temp);
	
	if($('ul#tracks li').length == 1) {
		playTrack();
	}
});

ss.event.on('skip', function(item, channel){
	console.log('event: skip');
	toastr.info('Skip command received.');
	a[0].pause();
	var data = {
		id: $('ul#tracks li').attr('id'),
		time: $('audio').first()[0].currentTime,
		streamKey: streamKey,
		serverID: serverID,
		songID: $('ul#tracks li').attr('data-track')
	};
	ss.rpc('app.played', data, function(result) {
		if(result == 'success')
			window.getTracks();
	});
});

ss.event.on('pause', function(item, channel){
	console.log('event: pause');
	toastr.info('Pause command received.');
	a[0].pause();
});

ss.event.on('play', function(item, channel){
	console.log('event: play');
	toastr.info('Play command received.');
	a[0].play();
});

ss.event.on('mute', function(item, channel){
	console.log('event: mute');
	toastr.info('Mute command received.');
	// mute audio
});

ss.event.on('unmute', function(item, channel){
	console.log('event: unmute');
	toastr.info('Unmute command received.');
	// unmute audio
});

ss.event.on('undo', function(item, channel){
	console.log('event: undo');
	toastr.info('Undo command received.');
	$('li#'+item).remove();
	if($('ul#tracks li').length == 0)
		a[0].pause();
});


$(function() {
	
	// render stripe form
	//$('#stripe').html(ss.tmpl['stripe'].render());
    
    // Load stripe
    require('/stripe');
	
	ss.rpc('app.init', event);
	
	// initial tracks fetch
	window.getTracks();
});

	
	
// UserView feedback
var uvOptions = {};
(function() {
	var uv = document.createElement('script'); uv.type = 'text/javascript'; uv.async = true;
	uv.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'widget.uservoice.com/3wEftMvLgplCwDMIPHJOTQ.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(uv, s);
})();

// Google Analytics
var _gaq = _gaq || []; 
_gaq.push(['_setAccount', 'UA-36862115-1']); 
_gaq.push(['_trackPageview']); 

(function() { 
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true; 
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js'; 
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s); 
})();				

// Pretty Print
prettyPrint();


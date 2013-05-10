
var utility = require("../utility.js");



/*
 * GET crimes data.
 */

exports.index = function(req, res){
	var request = require("request");

	var start = req.params.start;
	var end = req.params.end;
	var types = req.params.types;
	
	if( (start && end && types) && utility.isValidDate(start, 'mm-dd-yyyy') && utility.isValidDate(end, 'mm-dd-yyyy') && /^([a-zA-Z]+|\b,\b)+$/.test(types) ) {
		
		start = start.replace(/-/g, '/');
		end = end.replace(/-/g, '/');
		
		var url = "http://www.crimemapping.com/GetIncidents.aspx?db=" + start + "+00:00:00&de=" + end + "+23:59:00&ccs=" + types + "&xmin=-8943758.013339777&ymin=3076775.9927826896&xmax=-8891704.397077642&ymax=3101350.4973763404&faid=c5542236-85b6-402e-b598-d6b2e9b5683e";
	
		request(url, function(error, response, body) {
			var data = JSON.parse( body );
			var incidents = data['incidents'];
			
			console.log(incidents.length);
			
			var crimes = [];
			var queue = 0;
			
			incidents.forEach(function(incident) {
				var address = incident.Location;
				address = address.replace(/\//g," & ");
				
				var crime = {
					'case' : incident.CaseNumber,
					'type' : incident.CrimeCode,
					'address' : address,
					'description' : incident.Description,
					'date' : incident.DateReported
				};
				
				var latlng = utility.convertMercatorToLatLon(incident.Y, incident.X);
				crime.latitude = latlng.Lat;
				crime.longitude = latlng.Lon;
				
				crimes.push(crime);
			});
			
			res.send( crimes );
		});
	} else {
		process.on('uncaughtException', function (err) {
		console.error('uncaughtException:', err.message)
		console.error(err.stack)
		process.exit(1)})
	}
};

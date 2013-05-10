/* Utility functions */



exports.isValidDate = function (value, userFormat) {
	userFormat = userFormat || 'mm/dd/yyyy', // default format
	
	delimiter = /[^mdy]/.exec(userFormat)[0],
	theFormat = userFormat.split(delimiter),
	theDate = value.split(delimiter),
	
	isDate = function (date, format) {
		var m, d, y
		for (var i = 0, len = format.length; i < len; i++) {
			if (/m/.test(format[i])) m = date[i]
			if (/d/.test(format[i])) d = date[i]
			if (/y/.test(format[i])) y = date[i]
		}
		return (
			m > 0 && m < 13 &&
			y && y.length === 4 &&
			d > 0 && d <= (new Date(y, m, 0)).getDate()
		)
	}

	return isDate(theDate, theFormat);
}

exports.convertMercatorToLatLon = function (mercX, mercY) {
	var radius = 6378137;
	var shift  = Math.PI * radius;
    var lon    = mercX / shift * 180.0;
    var lat    = mercY / shift * 180.0;
    lat = 180 / Math.PI * (2 * Math.atan(Math.exp(lat * Math.PI / 180.0)) - Math.PI / 2.0);
 
    return { 'Lon': lon, 'Lat': lat };
}



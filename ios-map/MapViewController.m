//
//  MapViewController.m
//
//

#import "MapViewController.h"

@interface MapViewController ()
    
@end

@implementation MapViewController
@synthesize mapView, crimeTypes, crimeTypeOptions, startDate, endDate, incidentsData, incidents, activePin;

- (void)viewDidLoad
{
    [super viewDidLoad];
    
    crimeTypeOptions = @{
                         @"Arson":
                         @{
                             @"abbreviation": @"AR",
                             @"color": @24.0f
                             },
                         @"Assault":
                         @{
                             @"abbreviation": @"AS",
                             @"color": @48.0f
                             },
                         @"Burglary":
                         @{
                             @"abbreviation": @"BU",
                             @"color": @72.0f
                             },
                         @"Disturbing the Peace":
                         @{
                             @"abbreviation": @"DP",
                             @"color": @96.0f
                             },
                         @"Drugs/Alcohol Violations":
                         @{
                             @"abbreviation": @"DR",
                             @"color": @120.0f
                             },
                         @"DUI":
                         @{
                             @"abbreviation": @"DU",
                             @"color": @144.0f
                             },
                         @"Fraud":
                         @{
                             @"abbreviation": @"FR",
                             @"color": @168.0f
                             },
                         @"Homocide":
                         @{
                             @"abbreviation": @"HO",
                             @"color": @192.0f
                             },
                         @"Motor Vehicle Theft":
                         @{
                             @"abbreviation": @"VT",
                             @"color": @216.0f
                             },
                         @"Robbery":
                         @{
                             @"abbreviation": @"RO",
                             @"color": @240.0f
                             },
                         @"Sex Crimes":
                         @{
                             @"abbreviation": @"SX",
                             @"color": @264.0f
                             },
                         @"Theft/Larceny":
                         @{
                             @"abbreviation": @"TH",
                             @"color": @288.0f
                             },
                         @"Vandalism":
                         @{
                             @"abbreviation": @"VA",
                             @"color": @312.0f
                             },
                         @"Vehicle Break-in / Theft":
                         @{
                             @"abbreviation": @"VB",
                             @"color": @336.0f
                             },
                         @"Weapons":
                         @{
                             @"abbreviation": @"WE",
                             @"color": @359.0f
                             },
                         };
    
    crimeTypes = [[NSMutableArray alloc] initWithArray:[crimeTypeOptions allKeys] copyItems:YES];
    
    startDate = [NSDate date];
    endDate = [NSDate date];
    
    NSCalendar *calendar = [[NSCalendar alloc] initWithCalendarIdentifier:NSGregorianCalendar];
    NSDateComponents *components = [calendar components:NSYearCalendarUnit|NSMonthCalendarUnit|NSDayCalendarUnit fromDate:endDate];
    [components setDay:([components day] - 7)];
    
    startDate = [calendar dateFromComponents:components];
    
    incidents = [[NSMutableArray alloc] init];
    queue = total = 0;
    
    CLLocationCoordinate2D defaultCoord;
    defaultCoord.latitude = 26.7150;
    defaultCoord.longitude = 80.0536;
    MKCoordinateRegion region = MKCoordinateRegionMakeWithDistance(defaultCoord, 100, 100);
    [self.mapView setRegion:region animated:YES];
    
    [self loadIncidents];
}

- (void)didReceiveMemoryWarning
{
    [super didReceiveMemoryWarning];
    // Dispose of any resources that can be recreated.
}

-(void) prepareForSegue:(UIStoryboardSegue *)segue sender:(id)sender {
    if([segue.identifier isEqualToString:@"mapOptionsSeque"]){
        MapOptionsViewController *mapOptionsViewController = [segue destinationViewController];
        [mapOptionsViewController setDelegate:self];
    }
    else if ([segue.identifier isEqualToString:@"incidentDetailSegue"]) {
        IncidentDetailViewController *incidentDetailViewController = [segue destinationViewController];
        [incidentDetailViewController setDelegate:self];
    }
}

- (NSArray*)getCrimeTypeAbbreviations
{
    NSMutableArray *temp = [[NSMutableArray alloc] init];
    for (NSString *type in crimeTypes) {
        [temp addObject:[[crimeTypeOptions objectForKey:type] objectForKey:@"abbreviation"]];
    }
    
    return [[NSArray alloc] initWithArray:temp];
}

- (UIColor*)getCrimeTypeColor:(NSString*)type
{
    float color = [[NSString stringWithFormat:@"%@", [[crimeTypeOptions objectForKey:type] objectForKey:@"color"]] floatValue];
    return [UIColor colorWithHue:(color / 360.0) saturation:.8 brightness:1.0 alpha:1.0];
}

- (void)loadIncidents
{
    hud = [MBProgressHUD showHUDAddedTo:self.view animated:YES];
    hud.color = [UIColor flatBlackColor];
    hud.detailsLabelText = nil;
    hud.mode = MBProgressHUDModeIndeterminate;
    hud.labelText = @"Fetching incidents";
    
    [incidents removeAllObjects];
    
    NSDateFormatter* dateFormatter = [[NSDateFormatter alloc] init];
    [dateFormatter setDateFormat:@"MM-dd-yyyy"];
    
    NSString *start = [dateFormatter stringFromDate:startDate];
    NSString *end = [dateFormatter stringFromDate:endDate];
    
    NSString *types = [[self getCrimeTypeAbbreviations] componentsJoinedByString:@","];
    
    NSString *urlString = [NSString stringWithFormat:@"http://***REDACTED***/%@/%@/%@", start, end, types];
    
    NSURL *url = [NSURL URLWithString:urlString];
    NSURLRequest *request = [NSURLRequest requestWithURL:url];
    NSURLConnection *conn = [[NSURLConnection alloc] initWithRequest:request delegate:self];
    [conn start];
}

- (void)connection:(NSURLConnection *)connection didReceiveResponse:(NSURLResponse *)response {
    incidentsData = [[NSMutableData alloc] init];
}

- (void)connection:(NSURLConnection *)connection didReceiveData:(NSData *)data {
    [incidentsData appendData:data];
}

- (void)connectionDidFinishLoading:(NSURLConnection *)connection {
    NSError *jsonParsingError = nil;
        
    id object = [NSJSONSerialization JSONObjectWithData:incidentsData options:0 error:&jsonParsingError];
    
    if (jsonParsingError) {
        NSLog(@"JSON ERROR: %@", [jsonParsingError localizedDescription]);
    } else {
        NSMutableArray *data = object;
        
        for(NSData *i in data) {
            
            NSString *dateString = [i valueForKey:@"date"];
            NSDateFormatter *df = [[NSDateFormatter alloc] init];
            [df setFormatterBehavior:NSDateFormatterBehavior10_4];
            [df setDateFormat:@"yyyy-MM-dd'T'HH:mm:ss"];
            
            NSDate *convertedDate = [df dateFromString:dateString];
            NSDictionary *incident = @{
                                       @"title"         : [i valueForKey:@"type"],
                                       @"address"       : [i valueForKey:@"address"],
                                       @"description"   : [i valueForKey:@"description"],
                                       @"caseid"        : [i valueForKey:@"case"],
                                       @"date"          : [convertedDate timeAgo],
                                       @"latitude"      : [i valueForKey:@"latitude"],
                                       @"longitude"      : [i valueForKey:@"longitude"]
                                       };
            
            CLLocationCoordinate2D loc = CLLocationCoordinate2DMake([[incident valueForKey:@"latitude"] doubleValue], [[incident valueForKey:@"longitude"] doubleValue]);
            IncidentPin *pin = [[IncidentPin alloc] initWithIncident:incident coordinate:loc];
            
            [incidents addObject:pin];
        }
        
        [self mapCrimes];
    }
}

- (void)mapCrimes {
    for (id<MKAnnotation> annotation in mapView.annotations) {
        if(![annotation isKindOfClass:[MKUserLocation class]])
            [mapView removeAnnotation:annotation];
    }
            
    MKMapRect zoomRect = MKMapRectNull;
    
    for(IncidentPin *incident in incidents) {
        [mapView addAnnotation:incident];
        
        MKMapPoint annotationPoint = MKMapPointForCoordinate(incident.coordinate);
        MKMapRect pointRect = MKMapRectMake(annotationPoint.x, annotationPoint.y, 0.1, 0.1);
        zoomRect = MKMapRectUnion(zoomRect, pointRect);
    }
    
    [MBProgressHUD hideHUDForView:self.view animated:YES];
    
    [mapView setVisibleMapRect:zoomRect animated:YES];
}

- (MKAnnotationView *)mapView:(MKMapView *)mv viewForAnnotation:(id <MKAnnotation>)annotation {
    if ([annotation isKindOfClass:[IncidentPin class]]) {
        NSString *identifier = [NSString stringWithFormat:@"IncidentPin%@", [annotation title]];
        
        MKPinAnnotationView *annotationView = (MKPinAnnotationView *) [mv dequeueReusableAnnotationViewWithIdentifier:identifier];
        if (annotationView == nil) {
            annotationView = [[MKPinAnnotationView alloc] initWithAnnotation:annotation reuseIdentifier:identifier];
        } else {
            annotationView.annotation = annotation;
        }
        
        UIColor *color = [self getCrimeTypeColor:[annotation title]];
        annotationView.image = [ZSPinAnnotation pinAnnotationWithColor:color];
        
        annotationView.enabled = YES;
        annotationView.canShowCallout = YES;
        
        UIButton *goToDetail = [UIButton buttonWithType:UIButtonTypeDetailDisclosure];
        annotationView.rightCalloutAccessoryView = goToDetail;
        
        return annotationView;
    }
    
    return nil;
}


- (void)mapView:(MKMapView *)mapView annotationView:(MKAnnotationView *)view calloutAccessoryControlTapped:(UIControl *)control {
    if ([view.annotation isKindOfClass:[IncidentPin class]]) {
        UIBarButtonItem *newBackButton = [[UIBarButtonItem alloc] initWithTitle: @"Back" style: UIBarButtonItemStyleBordered target: nil action: nil];
        [[self navigationItem] setBackBarButtonItem: newBackButton];
        
        activePin = view.annotation;
        [self performSegueWithIdentifier:@"incidentDetailSegue" sender:self];
    }
}

@end

using System;
using System.Collections.Generic;
using System.Data.Services;
using System.Data.Services.Common;
using System.Linq;
using System.ServiceModel.Web;
using System.Web;
using Microsoft.Data.Services.Toolkit;
using System.Text;
using System.Data.Objects.DataClasses;
using System.Linq.Dynamic;

namespace Ahoy
{
    [EnableOutputCache(ExpiresInSeconds = 30)]
    public class DS : ODataService<AhoyEntities>
    {
		
        public static void InitializeService(DataServiceConfiguration config)
        {
            config.UseVerboseErrors = true;  
			
            config.SetEntitySetAccessRule("*", EntitySetRights.All);

            config.SetServiceOperationAccessRule("GetFlares", ServiceOperationRights.All);
            
            config.DataServiceBehavior.MaxProtocolVersion = DataServiceProtocolVersion.V2;
        }

        [WebGet(UriTemplate = "?nelatitude='{nelatitude}'&nelongitude='{nelongitude}'&swlatitude='{swlatitude}'&swlongitude='{swlongitude}'&launched='{launched}'&device='{device}'&types='{types}'")]
        public List<Flare> GetFlares(string nelatitude, string nelongitude, string swlatitude, string swlongitude, string launched, string device, string types)
        {
            if (nelatitude == null && nelongitude == null && swlatitude == null && swlongitude == null) throw new DataServiceException("Location not specified");
            
            AhoyEntities context = new Ahoy.AhoyEntities();

            //StringBuilder sb = new StringBuilder();

            DateTime dt = DateTime.Parse(launched);
			
			float lat1 = float.Parse(nelatitude);
			float lat2 = float.Parse(swlatitude);
			float long1 = float.Parse(nelongitude);
			float long2 = float.Parse(swlongitude);

            List<Flare> FlareList = context.Flares.Where(b => b.LaunchDT > dt).ToList<Flare>();

            if (device.Length > 0)
            {
                FlareList = FlareList.Where(x => x.Device == device).ToList<Flare>();
            }
            else
            {
                FlareList = FlareList.Where(b => ParseDouble(b.Latitude) < lat1 && ParseDouble(b.Latitude) > lat2 && ParseDouble(b.Longitude) < long1 && ParseDouble(b.Longitude) > long2).ToList<Flare>();
            }
			
			if (types.Length > 0) {
				string[] typeList = types.Split(',');
                string qry = "";
                foreach (string s in typeList)
                {
                    qry += "Type=" + s + " or ";
                }
                if (qry.Length > 4)
                    qry = qry.Substring(0, qry.Length - 4);

               FlareList = FlareList.AsQueryable<Flare>().Where(qry).ToList<Flare>();
			}
			
			return FlareList;
			
			//foreach (Flare f in FlareList) {
			//	sb.Append(f.ID.ToString() + "|");	
			//}

            //string str = sb.ToString();
            //return str;
        }
		
		
		
		[EdmFunction("AhoyModel", "ParseDouble")]
		public static double ParseDouble(string stringvalue)
		{
			return Double.Parse(stringvalue);
		}
    }
}

using System;
using System.Collections.Generic;
using System.Linq;
using System.Runtime.Serialization;
using System.ServiceModel;
using System.ServiceModel.Web;
using System.Text;
using System.IO;
using System.Net;
using HtmlAgilityPack;
using System.Text.RegularExpressions;

namespace CraigsData
{
    public class DS : IDS
    {
        public Objects.Post GetPostDetail(string link)
        {
            Objects.Post p = new Objects.Post();
            HtmlAgilityPack.HtmlDocument detailpage = new HtmlAgilityPack.HtmlDocument();
            detailpage.LoadUri(link);

            int startLine = detailpage.DocumentNode.SelectNodes("//h2").First().Line;
            int endLine = detailpage.DocumentNode.ChildNodes.Where(x => x.NodeType == HtmlNodeType.Comment && x.InnerHtml.Contains("END CLTAGS")).First().Line;

            StringBuilder sb = new StringBuilder();
            foreach (HtmlNode c in detailpage.DocumentNode.ChildNodes.Where(x => x.Line >= startLine && x.Line <= endLine))
                sb.Append(c.OuterHtml);
            p.Content = sb.ToString();

            if (detailpage.DocumentNode.SelectNodes("//table").Where(x => x.Attributes.Where(y => y.Name == "summary").Count() > 0 && x.Attributes["summary"].Value.ToString() == "craigslist hosted images").Count() > 0)
            {
                foreach (HtmlNode n in detailpage.DocumentNode.SelectNodes("//table").Where(x => x.Attributes.Where(y => y.Name == "summary").Count() > 0 && x.Attributes["summary"].Value.ToString() == "craigslist hosted images").First().DescendantNodes().Where(x => x.Name == "img"))
                {
                    Objects.PostImage pi = new Objects.PostImage();
                    pi.Link = n.Attributes["src"].Value.ToString();
                    p.PostImages.Add(pi);
                }
            }

            return p;
        }

        public List<Objects.Country> GetSites()
        {
            List<Objects.Country> countries = new List<Objects.Country>();

            HtmlAgilityPack.HtmlDocument page = new HtmlAgilityPack.HtmlDocument();
            page.LoadUri("http://www.craigslist.org/about/sites");
            foreach(HtmlNode country in page.DocumentNode.SelectNodes("//h1").Where(x => x.InnerText != ""))
            {
                Objects.Country c = new Objects.Country();
                c.Name = country.InnerText;
                
                foreach (HtmlNode state in country.NextSibling.NextSibling.DescendantNodes().Where(x => x.Name == "div" && x.Attributes.Where(y => y.Name == "class").Count() > 0 && x.Attributes["class"].Value.ToString() == "state_delimiter" && x.InnerText != ""))
                {
                    Objects.State s = new Objects.State();
                    s.Name = state.InnerText;

                    foreach (HtmlNode site in state.NextSibling.NextSibling.ChildNodes.Where(x => x.Name == "li"))
                    {
                        Objects.Site si = new Objects.Site();
                        si.Name = site.FirstChild.InnerText;
                        si.Value = site.FirstChild.Attributes["href"].Value.ToString().Split('.').First().Substring(7);
                        s.Sites.Add(si);
                    }
                    c.States.Add(s);
                }
                countries.Add(c);
            }

            return countries;
        }

        public List<Objects.Section> GetCategories(string sitename)
        {
            List<Objects.Section> sections = new List<Objects.Section>();

            HtmlAgilityPack.HtmlDocument page = new HtmlAgilityPack.HtmlDocument();
            page.LoadUri("http://" + sitename + ".craigslist.org/");
            foreach (HtmlNode tempSection in page.DocumentNode.SelectNodes("//h4").Where(x => x.InnerText != "" && x.Attributes.Where(y => y.Name == "class").Count() > 0 && x.Attributes["class"].Value.ToString() == "ban" && x.InnerText != "discussion forums"))
            {
                Objects.Section s = new Objects.Section();
                s.Name = tempSection.InnerText;

                foreach (HtmlNode tempCategory in tempSection.NextSibling.NextSibling.DescendantNodes().Where(x => x.Name == "li"))
                {
                    Objects.Category c = new Objects.Category();
                    c.Name = tempCategory.InnerText;
                    string v = tempCategory.FirstChild.Attributes["href"].Value.ToString();

                    if (v.Length >= 9 && v.Substring(0, 9) == "/cgi-bin/")
                        v = v.Substring(v.Length - 4);
                    
                    c.Value = v;
                    s.Categories.Add(c);
                }

                sections.Add(s);
            }

            return sections;
        }

        public List<Objects.Post> GetPosts(string sitename, string category)
        {
            List<Objects.Post> posts = new List<Objects.Post>();

            HtmlAgilityPack.HtmlDocument page = new HtmlAgilityPack.HtmlDocument();
            page.LoadUri("http://" + sitename + ".craigslist.org/" + category);
            foreach (HtmlNode tempPost in page.DocumentNode.SelectNodes("//p"))
            {
                if (tempPost.DescendantNodes().Where(x => x.Name == "a").Count() > 0)
                {
                    Objects.Post p = new Objects.Post();

                    HtmlNode a = tempPost.DescendantNodes().Where(x => x.Name == "a").First();
                    p.Title = a.InnerText;
                    p.Link = a.Attributes["href"].Value.ToString();

                    string price = tempPost.InnerText.Split('-')[1];
                    if (price.Contains('$'))
                        p.Price = price.Split('(')[0];
                       // p.Price = Regex.Replace(price, "[^.0-9]", "");

                    if (tempPost.DescendantNodes().Where(x => x.Name == "font").Count() > 0)
                    {
                        string l = tempPost.DescendantNodes().Where(x => x.Name == "font").First().InnerText;
                        l = l.Substring(2, l.Length - 3);
                        if (l != "")
                            p.Location = l;
                    }

                    DateTime d = DateTime.Today;
                    if (page.DocumentNode.SelectNodes("//h4").Where(x => x.Attributes.Where(y => y.Name == "class").Count() > 0 && x.Attributes["class"].Value.ToString() == "ban" && x.Line <= tempPost.Line).Count() > 0)
                    {
                        string t1 = page.DocumentNode.SelectNodes("//h4").Where(x => x.Attributes.Where(y => y.Name == "class").Count() > 0 && x.Attributes["class"].Value.ToString() == "ban" && x.Line <= tempPost.Line).Last().InnerText;
                        d = DateTime.Parse(t1.Split(' ')[1] + "/" + t1.Split(' ')[2] + "/" + DateTime.Today.Year.ToString());
                    }

                    p.Date = d;

                    if (tempPost.DescendantNodes().Where(x => x.Name == "span" && x.Attributes.Where(y => y.Name == "class").Count() > 0 && x.Attributes["class"].Value.ToString() == "p" && (x.InnerText == "pic" || x.InnerText == "img")).Count() > 0)
                    {
                        HtmlAgilityPack.HtmlDocument detailpage = new HtmlAgilityPack.HtmlDocument();
                        detailpage.LoadUri(p.Link);

                        int startLine = detailpage.DocumentNode.SelectNodes("//h2").First().Line;
                        int endLine = detailpage.DocumentNode.ChildNodes.Where(x => x.NodeType == HtmlNodeType.Comment && x.InnerHtml.Contains("END CLTAGS")).First().Line;

                        StringBuilder sb = new StringBuilder();
                        foreach (HtmlNode c in detailpage.DocumentNode.ChildNodes.Where(x => x.Line >= startLine && x.Line <= endLine))
                            sb.Append(c.OuterHtml);
                        p.Content = sb.ToString();

                        if (detailpage.DocumentNode.SelectNodes("//table").Where(x => x.Attributes.Where(y => y.Name == "summary").Count() > 0 && x.Attributes["summary"].Value.ToString() == "craigslist hosted images").Count() > 0)
                        {
                            foreach (HtmlNode n in detailpage.DocumentNode.SelectNodes("//table").Where(x => x.Attributes.Where(y => y.Name == "summary").Count() > 0 && x.Attributes["summary"].Value.ToString() == "craigslist hosted images").First().DescendantNodes().Where(x => x.Name == "img"))
                            {
                                Objects.PostImage pi = new Objects.PostImage();
                                pi.Link = n.Attributes["src"].Value.ToString();
                                p.PostImages.Add(pi);
                            }
                        }
                    }

                    posts.Add(p);
                }
            }

            return posts;
        }

        public CompositeType GetDataUsingDataContract(CompositeType composite)
        {
            if (composite == null)
            {
                throw new ArgumentNullException("composite");
            }
            if (composite.BoolValue)
            {
                composite.StringValue += "Suffix";
            }
            return composite;
        }
    }
}

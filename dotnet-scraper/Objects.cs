using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;

namespace CraigsData
{
    public class Objects
    {

        public class Country
        {
            //default constructor 
            public Country()
            {
            }

            public string Name { get; set; }

            private List<State> _States = new List<State>();
            public List<State> States
            {
                set { this._States = value; }
                get { return this._States; }
            }
        }

        public class State
        {
            //default constructor 
            public State()
            {
            }

            public string Name { get; set; }

            private List<Site> _Sites = new List<Site>();
            public List<Site> Sites
            {
                set { this._Sites = value; }
                get { return this._Sites; }
            }
        }

        public class Site
        {
            public string Name { get; set; }
            public string Value { get; set; }
        }





        public class Section
        {
            //default constructor 
            public Section()
            {
            }

            public string Name { get; set; }

            private List<Category> _Categories = new List<Category>();
            public List<Category> Categories
            {
                set { this._Categories = value; }
                get { return this._Categories; }
            }
        }

        public class Category
        {
            //default constructor 
            public Category()
            {
            }

            public string Name { get; set; }
            public string Value { get; set; }
        }



        public class Post
        {
            //default constructor 
            public Post()
            {
            }

            public string Title { get; set; }
            public string Link { get; set; }
            public string Price { get; set; }
            public string Location { get; set; }
            public DateTime Date { get; set; }
            public string Content { get; set; }

            private List<PostImage> _PostImages = new List<PostImage>();
            public List<PostImage> PostImages
            {
                set { this._PostImages = value; }
                get { return this._PostImages; }
            }
        }

        public class PostImage
        {
            //default constructor 
            public PostImage()
            {
            }

            public string Link { get; set; }
        }
    }
}

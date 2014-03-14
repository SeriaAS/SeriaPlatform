Seria Platform WebApp
=====================

The Seria Platform WebApp component aims to simplify and standardize development of custom websites using Seria Platform. The built in
way of implementing functionality in Seria Platform is too complex and is not very friendly towards user interface customization.

Theory
------

1. Create a bootstrap.php file on your web root. Create a rewrite rule that redirects all page requests to that bootstrap.php file. The bootstrap.php
   file must include seria/main.php, and then call WebApp::bootstrap('/path/to/views');

2. Create a views/ folder, where you implement all design and design logic.

3. When you visit http://www.yoursite.com/, ./views/index.php will be executed in the context of a WebApp object. If you visit 
   http://www.yoursite.com/users/ the router will look for ./views/users.php, ./views/users/index.php and finally ./views/default.php which is the
   catch-all handler for all page views.

   It is very easy to make more advanced rewrites by using the filename _ as a wildcard. For example ./views/user/_/profile.php will match any
   url matching /user/*/profile, for exampe http://www.yoursite.com/user/37/profile/. The number 37 is accessible through $_GET["_"][0].

Traditional Reusable Design
---------------------------

Traditionally, there are a few popular methods for reusing template code accross your website:

1. Include top/bottom. You simply create a top.php and a bottom.php file that you include() wherever you need them.

   This method is simple, and it works. The problem is that it is messy.

2. Template inheritance. You create a "master template" that sub views inherit from. In the master template, you declare blocks that a sub view can
   override.

   This method is quite simple, and it can be used in some clever ways; a template can extend a template, that in turn extends another parent
   template etc. The problem here is that your sometimes you want a URL to render the entire page, and other times you want to use AJAX to load
   only a portion of the page - and this would involve implementing stuff twice, or have more logic in your templates, e.g. if(!empty($_GET['ajax'])).

3. Include the contents. Many sites do something similar to `include('contents/'.$_GET['page'].'.php);`. 

4. The controller decides which variables are used, and then decides which template to use to render these variables. This method can be used in
   combination with either option 1 or option 2.

   The problem with this method is that it does not really separate presentation from design. If the designer decides to remove some information
   that the controller injects into the template, your controller will still continue fetching that stuff from the database - wasting resources.
   If the designer wants to show more information, you need to update your controller to make that information available.

Our Solution
------------

Our solution is somewhat similar to the "include the contents" method above (3). It is a very powerful method, which unfortunately in many cases leads
to security problems. We replace the include() with a special "sub request" mechanism. You simply call `WebApp::exec('/contents/'.WebApp::$requestUrl)`.
The template code in /contents/ will percieve this as a completely new request and handle it appropriately. The request will actually be much faster than
a real full HTTP request, as it happens "internally" without the overhead of bootstrapping the PHP interpreter.


$contents = WebApp::exec('/path/to/url?arg1=123', function($contents) {
	
});
echo strtoupper($contents);






Whenever you want to create a dynamic piece of information to display on your website, you designate a URL for that piece of information. For example,
the contents for the frontpage should be placed in ./views/contents/index.php. If the browser visits http://www.yoursite.com/contents/, it will only
receive the contents of your website - without menus and blocks.

If you need a "user block" to present in your sidebar, you should give it a nice url such as http://www.yoursite.com/blocks/user/profile. If you fetch
this block via AJAX, that block will send appropriate caching headers to the browser, allowing it to be cached privately for that user. The contents
block will have public cacheability and perhaps a very long time to live.

Once you've created the dynamic components that you wish to include in your website, you create one or more master templates that will handle the
layout. So the frontpage design would be ./views/index.php. The frontpage will create *sub requests* to for example /contents/, and render it.

If you're making an intranet section for your site, you would create a ./views/intranet/default.php handler that will match every single request that
starts with http://www.yoursite.com/intranet/. The ./views/intranet/default.php template could fetch its content from whichever "internal" url you
want.

Benefits
--------


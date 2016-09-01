# Bonnier Publications - WordPress WhiteAlbum OAuth plugin

This plugin enables your WordPress site to integrate with the WA user base.
It does it by integrating with the WA OAuth API, and giving you a set of
api functions that you may call in your theme to conditionally lock content from
unauthorized users.

### Requirements

- WordPress 4.4 or higher
- WP REST API must be installed and activated version 2.0 or higher http://v2.wp-api.org/
- Language support (Optional) Polylang plugin must installed and activated version 1.8.4 or higher
- PHP 5.6 or higher

### Installation/Configuration

Install through composer:

``` bash
composer require benjaminmedia/wp-wa-oauth
```

Download lastest release from: https://github.com/BenjaminMedia/wp-wa-oauth/releases
And unzip and place in your /wp-content/plugins directory.

#### Getting credentials and Configuration

Once you have installed and activated the plugin then make sure that you have a set of api credentials.
If you do not have a set of credentials then you should contact Bonnier to get them.
You need to have a set of credentials for each domain on your site.
Before credentials can be generated you must inform Bonnier of the redirect URI that should work with the credentials.
This must be in the following format http://|https://[your-domain].[your-country]/wp-json/bp-wa-oauth/v1/oauth/login

Example: http://digitalfoto.dk/wp-json/bp-wa-oauth/v1/oauth/login

Other than the redirect URI you should also tell them which WA userbase you want to
authenticate against ie: http://woman.dk or another WA site.

Once you have your credentials log in to your WP admin panel and go to the menu
settings->Wp Wa Oauth: here you must enter each set of credentials you recieved.

Here you also have the option to Globally enable or disable the login for each domain.
Furthermore you may also select a Globally required user role level.

If you have multiple languages versions of the site, make sure that you use the Polylang plugin.

If you wish specific pages to be unlocked or require another user role than the Globally set.
Then you may visit the the edit page for each page/post and here you will find a meta
box called: 'WA OAuth locked content'. Any setting you make here will override
the Global settings you have set.

#### Creating local users

As of version 1.1.0 and up the plugin now supports creating local users.
This means that you from the plugin settings page can toggle wether you
want the plugin to create WordPress users after they have logged in.

If you enable this feature you may also choose an option wether the WordPress
user should be automatically logged in to WordPress upon authenticating with WA.

Users will be given custom roles created from the roles that WA delivers: currently
this amounts to two roles ```users``` and ```subscribers```. To avoid conflicts
with existing WordPress roles the role names will be prefixed with ```bp_wa_```
meaning the final role names will be ```bp_wa_users``` and ```bp_wa_subscribers```.

Both roles will be given only the read access this, will allow users to edit/view their profile and nothing in WordPress and nothing else.

###### Customizing role capabilities:
You may override the default capabilities of the roles created you should add
the following filters to a plugin or your functions.php file.

``` php
// To override the default capabilities you should implement a filter like so

add_filter('bp_wa_users_capabilities', function($default) {
	return array_merge($default, ['edit_posts' => false]);
});

// you can either extend the default capabilities by doing an array merge or,
// you can override the capabilities completely by returning a new array like so

add_filter('bp_wa_subscribers_capabilities', function($default) {
	return ['edit_posts' => true];
});

// note the filter follows a [role_name].[_capabilities] format

```

#### User update callbacks

If you have the create local users option enabled, then what might happen is
that the user will update their profile on the Main WhiteAlbum site. This,
could potentially mean that the local user info that your have in your application
can come out of sync. In order to counter this, WhiteAlbum has a callback feature,
which means they can call your application each time there is a change to a user
that has been logged in to your application.

The Plugin automatically setups the callback route and will also handle the update
of the user information itself, but before WhiteAlbum can call your app.
They need to be informed of your callback url. The callback uri is generated from the following format:
http://|https://[your-domain].[your-country]/wp-json/bp-wa-oauth/v1/oauth/callback

Example url: http://test.komputer.dk/wp-json/bp-wa-oauth/v1/oauth/callback

Your should give this url to Bonnier when requesting the credentials, then we
will setup the callbacks.

Note: the callback can only work so long as your user has been logged into the application for the last 24 hours. Otherwise the local user information,
will not get updated, until the next time they login to your application.

### Usage Example:

The following code snippet shows an Example in php that may be used in your theme template files.

``` php

<!-- 	
	First we get an instance of the bp-wa-oauth plugin by calling bp_wa_oauth(),
	once we have an instance we can call the is_authenticated() function.
	This function checks if the user is logged in,
	and if they have access to the current page/post.
	You may pass an optional $postId variable to the function,
	if you wish to check against a specific page/post.
-->

<?php if( bp_wa_oauth()->is_authenticated() ) { ?>

	<!-- 	
		If we are authenticated;
		then we can get the current user info by calling: get_user()
		get_user() returns either a WP_User object or a stdClass if the create
		local user option is disabled.
	-->

		<?php echo bp_wa_oauth()->get_user()->first_name; ?>

		<!-- 	
			You can add logout buttons to trigger a logout, note this will not destroy
			your WhiteAlbum session. It will only log you out of the current site.
		-->
		<button class="bp-wa-oauth-logout">login and return here</button>
		<button class="bp-wa-oauth-logout" data-bp-wa-oauth-redirect="/some/url" >login and redirect to specific url</button>

		<!-- 	
			If we are not authenticated;
			then we can check wether to display the login buttons or not,
			by checking if the current page is locked.
			This i done by calling the is_locked function, like the is_authenticated()
			the function will attempt to check the current page/post, but also accepts
			an optional parameter $postId if you wish to check against a specific page/post.
		-->

<?php } elseif( bp_wa_oauth()->is_locked() ) { ?>

		<!-- 	
			On clicking this button the user will be redirected to login screen;
			and returned to the same page on login completion
		-->
		<button class="bp-wa-oauth-login" >login current page</button>

		<!-- 	
			On clicking this button the user will be redirected to login screen;
			and returned to the url provided in the attr: 'data-bp-wa-oauth-redirect'
		-->
		<button class="bp-wa-oauth-login" data-bp-wa-oauth-redirect="/test/tasker/akvis/tag-ny-test-dk-artikel-test-om-den-kommer">login and redirect to specific url</button>

		<!-- 	
			Notice how each button has a class "bp-wa-oauth-login",
			this class is what triggers the login JavaScript,
			any element that has this class attached will be clickable;
			and will once clicked, trigger the login flow.
		-->

<?php } ?>

```

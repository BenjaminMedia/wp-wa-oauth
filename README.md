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
	-->

		<?php echo bp_wa_oauth()->get_user()->first_name; ?>

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

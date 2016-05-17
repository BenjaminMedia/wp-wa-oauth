# Bonnier Publications - WordPress WhiteAlbum OAuth plugin

### Requirements

- WordPress 4.4 or higher
- WP REST API must be installed and activated version 2.0 or higher http://v2.wp-api.org/
- Language support (Optional) Polylang plugin must installed and activated version 1.8.4 or higher

### Installation

Todo: write composer install stuff

Once you have installed and activated the plugin then make sure that you have a set of api credentials.
If you do not have a set of credentials then you should contact Bonnier to get them.
You need to have a set of credentials for each domain on your site.
Before credentials can be generated you must inform Bonnier of the URI that should work with the credentials.
This must be in the following format http://|https://[your-domain].[your-country]/wp-json/bp-wa-oauth/v1/oauth/login

Example: http://digitalfoto.dk/wp-json/bp-wa-oauth/v1/oauth/login

### Usage:

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

<?php } ?>

```

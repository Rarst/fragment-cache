# Description

Fragment Cache is a WordPress plugin for partial and async caching of heavy front-end elements. It currently supports caching navigation menus, widgets, and galleries.

Caching is built on top of transients API (with enhancements provided by TLC Transients library), provides soft expiration and transparent object cache support.

# Installation

Fragment Cache is a Composer package and can be installed in plugin directory via:

    php composer.phar create-project rarst/fragment-cache --repository-url=http://rarst.net

# Frequently Asked Questions

## Why fragments don't recognize logged in users / current page / etc?

Fragment Cache implements soft expiration - when fragments expire, they are regenerated asynchronously and do not take time in front end page load. The side effect of this is that it is impossible to preserve context precisely and in generic way.

Fragments that must be aware of users or other context information should be excluded from caching or handled by custom implementation, that properly handles that specific context.

## How to disable caching?

Caching for the fragment type can be disabled by manipulating main plugin object:

    global $fragment_cache;

    unset( $fragment_cache['widget'] ); // completely remove handler, only use before init

	// or

    $fragment_cache['widget']->disable(); // disable, use after init

Caching for individual fragments can be disabled by using `fc_skip_cache` hook, for example:

    add_action( 'fc_skip_cache', function ( $skip, $type, $name, $args, $salt ) {
    
    	if ( 'widget' == $type && is_a( $args['callback'][0], 'WP_Widget_Meta' ) )
    		return true;
    
    	return $skip;
    }, 10, 5 );

# Changelog

## 1.1

 - anonymized fragment generation in front end for consistency
 - added `fc_skip_cache` filter so caching can be disabled for individual fragments
 - updated documentation 

## 1.0

 - initial release

# License Info

Fragment Cache own code is licensed under GPLv2+ and it makes use of code from:

 - Composer (MIT)
 - Pimple (MIT)
 - TLC Transients (GPLv2+)
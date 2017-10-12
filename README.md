# Fragment Cache
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Rarst/fragment-cache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Rarst/fragment-cache/?branch=master)

Fragment Cache is a WordPress plugin for partial and async caching of heavy front-end elements. It currently supports caching navigation menus, widgets, and galleries.

Caching is built on top of transients API (with enhancements provided by TLC Transients library), provides soft expiration and transparent object cache support.

# Installation

Download plugin archive from [releases section](https://github.com/Rarst/fragment-cache/releases).

Or install in plugin directory via [Composer](https://getcomposer.org/):

    composer create-project rarst/fragment-cache --no-dev

# Frequently Asked Questions

## Why fragments don't recognize logged in users / current page?

Fragment Cache implements soft expiration - when fragments expire, they are regenerated asynchronously and do not take time in front end page load. The side effect is that it is impossible to preserve context precisely and in generic way.

Fragments that must be aware of users or other context information should be excluded from caching or handled by custom implementation, that properly handles that specific context.

## How to disable caching?

### Disable handler

Caching for the fragment type can be disabled by manipulating main plugin object:

```php
global $fragment_cache;

// completely remove handler, only use before init
unset( $fragment_cache['widget'] );

// or disable handler, use after init
$fragment_cache['widget']->disable();
```

### Skip individual fragments

Caching for individual fragments can be disabled by using `fc_skip_cache` hook.

```php
add_filter( 'fc_skip_cache', function ( $skip, $type, $name, $args, $salt ) {

	// Widget by class.
	if ( 'widget' === $type && is_a( $args['callback'][0], 'WP_Widget_Meta' ) ) {
		return true;
	}

	// Menu by theme location.
	if ( 'menu' === $type && isset( $args['theme_location'] ) && 'header' === $args['theme_location'] ) {
		return true;
	}

	// Menu by name.
	if ( 'menu' === $type && isset( $args['menu'] ) ) {

		if ( 'Menu with login' === $args['menu'] ) {
			return true;
		}

		if ( is_a( $args['menu'], 'WP_Term' ) && 'Menu with login' === $args['menu']->name ) {
			return true;
		}
	}

	// Gallery by ID of post.
	if ( 'gallery' === $type && 123 === $args['post_id'] ) {
		return true;
	}

	return $skip;
}, 10, 5 );
```

# License Info

Fragment Cache own code is licensed under GPLv2+ and it makes use of code from:

 - Composer (MIT)
 - Pimple (MIT)
 - TLC Transients (GPLv2+)
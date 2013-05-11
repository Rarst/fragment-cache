<?php
/*
Plugin Name: Fragment Cache
Plugin URI: https://bitbucket.org/Rarst/fragment-cache
Description: Cache generated HTML for resource intensive and time consuming components.
Author: Andrey "Rarst" Savchenko
Version: 
Author URI: http://www.rarst.net/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Rarst\Fragment_Cache;

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) )
	require __DIR__ . '/vendor/autoload.php';

global $fragment_cache;

$fragment_cache = new Plugin(
	array(
		'timeout'       => HOUR_IN_SECONDS,
		'update_server' => new \TLC_Transient_Update_Server(),
	)
);

$fragment_cache->add_fragment_handler( 'menu', 'Rarst\\Fragment_Cache\\Menu_Cache' );
$fragment_cache->add_fragment_handler( 'widget', 'Rarst\\Fragment_Cache\\Widget_Cache' );
$fragment_cache->add_fragment_handler( 'gallery', 'Rarst\\Fragment_Cache\\Gallery_Cache' );

$fragment_cache->run();
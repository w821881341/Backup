<?php
/*
 WARNING: This is a core Generate file. DO NOT edit
 this file under any circumstances. Please do all modifications
 in the form of a child theme.
 */

/**
 * Manages addon functionality if they aren't installed
 *
 * This file is a core Generate file and should not be edited.
 *
 * @package  WordPress
 * @author   Thomas Usborne
 * @license  http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link     http://www.generatepress.com
 */

require get_template_directory() . '/inc/addons/typography.php';
require get_template_directory() . '/inc/addons/colors.php';
require get_template_directory() . '/inc/addons/spacing.php';

/** 
 * Check to see if there's any addons not already activated
 * @since 1.0.9
 */
function generate_addons_available()
{
	if ( defined( 'GP_PREMIUM_VERSION' ) )
		return false;
		
	if ( !function_exists('generate_fonts_setup') ||
		!function_exists('generate_colors_setup') ||
		!function_exists('generate_backgrounds_setup') ||
		!function_exists('generate_page_header') ||
		!function_exists('generate_insert_import_export') ||
		!function_exists('generate_copyright_option') ||
		!function_exists('generate_disable_elements') ||
		!function_exists('generate_blog_get_defaults') ||
		!function_exists('generate_hooks_setup') ||
		!function_exists('generate_secondary_nav_setup') ||
		!function_exists('generate_spacing_setup')) :
			return true;
		else :
			return false;
		endif;
}

/** 
 * Check to see if no addons are activated
 * @since 1.0.9
 */
function generate_no_addons()
{
	if ( defined( 'GP_PREMIUM_VERSION' ) )
		return false;
		
	if ( !function_exists('generate_fonts_setup') &&
		!function_exists('generate_colors_setup') &&
		!function_exists('generate_backgrounds_setup') &&
		!function_exists('generate_page_header') &&
		!function_exists('generate_insert_import_export') &&
		!function_exists('generate_copyright_option') &&
		!function_exists('generate_disable_elements') &&
		!function_exists('generate_blog_get_defaults') &&
		!function_exists('generate_hooks_setup') &&
		!function_exists('generate_secondary_nav_setup') &&
		!function_exists('generate_spacing_setup')) :
			return true;
		else :
			return false;
		endif;
}
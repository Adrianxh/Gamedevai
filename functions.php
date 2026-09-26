<?php
/**
 * GameDev AI Hub Child — functions.
 *
 * GeneratePress loads this child theme's style.css by itself, so there is
 * no stylesheet enqueue here.
 *
 * Navbar brand: the GameDev AI Hub logo and wordmark replace the
 * GeneratePress logo and site title in every header.
 *
 * Single posts (the "Docs Split" layout):
 *  - single posts always use the no-sidebar layout
 *  - "~/category/subcategory" path above the title
 *  - the post's manual excerpt as a subtitle under the title
 *  - avatar, updated date and reading time in the post meta
 *  - an "On this page" table of contents in its own column
 *  - anchor ids and "#" links on H2/H3 headings
 *  - reading progress per section, a "4/8 · 42%" counter and minutes left
 *  - current-section highlighting and Copy buttons on code blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A post needs at least this many H2/H3 headings to get a table of contents.
 */
const GDAIH_TOC_MIN_HEADINGS = 2;

/**
 * Navbar logo image.
 */
const GDAIH_LOGO_URL = 'https://gamedevaihub.com/wp-content/uploads/2026/09/gamedevailogo.png';

/**
 * True while GeneratePress renders the main post of a single post page.
 */
function gdaih_is_docs_post() {
	return is_singular( 'post' ) && in_the_loop() && get_the_ID() === get_queried_object_id();
}


/* -------------------------------------------------------------------------
 * Brand: logo + wordmark in the navbar
 * ---------------------------------------------------------------------- */

/**
 * The logo markup: the logo image and the wordmark.
 */
function gdaih_brand_markup( $tag = 'p' ) {
	return sprintf(
		'<%1$s class="main-title gd-brand"><a href="%2$s" rel="home"><img class="gd-brand__mark" src="%3$s" width="44" height="44" alt="" decoding="async"><span class="gd-brand__word">GameDev AI Hub</span></a></%1$s>',
		tag_escape( $tag ),
		esc_url( home_url( '/' ) ),
		esc_url( GDAIH_LOGO_URL )
	);
}

/**
 * Replace GeneratePress's logo with the brand. Runs for every header that
 * prints a logo (desktop navigation, site header, GP Premium mobile header).
 */
function gdaih_brand_logo( $output = '' ) {
	$GLOBALS['gdaih_brand_printed'] = true;
	$GLOBALS['gdaih_logo_just_printed'] = true;
	return gdaih_brand_markup();
}
add_filter( 'generate_logo_output', 'gdaih_brand_logo', 20 );
add_filter( 'generate_navigation_logo_output', 'gdaih_brand_logo', 20 );
add_filter( 'generate_mobile_header_logo_output', 'gdaih_brand_logo', 20 );

/**
 * Replace the site title with the brand, unless the brand was just printed
 * as the logo in the same header (logo and title both switched on).
 */
function gdaih_brand_title( $output = '' ) {
	if ( ! empty( $GLOBALS['gdaih_logo_just_printed'] ) ) {
		$GLOBALS['gdaih_logo_just_printed'] = false;
		return '';
	}
	$GLOBALS['gdaih_brand_printed'] = true;
	return gdaih_brand_markup();
}
add_filter( 'generate_site_title_output', 'gdaih_brand_title', 20 );

/**
 * Fallback: if the logo and site title are both switched off in the
 * Customizer, print the brand at the start of the navigation.
 */
function gdaih_brand_fallback() {
	if ( ! empty( $GLOBALS['gdaih_brand_printed'] ) ) {
		return;
	}
	$GLOBALS['gdaih_brand_printed'] = true;
	echo '<div class="navigation-branding">' . gdaih_brand_markup() . '</div>'; // Escaped in gdaih_brand_markup().
}
add_action( 'generate_inside_navigation', 'gdaih_brand_fallback', 100 );


/**
 * Navbar styles, printed in <head> after every stylesheet.
 *
 * The closed navbar is a floating white card: logo + wordmark, inline
 * links with an underlined current page, and a "Search docs…" pill.
 * Below 1025px the links move into the existing full-screen menu behind
 * the Menu button.
 *
 * Every rule is limited to the header navigation while it is closed
 * (:not(.toggled)), so the open full-screen menu keeps its own styles
 * from style.css. Printing last also keeps the first frame correct when
 * optimisation plugins paint with an old cached copy of style.css.
 */
function gdaih_critical_navbar_css() {
	$css = <<<'CSS'
:root{--gdn-ink:#0f2029;--gdn-link:#34444c;--gdn-muted:#7d8a90;--gdn-line:#e4e7e8;--gdn-field:#f7f8f8;--gdn-hover:#eef1f1;--gdn-logo:44px}
html body .site-header{background:transparent!important;background-image:none!important;border:0;box-shadow:none}
html body :is(#site-navigation,#mobile-header):not(.toggled){padding:16px clamp(12px,2.4vw,32px) 0!important;color:var(--gdn-ink);background:transparent!important;background-image:none!important;border:0!important;box-shadow:none!important}
html body :is(#site-navigation,#mobile-header):not(.toggled)>.inside-navigation{box-sizing:border-box;display:flex;flex-wrap:nowrap;align-items:center;gap:12px;width:100%;max-width:1240px;min-height:76px;margin:0 auto;padding:12px clamp(16px,2vw,26px)!important;background:#fff!important;border:1px solid rgb(15 32 41/5%)!important;border-radius:18px!important;box-shadow:0 1px 2px rgb(15 32 41/4%),0 14px 38px -14px rgb(15 32 41/18%)!important}

html body :is(#site-navigation,#mobile-header):not(.toggled) .navigation-branding{display:flex;align-items:center;order:0;min-width:0;margin:0 clamp(12px,4vw,64px) 0 0}
html body :is(#site-navigation,#mobile-header):not(.toggled) .gd-brand{margin:0!important;padding:0;font-size:clamp(19px,1.75vw,23px)!important;font-weight:800!important;line-height:1.1!important;letter-spacing:-.035em}
html body :is(#site-navigation,#mobile-header):not(.toggled) .gd-brand a{display:flex;align-items:center;gap:14px;color:var(--gdn-ink)!important;text-decoration:none!important;white-space:nowrap}
html body :is(#site-navigation,#mobile-header):not(.toggled) .gd-brand__mark{flex-shrink:0;width:auto;height:var(--gdn-logo);border-radius:0}
html body :is(#site-navigation,#mobile-header,.site-header) :is(.main-title a,.main-nav a,.mobile-menu)::before,
html body :is(#site-navigation,#mobile-header,.site-header) :is(.main-title a,.main-nav>ul>li>a,.mobile-menu)::after{content:none!important}

html body :is(#site-navigation,#mobile-header):not(.toggled) .main-nav{display:none!important;order:1}
html body :is(#site-navigation,#mobile-header):not(.toggled) .menu-bar-items{display:flex;align-items:center;gap:8px;order:2;margin:0 0 0 auto}
html body :is(#site-navigation,#mobile-header):not(.toggled) .menu-bar-item>a{display:flex;align-items:center;justify-content:center;min-width:44px;height:44px;padding:0;color:var(--gdn-ink)!important;background:var(--gdn-field)!important;border:1px solid var(--gdn-line);border-radius:999px;line-height:1;transition:background-color .17s}
html body :is(#site-navigation,#mobile-header):not(.toggled) .menu-bar-item>a:hover{background:var(--gdn-hover)!important}
html body :is(#site-navigation,#mobile-header):not(.toggled) .menu-bar-item .gp-icon{display:flex;top:0;margin:0}
html body :is(#site-navigation,#mobile-header):not(.toggled) .menu-bar-item svg{width:20px;height:20px;top:0}
html body :is(#site-navigation,#mobile-header):not(.toggled) :is(.search-item>a:not(.close-search),[data-gpmodal-trigger="gp-search"]){justify-content:flex-start;gap:12px;width:212px;padding:0 18px}
html body :is(#site-navigation,#mobile-header):not(.toggled) :is(.search-item>a:not(.close-search),[data-gpmodal-trigger="gp-search"])::after{content:"Search docs\2026";overflow:hidden;color:var(--gdn-muted);font-size:15px;font-weight:400;white-space:nowrap}
html body :is(#site-navigation,#mobile-header):not(.toggled) .menu-toggle{display:inline-flex!important;align-items:center;gap:8px;order:3;height:44px;margin:0;padding:0 16px;color:var(--gdn-ink)!important;background:var(--gdn-field)!important;border:1px solid var(--gdn-line)!important;border-radius:999px!important;font-size:15px;line-height:1}
html body :is(#site-navigation,#mobile-header):not(.toggled) .menu-toggle:hover{background:var(--gdn-hover)!important}

@media (min-width:1025px){
html body #site-navigation:not(.toggled) .menu-toggle{display:none!important}
html body #site-navigation:not(.toggled) .main-nav{display:block!important;position:static!important;inset:auto!important;width:auto!important;height:auto!important;margin:0 auto 0 0;padding:0!important;overflow:visible!important;background:transparent!important;transform:none!important;opacity:1!important;visibility:visible!important}
html body #site-navigation:not(.toggled) .main-nav>ul{display:flex;align-items:center;gap:20px;margin:0;padding:0;list-style:none}
html body #site-navigation:not(.toggled) .main-nav>ul>li>a{display:flex;align-items:center;min-height:52px;padding:0 8px!important;color:var(--gdn-link)!important;background:linear-gradient(var(--gdn-ink),var(--gdn-ink)) center bottom 4px/0 2px no-repeat!important;border:0!important;border-radius:0!important;box-shadow:none!important;font-size:16px;font-weight:400;line-height:1.2!important;text-decoration:none!important;transition:color .17s,background-size .17s}
html body #site-navigation:not(.toggled) .main-nav>ul>li:is(:hover,:focus-within)>a{color:var(--gdn-ink)!important}
html body #site-navigation:not(.toggled) .main-nav>ul>li:is(.current-menu-item,.current-menu-ancestor,.current_page_item,.current_page_ancestor)>a{color:var(--gdn-ink)!important;font-weight:500;background-size:100% 2px!important}
html body #site-navigation:not(.toggled) .main-nav ul ul{padding:7px;background:#fff!important;border:1px solid var(--gdn-line);border-radius:12px;box-shadow:0 14px 38px -14px rgb(15 32 41/18%)}
html body #site-navigation:not(.toggled) .main-nav ul ul a{padding:10px 13px;color:var(--gdn-link)!important;background:transparent!important;border-radius:8px;line-height:1.35}
html body #site-navigation:not(.toggled) .main-nav ul ul li:hover>a{color:var(--gdn-ink)!important;background:var(--gdn-hover)!important}
}
@media (max-width:1100px){
html body :is(#site-navigation,#mobile-header):not(.toggled) :is(.search-item>a:not(.close-search),[data-gpmodal-trigger="gp-search"]){justify-content:center;width:44px;padding:0}
html body :is(#site-navigation,#mobile-header):not(.toggled) :is(.search-item>a:not(.close-search),[data-gpmodal-trigger="gp-search"])::after{content:none}
}
@media (max-width:600px){
:root{--gdn-logo:34px}
html body :is(#site-navigation,#mobile-header):not(.toggled){padding:10px 10px 0!important}
html body :is(#site-navigation,#mobile-header):not(.toggled)>.inside-navigation{gap:8px;min-height:62px;padding:9px 10px 9px 14px!important;border-radius:14px!important}
html body :is(#site-navigation,#mobile-header):not(.toggled) .navigation-branding{margin-right:0}
html body :is(#site-navigation,#mobile-header):not(.toggled) .gd-brand{font-size:18px!important}
html body :is(#site-navigation,#mobile-header):not(.toggled) .gd-brand a{gap:10px}
html body :is(#site-navigation,#mobile-header):not(.toggled) .menu-toggle{justify-content:center;width:44px;padding:0}
html body :is(#site-navigation,#mobile-header):not(.toggled) .menu-toggle .mobile-menu{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);white-space:nowrap}
}
CSS;

	echo '<style id="gdaih-critical-navbar">' . $css . '</style>' . "\n"; // Static CSS, no user input.
}
add_action( 'wp_head', 'gdaih_critical_navbar_css', 999 );


/* -------------------------------------------------------------------------
 * Layout
 * ---------------------------------------------------------------------- */

/**
 * Single posts never show the widget sidebar; the TOC column replaces it.
 * This overrides the Customizer and the per-post Layout setting.
 */
function gdaih_single_post_layout( $layout ) {
	return is_singular( 'post' ) ? 'no-sidebar' : $layout;
}
add_filter( 'generate_sidebar_layout', 'gdaih_single_post_layout' );

/**
 * Mark the article so style.css can switch on the two-column layout.
 */
function gdaih_post_class( $classes, $class = '', $post_id = 0 ) {
	if ( is_singular( 'post' ) && (int) $post_id === get_queried_object_id() && gdaih_toc_items( $post_id ) ) {
		$classes[] = 'gd-has-toc';
	}
	return $classes;
}
add_filter( 'post_class', 'gdaih_post_class', 10, 3 );


/* -------------------------------------------------------------------------
 * Headings and table of contents
 * ---------------------------------------------------------------------- */

/**
 * Return a unique anchor id for a heading label.
 */
function gdaih_unique_id( $label, array &$used ) {
	$base = sanitize_title( $label );
	if ( '' === $base ) {
		$base = 'section';
	}
	$id = $base;
	$n  = 2;
	while ( isset( $used[ $id ] ) ) {
		$id = $base . '-' . $n++;
	}
	$used[ $id ] = true;
	return $id;
}

/**
 * Find H2/H3 headings in HTML, wherever they are nested.
 *
 * @return array[] Each item: level, id, label, offset (position in the HTML).
 */
function gdaih_parse_headings( $html ) {
	if ( ! preg_match_all( '/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return array();
	}

	// Reserve ids that headings already have (set in the block's HTML anchor field).
	$used = array();
	foreach ( $matches as $m ) {
		if ( ! empty( $m[2][0] ) && preg_match( '/\sid=(["\'])(.*?)\1/i', $m[2][0], $id_match ) ) {
			$used[ $id_match[2] ] = true;
		}
	}

	$items = array();
	foreach ( $matches as $m ) {
		$label = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $m[3][0] ) ) );
		if ( '' === $label ) {
			continue;
		}
		if ( ! empty( $m[2][0] ) && preg_match( '/\sid=(["\'])(.*?)\1/i', $m[2][0], $id_match ) ) {
			$id = $id_match[2];
		} else {
			$id = gdaih_unique_id( $label, $used );
		}
		$items[] = array(
			'level'  => (int) $m[1][0],
			'id'     => $id,
			'label'  => $label,
			'offset' => $m[0][1],
		);
	}
	return $items;
}

/**
 * Table-of-contents entries for a post (cached per request).
 *
 * Reads the post's blocks directly, so the TOC can be printed before the
 * content itself is rendered. Top-level entries get a reading time for
 * their whole section (the heading up to the next top-level heading).
 */
function gdaih_toc_items( $post_id = 0 ) {
	static $cache = array();

	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$items = array();
	$post  = get_post( $post_id );
	if ( $post && ! post_password_required( $post ) ) {
		$html  = do_blocks( $post->post_content );
		$items = gdaih_parse_headings( $html );

		// Section boundaries are the top-level entries (H2, or an H3 before any H2).
		$top = array();
		foreach ( $items as $i => $item ) {
			if ( 2 === $item['level'] || ! $top ) {
				$top[] = $i;
			}
		}
		foreach ( $top as $n => $i ) {
			$start = $items[ $i ]['offset'];
			$end   = isset( $top[ $n + 1 ] ) ? $items[ $top[ $n + 1 ] ]['offset'] : strlen( $html );
			$words = str_word_count( wp_strip_all_tags( substr( $html, $start, $end - $start ) ) );

			$items[ $i ]['top']     = true;
			$items[ $i ]['minutes'] = max( 1, (int) round( $words / 225 ) );
		}
	}
	if ( count( $items ) < GDAIH_TOC_MIN_HEADINGS ) {
		$items = array();
	}

	$cache[ $post_id ] = $items;
	return $items;
}

/**
 * Give H2/H3 headings in the rendered post the same ids the TOC links to,
 * and add a "#" link after each one.
 */
function gdaih_heading_anchors( $content ) {
	if ( ! gdaih_is_docs_post() ) {
		return $content;
	}
	$items = gdaih_toc_items();
	if ( ! $items ) {
		return $content;
	}

	// Match headings to TOC entries by their slug, in order.
	$queue = array();
	$used  = array();
	foreach ( $items as $item ) {
		$queue[ sanitize_title( $item['label'] ) ][] = $item['id'];
		$used[ $item['id'] ]                          = true;
	}

	return preg_replace_callback(
		'/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is',
		function ( $m ) use ( &$queue, &$used ) {
			$attrs = isset( $m[2] ) ? $m[2] : '';
			$inner = $m[3];
			$label = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $inner ) ) );
			if ( '' === $label || false !== strpos( $inner, 'gd-anchor' ) ) {
				return $m[0];
			}

			if ( preg_match( '/\sid=(["\'])(.*?)\1/i', $attrs, $id_match ) ) {
				$id = $id_match[2];
			} else {
				$key   = sanitize_title( $label );
				$id    = ! empty( $queue[ $key ] ) ? array_shift( $queue[ $key ] ) : gdaih_unique_id( $label, $used );
				$attrs = ' id="' . esc_attr( $id ) . '"' . $attrs;
			}

			$anchor = '<a class="gd-anchor" href="#' . esc_attr( $id ) . '" aria-label="' . esc_attr( sprintf( __( 'Link to “%s”', 'gdaih' ), $label ) ) . '">#</a>';

			return '<h' . $m[1] . $attrs . '>' . $inner . $anchor . '</h' . $m[1] . '>';
		},
		$content
	);
}
add_filter( 'the_content', 'gdaih_heading_anchors', 20 );

/**
 * Print the TOC right after the post header. It stays outside the post
 * content, so its position never depends on how the content is built.
 *
 * Sections are numbered and show their reading time; the counter and the
 * "min left" line are updated by the script while reading.
 */
function gdaih_print_toc() {
	if ( ! gdaih_is_docs_post() ) {
		return;
	}
	$items = gdaih_toc_items();
	if ( ! $items ) {
		return;
	}

	$list     = '';
	$in_sub   = false;
	$open_li  = false;
	$sections = 0;
	$minutes  = 0;
	foreach ( $items as $item ) {
		$href = '#' . esc_attr( $item['id'] );
		$text = esc_html( $item['label'] );

		if ( empty( $item['top'] ) ) {
			if ( ! $in_sub ) {
				$list  .= '<ul>';
				$in_sub = true;
			}
			$list .= '<li><a href="' . $href . '">' . $text . '</a></li>';
			continue;
		}

		if ( $in_sub ) {
			$list  .= '</ul>';
			$in_sub = false;
		}
		if ( $open_li ) {
			$list .= '</li>';
		}
		++$sections;
		$minutes += $item['minutes'];
		$list    .= sprintf(
			'<li data-minutes="%1$d"><a href="%2$s"><span class="gd-toc__num">%3$s</span><span class="gd-toc__text">%4$s</span><span class="gd-toc__min">%1$dm</span></a>',
			(int) $item['minutes'],
			$href,
			sprintf( '%02d', $sections ),
			$text
		);
		$open_li = true;
	}
	$list .= ( $in_sub ? '</ul>' : '' ) . ( $open_li ? '</li>' : '' );

	$title = esc_html__( 'On this page', 'gdaih' );
	printf(
		'<nav class="gd-toc" aria-label="%1$s"><details class="gd-toc__box" open>'
		. '<summary class="gd-toc__title"><span>%2$s</span><span class="gd-toc__count">%3$s</span></summary>'
		. '<ol class="gd-toc__list">%4$s</ol>'
		. '<p class="gd-toc__foot"><span class="gd-toc__left">%5$s</span><a class="gd-toc__top" href="#">%6$s</a></p>'
		. '</details></nav>',
		esc_attr( $title ),
		$title,
		esc_html( sprintf( '0/%d · 0%%', $sections ) ),
		$list, // Escaped above.
		esc_html( sprintf( __( '~%d min read', 'gdaih' ), $minutes ) ),
		esc_html__( '↑ Top', 'gdaih' )
	);
}
add_action( 'generate_after_entry_header', 'gdaih_print_toc', 20 );


/* -------------------------------------------------------------------------
 * Post header: path, subtitle, meta
 * ---------------------------------------------------------------------- */

/**
 * "~/tutorials/godot" above the title, built from the post's deepest category.
 */
function gdaih_print_crumb() {
	if ( ! gdaih_is_docs_post() ) {
		return;
	}
	$categories = get_the_category();
	if ( empty( $categories ) ) {
		return;
	}

	$deepest = $categories[0];
	$depth   = count( get_ancestors( $deepest->term_id, 'category' ) );
	foreach ( $categories as $category ) {
		$category_depth = count( get_ancestors( $category->term_id, 'category' ) );
		if ( $category_depth > $depth ) {
			$deepest = $category;
			$depth   = $category_depth;
		}
	}

	$chain   = array_reverse( get_ancestors( $deepest->term_id, 'category' ) );
	$chain[] = $deepest->term_id;
	$parts   = array();
	foreach ( $chain as $term_id ) {
		$term = get_term( $term_id, 'category' );
		$link = $term && ! is_wp_error( $term ) ? get_term_link( $term ) : '';
		if ( ! $link || is_wp_error( $link ) ) {
			continue;
		}
		$parts[] = '<a href="' . esc_url( $link ) . '">' . esc_html( urldecode( $term->slug ) ) . '</a>';
	}

	if ( $parts ) {
		echo '<p class="gd-crumb">~/' . implode( '/', $parts ) . '</p>'; // Escaped above.
	}
}
add_action( 'generate_before_entry_title', 'gdaih_print_crumb', 5 );

/**
 * The post's manual excerpt (Post settings > Excerpt) as a subtitle.
 */
function gdaih_print_dek() {
	if ( ! gdaih_is_docs_post() || ! has_excerpt() ) {
		return;
	}
	echo '<p class="gd-dek">' . esc_html( wp_strip_all_tags( get_the_excerpt() ) ) . '</p>';
}
add_action( 'generate_after_entry_title', 'gdaih_print_dek', 5 );

/**
 * Meta order on single posts: author, date, reading time.
 */
function gdaih_header_meta_items( $items ) {
	if ( ! is_singular( 'post' ) ) {
		return $items;
	}
	$items = array_values( array_diff( (array) $items, array( 'reading-time' ) ) );
	if ( in_array( 'author', $items, true ) ) {
		$items = array_merge( array( 'author' ), array_values( array_diff( $items, array( 'author' ) ) ) );
	}
	$items[] = 'reading-time';
	return $items;
}
add_filter( 'generate_header_entry_meta_items', 'gdaih_header_meta_items' );

/**
 * Output the custom "reading-time" meta item.
 */
function gdaih_meta_item( $item ) {
	if ( 'reading-time' !== $item || ! is_singular( 'post' ) ) {
		return;
	}
	$words   = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) ) );
	$minutes = max( 1, (int) ceil( $words / 225 ) );
	/* translators: %d: minutes. */
	printf( '<span class="reading-time">%s</span>', esc_html( sprintf( __( '%d min read', 'gdaih' ), $minutes ) ) );
}
add_action( 'generate_post_meta_items', 'gdaih_meta_item' );

/**
 * "Updated <date>" when the post was edited after publishing.
 */
function gdaih_post_date( $output ) {
	if ( ! is_singular( 'post' ) ) {
		return $output;
	}
	$updated = get_the_modified_date( 'Y-m-d' ) !== get_the_date( 'Y-m-d' );
	$label   = $updated ? __( 'Updated %s', 'gdaih' ) : __( 'Published %s', 'gdaih' );
	$date    = $updated ? get_the_modified_date() : get_the_date();
	$iso     = $updated ? get_the_modified_date( 'c' ) : get_the_date( 'c' );

	return sprintf(
		'<span class="posted-on"><time class="entry-date %1$s" datetime="%2$s">%3$s</time></span> ',
		$updated ? 'updated' : 'published',
		esc_attr( $iso ),
		esc_html( sprintf( $label, $date ) )
	);
}
add_filter( 'generate_post_date_output', 'gdaih_post_date' );

/**
 * Author avatar in front of the name.
 */
function gdaih_author_avatar( $output ) {
	if ( ! is_singular( 'post' ) ) {
		return $output;
	}
	$avatar = get_avatar( get_the_author_meta( 'ID' ), 32, '', '', array( 'class' => 'gd-avatar' ) );
	if ( ! $avatar ) {
		return $output;
	}
	return preg_replace( '/<span class="byline">/', '<span class="byline">' . $avatar, $output, 1 );
}
add_filter( 'generate_post_author_output', 'gdaih_author_avatar' );


/* -------------------------------------------------------------------------
 * Front-end behaviour (single posts only, no library)
 * ---------------------------------------------------------------------- */

function gdaih_docs_script() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$script = <<<'JS'
(function () {
	var desktop = window.matchMedia('(min-width: 1024px)');
	var toc = document.querySelector('.gd-toc');
	var box = toc && toc.querySelector('.gd-toc__box');
	var content = document.querySelector('.gd-has-toc .entry-content');
	var items = [];
	var sections = [];
	var active = null;

	// Height of fixed or sticky bars at the top (admin bar, sticky navigation).
	function barsBottom() {
		var bars = [];
		document.querySelectorAll('#wpadminbar, .site-header, .main-navigation, #mobile-header').forEach(function (el) {
			var position = getComputedStyle(el).position;
			if (position === 'fixed' || position === 'sticky') bars.push(el.getBoundingClientRect());
		});
		bars.sort(function (a, b) { return a.top - b.top; });
		var bottom = 0;
		bars.forEach(function (r) {
			if (r.height && r.top <= bottom + 1 && r.bottom > bottom) bottom = r.bottom;
		});
		return bottom;
	}
	function targetOf(a) {
		return document.getElementById(decodeURIComponent(a.hash.slice(1)));
	}

	if (toc) {
		toc.classList.add('is-enhanced');
		items = Array.prototype.slice.call(toc.querySelectorAll('.gd-toc__list a[href^="#"]'))
			.map(function (a) { return { link: a, target: targetOf(a) }; })
			.filter(function (item) { return item.target; });
		sections = Array.prototype.slice.call(toc.querySelectorAll('.gd-toc__list > li'))
			.map(function (li) {
				var a = li.querySelector('a');
				return { li: li, target: a && targetOf(a), minutes: parseFloat(li.getAttribute('data-minutes')) || 1 };
			})
			.filter(function (section) { return section.target; });
		var count = toc.querySelector('.gd-toc__count');
		var left = toc.querySelector('.gd-toc__left');

		// Open rail on desktop, collapsed bar on smaller screens.
		box.open = desktop.matches;
		box.addEventListener('toggle', function () {
			if (desktop.matches && !box.open) box.open = true;
		});
		desktop.addEventListener('change', function (e) { box.open = e.matches; });
		toc.addEventListener('click', function (e) {
			if (!desktop.matches && e.target.closest('.gd-toc__list a')) box.open = false;
		});
		toc.querySelector('.gd-toc__top').addEventListener('click', function (e) {
			e.preventDefault();
			window.scrollTo({ top: 0 });
		});
	}

	var lastOffset = null;
	var lastWidth = null;
	var ticking = false;
	function update() {
		ticking = false;
		var offset = barsBottom() + 24;
		if (offset !== lastOffset) {
			document.body.style.setProperty('--gdh-sticky-top', offset + 'px');
			lastOffset = offset;
		}
		// Window width without the scrollbar, for the full-width layout.
		var width = document.documentElement.clientWidth;
		if (width !== lastWidth) {
			document.body.style.setProperty('--gdh-vw', width + 'px');
			lastWidth = width;
		}
		if (!items.length) return;

		var line = offset + 16; // Reading line, just below the top bars.
		var root = document.documentElement;
		var atEnd = window.innerHeight + window.scrollY >= root.scrollHeight - 2;
		var end = content ? content.getBoundingClientRect().bottom : root.getBoundingClientRect().bottom;

		// Fill each section's line by how far the reading line has passed through it.
		var done = 0;
		var total = 0;
		var remaining = 0;
		sections.forEach(function (section, i) {
			var top = section.target.getBoundingClientRect().top;
			var bottom = sections[i + 1] ? sections[i + 1].target.getBoundingClientRect().top : end;
			var fill = atEnd ? 1 : Math.min(1, Math.max(0, (line - top) / Math.max(1, bottom - top)));
			section.li.style.setProperty('--fill', (fill * 100).toFixed(1) + '%');
			section.li.classList.toggle('is-done', fill >= 1);
			if (fill >= 1) done++;
			total += section.minutes;
			remaining += section.minutes * (1 - fill);
		});
		if (sections.length) {
			var read = total ? Math.round((1 - remaining / total) * 100) : 0;
			count.textContent = done + '/' + sections.length + ' · ' + read + '%';
			var minutesLeft = Math.ceil(remaining - 0.05);
			left.textContent = minutesLeft > 0 ? '~' + minutesLeft + ' min left' : 'Finished';
		}

		var current = items[0];
		items.forEach(function (item) {
			if (item.target.getBoundingClientRect().top <= line) current = item;
		});
		if (atEnd) current = items[items.length - 1];
		if (current === active) return;
		active = current;

		items.forEach(function (item) {
			if (item === current) item.link.setAttribute('aria-current', 'true');
			else item.link.removeAttribute('aria-current');
		});
		sections.forEach(function (section) { section.li.classList.toggle('is-open', section.li.contains(current.link)); });

		// Keep the current entry visible inside a long rail.
		if (desktop.matches && toc.scrollHeight > toc.clientHeight) {
			var linkTop = current.link.offsetTop;
			var linkBottom = linkTop + current.link.offsetHeight;
			if (linkTop < toc.scrollTop + 48) toc.scrollTop = linkTop - 48;
			else if (linkBottom > toc.scrollTop + toc.clientHeight - 48) toc.scrollTop = linkBottom - toc.clientHeight + 48;
		}
	}
	function schedule() {
		if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
	}
	window.addEventListener('scroll', schedule, { passive: true });
	window.addEventListener('resize', schedule);
	update();

	// Copy buttons on code blocks.
	if (!navigator.clipboard) return;
	document.querySelectorAll('.entry-content pre').forEach(function (pre) {
		var head = pre.previousElementSibling;
		if (!head || !head.classList.contains('code-file')) {
			head = document.createElement('div');
			head.className = 'code-file';
			pre.parentNode.insertBefore(head, pre);
		}
		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'gd-copy';
		button.textContent = 'Copy';
		button.addEventListener('click', function () {
			navigator.clipboard.writeText(pre.innerText).then(function () {
				button.textContent = 'Copied';
				setTimeout(function () { button.textContent = 'Copy'; }, 1500);
			});
		});
		head.appendChild(button);
	});
})();
JS;

	wp_print_inline_script_tag( $script );
}
add_action( 'wp_footer', 'gdaih_docs_script' );

/**
 * Close the full-screen menu with Escape (every page).
 */
function gdaih_menu_script() {
	wp_print_inline_script_tag(
		"document.addEventListener('keydown',function(e){if(e.key!=='Escape')return;var b=document.querySelector('.main-navigation.toggled .menu-toggle,#mobile-header.toggled .menu-toggle');if(b){b.click();b.focus();}});"
	);
}
add_action( 'wp_footer', 'gdaih_menu_script' );

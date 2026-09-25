<?php
/**
 * GameDev AI Hub Child — functions.
 *
 * GeneratePress loads this child theme's style.css by itself, so there is
 * no stylesheet enqueue here.
 *
 * Builds the "Docs Split" single post (concept 02):
 *  - single posts always use the no-sidebar layout
 *  - "~/category/subcategory" path above the title
 *  - the post's manual excerpt as a subtitle under the title
 *  - avatar, updated date and reading time in the post meta
 *  - an "On this page" table of contents in its own column
 *  - anchor ids and "#" links on H2/H3 headings
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
 * True while GeneratePress renders the main post of a single post page.
 */
function gdaih_is_docs_post() {
	return is_singular( 'post' ) && in_the_loop() && get_the_ID() === get_queried_object_id();
}


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
 * @return array[] Each item: level, id, label.
 */
function gdaih_parse_headings( $html ) {
	if ( ! preg_match_all( '/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER ) ) {
		return array();
	}

	// Reserve ids that headings already have (set in the block's HTML anchor field).
	$used = array();
	foreach ( $matches as $m ) {
		if ( ! empty( $m[2] ) && preg_match( '/\sid=(["\'])(.*?)\1/i', $m[2], $id_match ) ) {
			$used[ $id_match[2] ] = true;
		}
	}

	$items = array();
	foreach ( $matches as $m ) {
		$label = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $m[3] ) ) );
		if ( '' === $label ) {
			continue;
		}
		if ( ! empty( $m[2] ) && preg_match( '/\sid=(["\'])(.*?)\1/i', $m[2], $id_match ) ) {
			$id = $id_match[2];
		} else {
			$id = gdaih_unique_id( $label, $used );
		}
		$items[] = array(
			'level' => (int) $m[1],
			'id'    => $id,
			'label' => $label,
		);
	}
	return $items;
}

/**
 * Table-of-contents entries for a post (cached per request).
 *
 * Reads the post's blocks directly, so the TOC can be printed before the
 * content itself is rendered.
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
		$items = gdaih_parse_headings( do_blocks( $post->post_content ) );
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
 */
function gdaih_print_toc() {
	if ( ! gdaih_is_docs_post() ) {
		return;
	}
	$items = gdaih_toc_items();
	if ( ! $items ) {
		return;
	}

	$list    = '';
	$in_sub  = false;
	$open_li = false;
	foreach ( $items as $item ) {
		$link = '<a href="#' . esc_attr( $item['id'] ) . '">' . esc_html( $item['label'] ) . '</a>';

		// H3s nest under the H2 before them.
		if ( 3 === $item['level'] && $open_li ) {
			if ( ! $in_sub ) {
				$list  .= '<ul>';
				$in_sub = true;
			}
			$list .= '<li>' . $link . '</li>';
			continue;
		}
		if ( $in_sub ) {
			$list  .= '</ul>';
			$in_sub = false;
		}
		if ( $open_li ) {
			$list .= '</li>';
		}
		$list   .= '<li>' . $link;
		$open_li = true;
	}
	$list .= ( $in_sub ? '</ul>' : '' ) . ( $open_li ? '</li>' : '' );

	$title = esc_html__( 'On this page', 'gdaih' );
	printf(
		'<nav class="gd-toc" aria-label="%1$s"><details class="gd-toc__box" open><summary class="gd-toc__title">%2$s</summary><ul class="gd-toc__list">%3$s</ul></details></nav>',
		esc_attr( $title ),
		$title,
		$list // Escaped above.
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
 * Front-end behaviour (single posts only, about 50 lines, no library)
 * ---------------------------------------------------------------------- */

function gdaih_docs_script() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$script = <<<'JS'
(function () {
	var desktop = window.matchMedia('(min-width: 1024px)');

	// TOC: an open rail on desktop, a collapsed "On this page" bar below that.
	var box = document.querySelector('.gd-toc__box');
	if (box) {
		box.open = desktop.matches;
		box.addEventListener('toggle', function () {
			if (desktop.matches && !box.open) box.open = true;
		});
		desktop.addEventListener('change', function (e) { box.open = e.matches; });
	}

	// Highlight the section being read.
	var items = Array.prototype.slice.call(document.querySelectorAll('.gd-toc a[href^="#"]'))
		.map(function (a) { return { link: a, target: document.getElementById(decodeURIComponent(a.hash.slice(1))) }; })
		.filter(function (item) { return item.target; });
	if (items.length) {
		var ticking = false;
		var update = function () {
			var offset = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--gd-sticky-top')) || 88;
			var current = items[0];
			items.forEach(function (item) {
				if (item.target.getBoundingClientRect().top <= offset + 40) current = item;
			});
			items.forEach(function (item) {
				if (item === current) item.link.setAttribute('aria-current', 'true');
				else item.link.removeAttribute('aria-current');
			});
			ticking = false;
		};
		window.addEventListener('scroll', function () {
			if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
		}, { passive: true });
		update();
	}

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

<?php
/**
 * GameDev AI Hub Child — functions.
 *
 * GeneratePress loads this child theme's style.css by itself, so no
 * stylesheet enqueue is needed here.
 *
 * This file adds an automatic "On this page" table of contents to single
 * posts. style.css turns it into the sticky rail on desktop.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimum number of H2/H3 headings a post needs before it gets a TOC.
 */
const GDAIH_TOC_MIN_HEADINGS = 2;

/**
 * Build the table of contents and give every H2/H3 an anchor id.
 *
 * Runs after blocks, wpautop and shortcodes (priority 20). Skips posts that
 * already contain a TOC from a plugin or a List block with the class "toc".
 */
function gdaih_add_table_of_contents( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$existing_toc = '/id=["\'](?:ez-toc-container|toc_container|rank-math-toc)["\']|class=["\'][^"\']*(?:\btoc\b|yoast-table-of-contents|lwptoc)/i';
	if ( preg_match( $existing_toc, $content ) ) {
		return $content;
	}

	if ( ! preg_match_all( '/<h([23])([^>]*)>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}
	if ( count( $matches ) < GDAIH_TOC_MIN_HEADINGS ) {
		return $content;
	}

	$used_ids = array();
	$items    = array();
	$offset   = 0; // Shift caused by ids inserted earlier in the string.

	foreach ( $matches as $match ) {
		$full  = $match[0][0];
		$start = $match[0][1] + $offset;
		$level = (int) $match[1][0];
		$attrs = $match[2][0];
		$label = trim( wp_strip_all_tags( $match[3][0] ) );

		if ( '' === $label ) {
			continue;
		}

		if ( preg_match( '/\sid=(["\'])(.*?)\1/i', $attrs, $id_match ) ) {
			$id = $id_match[2];
		} else {
			$base = sanitize_title( $label );
			$base = '' !== $base ? $base : 'section';
			$id   = $base;
			$n    = 2;
			while ( in_array( $id, $used_ids, true ) ) {
				$id = $base . '-' . $n++;
			}

			$new_heading = '<h' . $level . ' id="' . esc_attr( $id ) . '"' . $attrs . '>' . $match[3][0] . '</h' . $level . '>';
			$content     = substr_replace( $content, $new_heading, $start, strlen( $full ) );
			$offset     += strlen( $new_heading ) - strlen( $full );
		}

		$used_ids[] = $id;
		$items[]    = array(
			'level' => $level,
			'id'    => $id,
			'label' => $label,
		);
	}

	if ( count( $items ) < GDAIH_TOC_MIN_HEADINGS ) {
		return $content;
	}

	// H2s are top-level entries; H3s nest under the H2 before them.
	$html    = '<nav class="toc" aria-label="' . esc_attr__( 'On this page', 'gdaih' ) . '">';
	$html   .= '<p class="toc-title">' . esc_html__( 'On this page', 'gdaih' ) . '</p><ul>';
	$in_sub  = false;
	$open_li = false;

	foreach ( $items as $item ) {
		$link = '<a href="#' . esc_attr( $item['id'] ) . '">' . esc_html( $item['label'] ) . '</a>';

		if ( 3 === $item['level'] && $open_li ) {
			if ( ! $in_sub ) {
				$html  .= '<ul>';
				$in_sub = true;
			}
			$html .= '<li>' . $link . '</li>';
			continue;
		}

		if ( $in_sub ) {
			$html  .= '</ul>';
			$in_sub = false;
		}
		if ( $open_li ) {
			$html .= '</li>';
		}
		$html   .= '<li>' . $link;
		$open_li = true;
	}

	if ( $in_sub ) {
		$html .= '</ul>';
	}
	if ( $open_li ) {
		$html .= '</li>';
	}
	$html .= '</ul></nav>';

	// Place the TOC just before the first heading, after the intro.
	preg_match( '/<h[23][\s>]/i', $content, $first_tag, PREG_OFFSET_CAPTURE );
	$first = isset( $first_tag[0][1] ) ? $first_tag[0][1] : 0;

	return substr_replace( $content, $html, $first, 0 );
}
add_filter( 'the_content', 'gdaih_add_table_of_contents', 20 );

/**
 * Highlight the section being read in the TOC (aria-current="true").
 * About 20 lines of inline JavaScript, printed on single posts only.
 */
function gdaih_toc_scrollspy() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$script = <<<'JS'
(function () {
	var links = Array.prototype.slice.call(document.querySelectorAll('.entry-content > .toc a[href^="#"]'));
	var items = links.map(function (a) {
		return { link: a, target: document.getElementById(decodeURIComponent(a.hash.slice(1))) };
	}).filter(function (item) { return item.target; });
	if (!items.length) return;

	var ticking = false;
	function update() {
		var current = items[0];
		items.forEach(function (item) {
			if (item.target.getBoundingClientRect().top <= 140) current = item;
		});
		items.forEach(function (item) {
			if (item === current) item.link.setAttribute('aria-current', 'true');
			else item.link.removeAttribute('aria-current');
		});
		ticking = false;
	}
	window.addEventListener('scroll', function () {
		if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
	}, { passive: true });
	update();
})();
JS;

	wp_print_inline_script_tag( $script );
}
add_action( 'wp_footer', 'gdaih_toc_scrollspy' );

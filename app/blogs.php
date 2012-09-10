<?php
	function category_name_html($row) {
		return '<a href="'.html('category.php?id='.intval($row['categories.id'])).'">'
			.(strlen($row['name']) ? html_stiff($row['name']) : '<i>(χωρίς όνομα)</i>' )
		.'</a>';
	}
	function blog_name_html($row) {
		return '<a href="'.html('blog.php?id='.intval($row['blogs.id'])).'">'
			.(strlen($row['blogs.name']) ? html_stiff($row['blogs.name']) : '<i>(χωρίς όνομα)</i>' )
		.'</a>';
	}
	function blog_url_html($row) {
		return '<a href="'.html($row['blogs.url']).'">'
			.html_stiff($row['blogs.url'])
		.'</a>';
	}
	function feed_name_html($row) {
		return '<a href="'.html('feed.php?id='.intval($row['feeds.id'])).'">'
			.(strlen($row['feeds.name']) ? html_stiff($row['feeds.name']) : '<i>(χωρίς όνομα)</i>' )
		.'</a>';
	}
	function feed_url_html($row) {
		return '<a href="'.html($row['feeds.url']).'">'
			.($row['feeds.disabled']?'<strike>':'')
				.html_stiff($row['feeds.url'])
			.($row['feeds.disabled']?'</strike>':'')
		.'</a>';
	}
	function feed_full_html($row) {
		global $URL;
		if ($row['feeds.full_feed']===null) {
			return '';
		} else {
			$str = $row['feeds.full_feed'] ? 'ναι' : 'όχι';
			if ( strlen($row['feeds.body_xpath']) > 0) {
				$str .= '<img src="'.html($URL.'/app/star.png').'" alt="xpath"'
				           .' title="'.html($row['feeds.body_xpath']).'" />';
			}
			return $str;
		}
	}
	function blog_state_html($state) {
		return ($state&1) ? '<b>Ελέγχεται τώρα...</b>' : '';
	}
?>

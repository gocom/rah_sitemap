<?php	##################
	#
	#	rah_sitemap-plugin for Textpattern
	#	version 1.2
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	if(@txpinterface == 'admin') {
		add_privs('rah_sitemap','1,2');
		register_tab('extensions','rah_sitemap','Sitemap');
		register_callback('rah_sitemap_page','rah_sitemap');
		register_callback('rah_sitemap_head','admin_side','head_end');
	} else 
		register_callback('rah_sitemap','textpattern');

/**
	Checks if the sitemap should be returned
*/

	function rah_sitemap_delivery() {
		global $pretext;
		
		$uri = $pretext['request_uri'];
		$uri = explode('/',$uri);
		$uri = array_reverse($uri);
		
		if(in_array($uri[0],array('sitemap.xml.gz','sitemap.xml')))
			return true;
		
		if(gps('rah_sitemap') == 'sitemap')	
			return true;
		
		return false;
	}

/**
	The sitemap
*/

	function rah_sitemap() {
		if(rah_sitemap_delivery() == false)
			return;
		
		global $s, $thissection, $thiscategory, $c, $pretext, $thispage, $thisarticle;
		
		@$pref = rah_sitemap_prefs();
		
		if(!isset($pref['zlib_output'])) {
			rah_sitemap_install();
			$pref = rah_sitemap_prefs();
		}

		header('Content-type: application/xml');

		if($pref['compress'] == 1 && function_exists('gzencode'))
			header('Content-Encoding: gzip');
		
		if($pref['zlib_output'] == 1)
			ini_set('zlib.output_compression','Off');
	
		$timestampformat = (!empty($pref['timestampformat'])) ? $pref['timestampformat'] : 'c';
		
		$out[] = 
			'<?xml version="1.0" encoding="utf-8"?>'.
			'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.
			'<url><loc>'.hu.'</loc></url>';
		
		if($pref['nosections'] != 1) {
			
			$rs = 
				safe_rows(
					'name',
					'txp_section',
					rah_sitemap_in('name',$pref['sections'],'default') .
					" order by name asc"
				);
			
			$thisarticle['section'] = '';
			
			foreach($rs as $a) {
				
				$s = $thispage['s'] = $thissection['section'] =  $pretext['s'] = $a['name'];
				
				if($pref['permlink_section'])
					$out[] = '<url><loc>'.parse($pref['permlink_section']).'</loc></url>';
				else 
					$out[] = '<url><loc>'.pagelinkurl(array('s' => $a['name'])).'</loc></url>';
			}
			
			$s =  $thissection['section'] = $thispage['s'] = $pretext['s'] = '';
			
		}
		
		$notypes = '';
		
		if($pref['nocategories'] == 1)
			$notypes[] = 'article';
		if($pref['nofile'] == 1)
			$notypes[] = 'file';
		if($pref['noimage'] == 1)
			$notypes[] = 'image';
		if($pref['nolink'] == 1)
			$notypes[] = 'link';
		
		$not = explode(',',$pref['categories']);
		
		$rs = 
			safe_rows(
				'name,type,id',
				'txp_category',
				"name != 'root' " . rah_sitemap_in('and type',$notypes) . " order by name asc"
			);
		
		foreach($rs as $a) {

			if(in_array($a['type'].'_||_'.$a['name'],$not))
				continue;

			$c = $thiscategory['c'] = $pretext['c'] = $thispage['c'] = $a['name'];

			if($pref['permlink_category'])
				$out[] = 
					
					'<url><loc>'.
					
					htmlspecialchars(str_replace(
						array(
							'[type]',
							'[id]'
						),
						array(
							$a['type'],
							$a['id']
						),
						parse($pref['permlink_category'])
					)).
					
					'</loc></url>';
					
			else 
				$out[] = 
					'<url><loc>'.pagelinkurl(array('c' => $a['name'])).'</loc></url>';
		}
		
		$c = $thiscategory['c'] = $pretext['c'] = $thispage['c'] =  '';
		
		if($pref['noarticles'] != 1) {
			
			$sql[] = rah_sitemap_in(' and Category1',$pref['articlecategories']);
			$sql[] = rah_sitemap_in(' and Category2',$pref['articlecategories']);
			$sql[] = rah_sitemap_in(' and Status',$pref['articlestatus'],'1,2,3');
			$sql[] = rah_sitemap_in(' and Section',$pref['articlesections']);
			
			if($pref['articlefuture'])
				$sql[] = ' and Posted <= now()';
			if($pref['articlepast'])
				$sql[] = ' and Posted > now()';
			if($pref['articleexpired'])
				$sql[] = " and (Expires = '0000-00-00 00:00:00' or Expires >= now())";
			
			
			if($pref['permlink_article'])
				$columns = 
				 	'*,  unix_timestamp(Posted) as posted, '.
				 	'unix_timestamp(Expires) as uExpires, '.
				 	'unix_timestamp(LastMod) as uLastMod';
			else
				$columns = 
					'ID as thisid, Section as section, '.
					'Title as title, url_title, unix_timestamp(Posted)'.
					' as posted, unix_timestamp(LastMod) as uLastMod';
			
			$rs = 
				safe_rows(
					$columns,
					'textpattern',
					'1=1' . implode('',$sql). ' order by Posted desc'
				);
			
			foreach($rs as $a) {
				extract($a);
				
				if($pref['permlink_article']) {
					$thisarticle = 
						array(
							'thisid' => $ID,
							'posted' => $posted,
							'modified' => $uLastMod,
							'annotate' => $Annotate,
							'comments_invite' => $AnnotateInvite,
							'authorid' => $AuthorID,
							'title' => $Title,
							'url_title' => $url_title,
							'category1' => $Category1,
							'category2' => $Category2,
							'section' => $Section,
							'keywords' => $Keywords,
							'article_image' => $Image,
							'comments_count' => $comments_count,
							'body' => $Body_html,
							'excerpt' => $Excerpt_html,
							'override_form' => $override_form,
							'status'=> $Status
						)
					;
					
					@$url = htmlspecialchars(parse($pref['permlink_article']));
				}
				
				else {
					@$url = permlinkurl($a);
					
					/*
						Fix for gbp_permanent_links
					*/
					
					if(strpos($url,'/') === 0)
						$url = hu . ltrim($url,'/');
				}
				
				@$out[] = 
					'<url><loc>'.$url.'</loc><lastmod>'.
					($uLastMod < $posted ? 
						date($timestampformat,$posted) : 
						date($timestampformat,$uLastMod)
					).
					'</lastmod></url>'
				;
				
			}
			$thisarticle = '';
		}
		
		$rs = 
			safe_rows(
				'*, unix_timestamp(posted) as uposted',
				'rah_sitemap',
				'1=1 order by posted desc'
			);
		
		foreach($rs as $a) {
			$url = parse($a['url']);
			$out[] = '<url><loc>'.rah_sitemap_uri($url,1).'</loc>';
			if($a['include'] == 1)
				@$out[] = '<lastmod>'.date($timestampformat,$a['uposted']).'</lastmod>';
			$out[] = '</url>';
		}
		
		$out[] = '</urlset>';
		$out = implode('',$out);
		
		echo (@$pref['compress'] == 1 && function_exists('gzencode')) ? gzencode($out,$pref['compression_level']) : $out;
		exit();
	}

/**
	Delivers the panels
*/

	function rah_sitemap_page() {
		require_privs('rah_sitemap');
		global $step;
		if(in_array($step,array(
			'rah_sitemap_save',
			'rah_sitemap_custom_list',
			'rah_sitemap_custom_form',
			'rah_sitemap_custom_save',
			'rah_sitemap_delete'
		))) $step();
		else rah_sitemap_list();
	}

/**
	Preferences panel
*/

	function rah_sitemap_list($message='') {
		@$pref = rah_sitemap_prefs();

		if(!isset($pref['zlib_output'])) {
			rah_sitemap_install();
			$pref = rah_sitemap_prefs();
		}

		rah_sitemap_header(
			
			'	<form method="post" action="index.php">'.n.
			
			'		<p><strong>General preferences</strong></p>'.n.
			
			'		<p title="Click to expand" class="rah_sitemap_heading">'.n.
			'			+ <span>Exclude sections and categories from the sitemap</span>'.n.
			'		</p>'.n.
			
			'		<div class="rah_sitemap_more">'.n.
			
			rah_sitemap_listing('Exclude sections','sections','txp_section',"name != 'default'").
			rah_sitemap_listing('Exclude categories','categories','txp_category',"name != 'root' and title != 'root'").
			
			'			<div class="rah_sitemap_column">'.n.
			
			'				<strong>Advanced settings</strong><br />'.n.
			'				<label><input type="checkbox" name="nosections" value="1"'.(($pref['nosections'] == 1) ? ' checked="checked"' : '').' /> Exclude all section URLs</label><br />'.n.
			'				<label><input type="checkbox" name="nofile" value="1"'.(($pref['nofile'] == 1) ? ' checked="checked"' : '').' /> Exclude all file-type category URLs</label><br />'.n.
			'				<label><input type="checkbox" name="noimage" value="1"'.(($pref['noimage'] == 1) ? ' checked="checked"' : '').' /> Exclude all image-type category URLs</label><br />'.n.
			'				<label><input type="checkbox" name="nolink" value="1"'.(($pref['nolink'] == 1) ? ' checked="checked"' : '').' /> Exclude all link-type category URLs</label><br />'.n.
			'				<label><input type="checkbox" name="nocategories" value="1"'.(($pref['nocategories'] == 1) ? ' checked="checked"' : '').' /> Exclude all article-type category URLs</label><br />'.n.
			
			
			'			</div>'.n.
			
			'		</div>'.n.
			
			'		<p title="Click to expand" class="rah_sitemap_heading">'.n.
			'			+ <span>Filter articles from the sitemap</span>'.n.
			'		</p>'.n.
			
			'		<div class="rah_sitemap_more">'.n.
			
			
			rah_sitemap_listing('Exclude article sections','articlesections','txp_section',"name != 'default'").
			rah_sitemap_listing('Exclude article categories','articlecategories','txp_category',"name != 'root' and title != 'root' and type = 'article'").
			
			'			<div class="rah_sitemap_column">'.n.
			'				<strong>Advanced settings</strong><br />'.n.
			'				<label><input type="checkbox" name="noarticles" value="1"'.(($pref['noarticles'] == 1) ? ' checked="checked"' : '').' /> Don\'t include articles in sitemap</label><br />'.n.
			'				<label><input type="checkbox" name="articlestatus" value="5"'.(($pref['articlestatus'] == 5) ? ' checked="checked"' : '').' /> Exclude sticky articles</label><br />'.n.
			'				<label><input type="checkbox" name="articlefuture" value="1"'.(($pref['articlefuture'] == 1) ? ' checked="checked"' : '').' /> Exclude future articles</label><br />'.n.
			'				<label><input type="checkbox" name="articlepast" value="1"'.(($pref['articlepast'] == 1) ? ' checked="checked"' : '').' /> Exclude past articles</label><br />'.n.
			'				<label><input type="checkbox" name="articleexpired" value="1"'.(($pref['articleexpired'] == 1) ? ' checked="checked"' : '').' /> Exclude expired articles</label>'.n.
			'			</div>'.n.
			
			'		</div>'.n.
			
			'		<p><strong>Advanced settings</strong></p>'.n.
			
			'		<p title="Click to expand" class="rah_sitemap_heading">'.n.
			'			+ <span>Compression methods</span>'.n.
			'		</p>'.n.
			
			'		<div class="rah_sitemap_more">'.n.
			
			'			<p class="rah_sitemap_paragraph"><strong>Compression.</strong> With these settings you can control compression and even turn it off if it causes problems on your server. It is recommeded to leave compression on if possible. Compression level 0 is the mildest and 9 is the maximum compression.</p>'.n.
			'			<p>'.n.
			'				<label for="rah_sitemap_compress">Use Gzip compression:</label><br />'.n.
			
			'				<select name="compress" id="rah_sitemap_compress">'.n.
			'					<option value="1"'.(($pref['compress'] == 1) ? ' selected="selected"' : '').'>Yes</option>'.n.
			'					<option value="0"'.(($pref['compress'] == 0) ? ' selected="selected"' : '').'>No</option>'.n.
			'				</select>'.n.
			
			'			</p>'.n.
			'			<p>'.n.
			'				<label for="rah_sitemap_compression_level">Compression level:</label><br />'.n.
			
			'				<select name="compression_level" id="rah_sitemap_compression_level">'.n.
			'					<option value="0"'.(($pref['compression_level'] == 0) ? ' selected="selected"' : '').'>0</option>'.n.
			'					<option value="1"'.(($pref['compression_level'] == 1) ? ' selected="selected"' : '').'>1</option>'.n.
			'					<option value="2"'.(($pref['compression_level'] == 2) ? ' selected="selected"' : '').'>2</option>'.n.
			'					<option value="3"'.(($pref['compression_level'] == 3) ? ' selected="selected"' : '').'>3</option>'.n.
			'					<option value="4"'.(($pref['compression_level'] == 4) ? ' selected="selected"' : '').'>4</option>'.n.
			'					<option value="5"'.(($pref['compression_level'] == 5) ? ' selected="selected"' : '').'>5</option>'.n.
			'					<option value="6"'.(($pref['compression_level'] == 6) ? ' selected="selected"' : '').'>6</option>'.n.
			'					<option value="7"'.(($pref['compression_level'] == 7) ? ' selected="selected"' : '').'>7</option>'.n.
			'					<option value="8"'.(($pref['compression_level'] == 8) ? ' selected="selected"' : '').'>8</option>'.n.
			'					<option value="9"'.(($pref['compression_level'] == 9) ? ' selected="selected"' : '').'>9</option>'.n.
			'				</select>'.n.
			
			'			</p>'.n.
			
			'			<p>'.n.
			'				<label for="rah_sitemap_zlib_output">Set zlib output compression off. If set to no, configuration is not modified:</label><br />'.n.
			
			'				<select name="zlib_output" id="rah_sitemap_zlib_output">'.n.
			'					<option value="1"'.(($pref['zlib_output'] == 1) ? ' selected="selected"' : '').'>Yes</option>'.n.
			'					<option value="0"'.(($pref['zlib_output'] == 0) ? ' selected="selected"' : '').'>No</option>'.n.
			'				</select>'.n.
			
			'			</p>'.n.
			'		</div>'.n.
			
			
			'		<p title="Click to expand" class="rah_sitemap_heading">'.n.
			'			+ <span>Override timestamp format</span>'.n.
			'		</p>'.n.
			
			'		<div class="rah_sitemap_more">'.n.
			
			'			<p class="rah_sitemap_paragraph"><strong>Timestamps.</strong> Customize the date format used in last modified timestamps. Use <a href="http://php.net/manual/en/function.date.php">date()</a> string values. If unset (left empty) default ISO 8601 date (<code>c</code>) is used. Use this setting if you want to hard-code/override timestamps, timezones or if your server doesn\'t support <code>c</code> format.</p>'.n.
			
			'			<p>'.n.
			'				<label for="rah_sitemap_timestampformat">Format:</label><br />'.n.
			'				<input type="text" class="edit" style="width: 940px;" name="timestampformat" id="rah_sitemap_timestampformat" value="'.htmlspecialchars($pref['timestampformat']).'" />'.n.
			'			</p>'.n.
			
			'		</div>'.n.
			
			'		<p title="Click to expand" class="rah_sitemap_heading">'.n.
			'			+ <span>Override permlink formats</span>'.n.
			'		</p>'.n.
			
			'		<div class="rah_sitemap_more">'.n.
			
			'			<p class="rah_sitemap_paragraph"><strong>Permlinks.</strong> With these settings you can make the Sitemap\'s URLs to match your own URL rewriting rules, or permlinks made by a <em>custom permlink rule</em> plugin. You can leave these fields empty, if using TXP\'s inbuild permlink rules. Note that these setting do not rewrite TXP\'s permlinks for you, use only for matching not rewriting!</p>'.n.
			'			<p>'.n.
			'				<label for="rah_sitemap_permlink_category">Category URLs:</label><br />'.n.
			'				<input type="text" class="edit" name="permlink_category" id="rah_sitemap_permlink_category" value="'.htmlspecialchars($pref['permlink_category']).'" />'.n.
			'			</p>'.n.
			'			<p>'.n.
			'				<label for="rah_sitemap_permlink_section">Section URLs:</label><br />'.n.
			'				<input type="text" class="edit" name="permlink_section" id="rah_sitemap_permlink_section" value="'.htmlspecialchars($pref['permlink_section']).'" />'.n.
			'			</p>'.n.
			'			<p>'.n.
			'				<label for="rah_sitemap_permlink_article">Article URLs:</label><br />'.n.
			'				<input type="text" class="edit" name="permlink_article" id="rah_sitemap_permlink_article" value="'.htmlspecialchars($pref['permlink_article']).'" />'.n.
			'			</p>'.n.
			
			'		</div>'.n.
			
			'		<input type="hidden" name="event" value="rah_sitemap" />'.n.
			'		<input type="hidden" name="step" value="rah_sitemap_save" />'.n.
			
			'		<p><input type="submit" value="'.gTxt('save').'" class="publish" /></p>'.n.
			
			'	</form>',
			
			'rah_sitemap',
			'Manage your sitemap',
			'Sitemap.org Sitemaps',
			$message
			
		);
	}

/**
	Panel, lists custom URLs
*/

	function rah_sitemap_custom_list($message='') {

		$out[] = 
			'	<form method="post" action="index.php">'.n.
			'		<table id="list" class="list rah_sitemap_table"  border="0" cellspacing="0" cellpadding="0">'.n.
			'			<tr>'.n.
			'				<th>URL</th>'.n.
			'				<th>LastMod</th>'.n.
			'				<th>Include LastMod</th>'.n.
			'				<th>View</th>'.n.
			'				<th>&#160;</th>'.n.
			'			</tr>'.n;
		
		$rs =
			safe_rows(
				'url,posted,include',
				'rah_sitemap',
				"1=1 order by posted desc"
			);
		
		if($rs) {
			foreach($rs as $a) {
				$uri = rah_sitemap_uri($a['url'],1);
				$out[] = 
					'			<tr>'.n.
					'				<td><a href="?event=rah_sitemap&amp;step=rah_sitemap_custom_form&amp;edit='.urlencode($a['url']).'">'.$uri.'</a></td>'.n.
					'				<td>'.$a['posted'].'</td>'.n.
					'				<td>'.(($a['include'] == 1) ? 'Yes' : 'No').'</td>'.n.
					'				<td><a target="_blank" href="'.htmlspecialchars($uri).'">'.gTxt('view').'</a></td>'.n.
					'				<td><input type="checkbox" name="selected[]" value="'.htmlspecialchars($a['url']).'" /></td>'.n.
					'			</tr>'.n;
			}
		} else 
			$out[] =  '			<tr><td colspan="5">No custom URLs found.</td></tr>'.n;
		
		$out[] =  
			'		</table>'.n.
			'		<p id="rah_sitemap_step">'.n.
			'			<select name="step">'.n.
			'				<option value="">With selected...</option>'.n.
			'				<option value="rah_sitemap_delete">Delete</option>'.n.
			'			</select>'.n.
			'			<input type="submit" class="smallerbox" value="Go" />'.n.
			'		</p>'.n.
			'		<input type="hidden" name="event" value="rah_sitemap" />'.n.
			'	</form>'.n;
		
		rah_sitemap_header(
			$out,
			'rah_sitemap',
			'List of custom URLs',
			'Sitemap',
			$message
		);
	}

/**
	Panel to add custom URLs
*/

	function rah_sitemap_custom_form($message='') {
		
		$edit = gps('edit');
		
		if($edit) {
			
			$rs = 
				safe_row(
					'*',
					'rah_sitemap',
					"url='".doSlash($edit)."'"
				);
			
			if(!$rs) {
				rah_sitemap_custom_list('Selection not found.');
				return;
			}
			
			extract($rs);
		
		}
		
		if(!isset($rs))
			extract(gpsa(array(
				'url','include','posted'
			)));
		
		rah_sitemap_header(
			'	<form method="post" action="index.php">'.n.
			'		<p>'.n.
			'			<label for="rah_sitemap_url">URL:</label><br />'.n.
			'			<input id="rah_sitemap_url" class="edit" type="text" name="url" value="'.htmlspecialchars($url).'" />'.n.
			'		</p>'.n.
			'		<p>'.n.
			'			<label for="rah_sitemap_posted">LastMod (YYYY-mm-dd HH:MM:SS). Leave empty to use current time:</label><br />'.n.
			'			<input id="rah_sitemap_posted" class="edit" type="text" name="posted" value="'.htmlspecialchars($posted).'" />'.n.
			'		</p>'.n.
			'		<p>'.n.
			'			<label for="rah_sitemap_lastmod">Include LastMod:</label><br />'.n.
			'			<select id="rah_sitemap_lastmod" name="include">'.n.
			'					<option value="0"'.(($include == 0) ? ' selected="selected"' : '').'>'.gTxt('no').' (Recommended)</option>'.n.
			'					<option value="1"'.(($include == 1) ? ' selected="selected"' : '').'>'.gTxt('yes').'</option>'.n.
			'			</select>'.n.
			'		</p>'.n.
			'		<p><input type="submit" value="'.gTxt('save').'" class="publish" /></p>'.n.
			
			(($edit) ? 
				'		<input type="hidden" name="edit" value="'.htmlspecialchars($edit).'" />' : ''
			).
			
			'		<input type="hidden" name="event" value="rah_sitemap" />'.n.
			'		<input type="hidden" name="step" value="rah_sitemap_custom_save" />'.n.
			
			'	</form>'
			
			,'rah_sitemap',
			'Add a new custom URL',
			'Sitemap',
			$message
			
		);
		
	}

/**
	Saves custom URL
*/

	function rah_sitemap_custom_save() {
		extract(doSlash(gpsa(array(
			'url',
			'posted',
			'include',
			'reset',
			'edit'
		))));
		
		if(empty($posted) or $reset == 1)
			$posted = 'posted=now()';
		else 
			$posted = "posted='$posted'";
		
		if(!$edit && safe_count('rah_sitemap',"url='$url'") != 0) {
			rah_sitemap_custom_form('URL already exists.');
			return;
		}
		
		if($edit && safe_count('rah_sitemap',"url='$edit'") == 1) {
			
			if($url != $edit && safe_count('rah_sitemap',"url='$url'") == 1) {
				rah_sitemap_custom_form('New URL already exists.');
				return;
			}
			
			safe_update(
				'rah_sitemap',
				"$posted,
				include='$include',
				url='$url'",
				"url='$edit'"
			);
			
			rah_sitemap_custom_list('URL updated.');
			return;
			
		}
		
		safe_insert(
			'rah_sitemap',
			"url='$url',
			$posted,
			include='$include'"
		);
		
		rah_sitemap_custom_list('URL added.');
		return;
	}

/**
	Outputs the panel's CSS and JavaScript to page's <head> segment
*/

	function rah_sitemap_head() {
		
		global $event;
		
		if($event != 'rah_sitemap')
			return;
		
		echo <<<EOF
			<style type="text/css">
				#rah_sitemap_container {
					width: 950px;
					margin: 0 auto;
				}
				#rah_sitemap_container #rah_sitemap_step {
					text-align: right;
					padding-top: 10px;
				}
				#rah_sitemap_container .rah_sitemap_table {
					width: 100%;
				}
				#rah_sitemap_container .rah_sitemap_column {
					width: 315px;
					float: left;
					display: inline;
					padding: 0 0 10px 0;
				}
				#rah_sitemap_container .rah_sitemap_heading {
					font-weight: 900;
					padding: 5px 0;
					margin: 0 0 10px 0;
					border-top: 1px solid #ccc;
					border-bottom: 1px solid #ccc;
				}
				#rah_sitemap_container .rah_sitemap_heading span {
					cursor: pointer;
					color: #963;
				}
				#rah_sitemap_container .rah_sitemap_heading span:hover {
					text-decoration: underline;
				}
				#rah_sitemap_container .rah_sitemap_more {
					overflow: hidden;
				}
				#rah_sitemap_container input.edit {
					width: 940px;
				}
				#rah_sitemap_zlib_output,
				#rah_sitemap_compression_level,
				#rah_sitemap_compress,
				#rah_sitemap_lastmod {
					width: 450px;
				}
				.rah_sitemap_paragraph {
					margin: 0 0 10px 0;
					padding: 0;
				}
			</style>
			<script type="text/javascript">
				$(document).ready(function(){
					$('.rah_sitemap_more').hide();
					$('.rah_sitemap_heading').click(function(){
						$(this).next('div.rah_sitemap_more').slideToggle();
					});
				});
			</script>
EOF;
	}

/**
	The panel's navigation bar
*/

	function rah_sitemap_header($content='',$title='rah_sitemap',$msg='Manage your sitemap',$pagetop='',$message='') {
		
		pagetop($pagetop,$message);
		
		if(is_array($content))
			$content = implode('',$content);
		
		echo 
			n.
			'	<div id="rah_sitemap_container">'.n.
			'		<h1><strong>'.$title.'</strong> | '.$msg.'</h1>'.n.
			'		<p id="rah_sitemap_nav">'.
					' &#187; <a href="?event=rah_sitemap">Preferences</a>'.
					' &#187; <a href="?event=rah_sitemap&amp;step=rah_sitemap_custom_form">Add custom URL</a>'.
					' &#187; <a href="?event=rah_sitemap&amp;step=rah_sitemap_custom_list">List of custom URLs</a>'.
					' &#187; <a target="_blank" href="'.hu.'?rah_sitemap=sitemap">View the sitemap</a>'.
					'</p>'.n.
			$content.n.	
			'	</div>'.n;
	}

/**
	Builds the required in array for SQL statements
*/

	function rah_sitemap_in($field='',$array='',$default='',$sql=' not in') {
		
		if(empty($array) && empty($default))
			return;
		
		if(!is_array($array))
			$array = explode(',',$array);
		
		if(!empty($default)) {
			$default = explode(',',$default);
			$array = 
				array_merge(
					$array,
					$default
				);
		}
		
		foreach($array as $value) 
			$out[] = "'".doSlash(trim($value))."'";
		
		if(!isset($out))
			return;
	
		return 
			$field . $sql . '(' . implode(',',$out) . ')';
		
	}

/**
	Default settings
*/

	function rah_sitemap_pref_fields() {
		return
			array(
				'noarticles' => '',
				'nosections' => '',
				'nocategories' => '',
				'articlecategories' => '',
				'articlesections' => '',
				'sections' => '',
				'categories' => '',
				'nofile' => 1,
				'noimage' => 1,
				'nolink' => 1,
				'articlestatus' => '',
				'articlefuture' => '',
				'articlepast' => '',
				'articleexpired' => '',
				'permlink_category' => '',
				'permlink_section' => '',
				'permlink_article' => '',
				'timestampformat' => 'c',
				'compress' => 1,
				'compression_level' => 9,
				'zlib_output' => 0
			);
	}

/**
	Returns preferences as an array
*/

	function rah_sitemap_prefs() {
		
		$out = array();
		
		$rs = 
			safe_rows(
				'name,value',
				'rah_sitemap_prefs',
				'1=1'
			);
		
		foreach($rs as $row)
			$out[$row['name']] = $row['value'];
			
		return $out;
		
	}

/**
	Build the custom URLs
*/

	function rah_sitemap_uri($uri='',$escape=0) {
		
		if(
			substr($uri,0,7) != 'http://' && 
			substr($uri,0,8) != 'https://' &&
			substr($uri,0,6) != 'ftp://' &&
			substr($uri,0,7) != 'ftps://' && 
			substr($uri,0,4) != 'www.'
		)
			$uri =  hu . $uri;
		
		else if(substr($uri,0,4) == 'www.')
			$uri =  'http://' . $uri;
		
		if($escape == 1)
			$uri = htmlspecialchars($uri);
		
		return $uri;
	}

/**
	Installer. Creates tables and adds the default rows
*/

	function rah_sitemap_install() {
		safe_query(
			"CREATE TABLE IF NOT EXISTS ".safe_pfx('rah_sitemap')." (
				`url` VARCHAR(255) NOT NULL,
				`posted` DATETIME NOT NULL,
				`include` INT(1) NOT NULL,
				PRIMARY KEY(`url`)
			)"
		);
		safe_query(
			"CREATE TABLE IF NOT EXISTS ".safe_pfx('rah_sitemap_prefs')." (
				`name` VARCHAR(255) NOT NULL,
				`value` LONGTEXT NOT NULL,
				PRIMARY KEY(`name`)
			)"
		);
		
		foreach(rah_sitemap_pref_fields() as $key => $val) {
			if(
				safe_count(
					'rah_sitemap_prefs',
					"name='".doSlash($key)."'"
				) == 0
			) {
				safe_insert(
					'rah_sitemap_prefs',
					"name='".doSlash($key)."', value='".doSlash($val)."'"
				);
			}
		}
	}

/**
	Builds the list of filters
*/

	function rah_sitemap_listing($label='',$field='',$table='',$where='') {
		
		$pref = 
			rah_sitemap_prefs();
		
		$exclude = explode(',',$pref[$field]);
		
		$rs = 
			safe_rows(
				'name,title'.(($table == 'txp_category') ? ',type' : ''),
				$table,
				"$where order by ".(($table == 'txp_category') ? 'type asc, ' : '')." name asc"
			);
		
		$out[] = 
			'					<div class="rah_sitemap_column">'.n.
			'						<strong>'.$label.'</strong><br />'.n;
		
		if($rs){
			foreach($rs as $a) {
				
				$name = $a['name'];
				$title = $a['title'];
				
				if($field == 'categories') {
					$name = $a['type'].'_||_'.$a['name'];
					$title = ucfirst($a['type']).$a['title'];
				}

				$out[] = 
					'						<label><input type="checkbox" name="'.$field.'[]" value="'.$name.'"'.((in_array($name,$exclude)) ? ' checked="checked"' : '').' /> '.$title .'</label><br />'.n;
			}
		} else $out[] = '						Nothing found.'.n;
		$out[] = 
			'					</div>'.n;
		return implode('',$out);
	}

/**
	Saves preferences
*/

	function rah_sitemap_save() {
		foreach(rah_sitemap_pref_fields() as $key => $val) {
			$ps = ps($key);
			
			if(is_array($ps))
				$ps = implode(',',$ps);
			
			safe_update(
				'rah_sitemap_prefs',
				"value='".doSlash(trim($ps))."'",
				"name='".$key."'"
			);
		}
		rah_sitemap_list('Sitemap preferences saved');
	}

/**
	Deletes custom URIs
*/

	function rah_sitemap_delete() {
		
		$selected = ps('selected');
		
		if(!is_array($selected)) {
			rah_sitemap_custom_list('Nothing selected');
			return;
		}
		
		foreach($selected as $name) 
			safe_delete(
				'rah_sitemap',
				"url='".doSlash($name)."'"
			);
		
		rah_sitemap_custom_list('Selection deleted');
	}
?>
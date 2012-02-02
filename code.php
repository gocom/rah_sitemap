<?php	##################
	#
	#	rah_sitemap-plugin for Textpattern
	#	version 0.5
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	if (@txpinterface == 'admin') {
		add_privs('rah_sitemap', '1,2');
		register_tab("extensions", "rah_sitemap", "Sitemap");
		register_callback("rah_sitemap_page", "rah_sitemap");
	} else if(gps('rah_sitemap') == 'sitemap') rah_sitemap();

	function rah_sitemap() {
		header('Content-type: application/xml');
		if(function_exists('gzencode')) header("Content-Encoding: gzip");
		global $s, $thissection, $thiscategory, $c, $pretext, $thispage, $thisarticle;
		
		@$pref = rah_sitemap_prefs();
		
		if(!$pref) {
			rah_sitemap_install();
			@$pref = rah_sitemap_prefs();
		}
		
		@$timestampformat = ($pref['timestampformat']) ? $pref['timestampformat'] : 'c';
		
		$out = array();
		$out[] = 
			'<?xml version="1.0" encoding="utf-8"?>'.
			'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.
			'<url><loc>'.hu.'</loc></url>';
			
		// Sections
		
		if($pref['nosections'] != 1) {
			$not = trim($pref['sections']);
			$not = ($not) ? $not.',default' : 'default';
			$not = ($not) ? explode(',',$not) : array();
			$exclude = array();
			foreach ($not as $key => $value) {
				$exclude[$key] = "'".doSlash($value)."'";
			}
			$not = implode(',',$exclude);
			$permlink_section = trim($pref['permlink_section']);
			$rs = safe_rows_start('name,title','txp_section',"name not in($not) order by name asc");
			
			while ($a = nextRow($rs)) {
				extract($a);
				$pretext['s'] = $name;
				$thispage['s'] = $name;
				$thissection['section'] = $name;
				$s = $name;
				$thisarticle['section'] = '';
				
				$out[] = '<url><loc>'.
					(
						($permlink_section) ? 
							trim(strip_tags(parse($permlink_section)))
						: 
							pagelinkurl(array('s' => $name))
					).
					'</loc></url>'
				;
				$pretext['s'] = '';
				$thispage['s'] = '';
				$thissection['section'] = '';
				$s = '';
			}
		}
		
		// Categories: rebuilt
		
		$notypes = array();
		
		if($pref['nocategories'] == 1) $notypes[] = 'article';
		if($pref['nofile'] == 1) $notypes[] = 'file';
		if($pref['noimage'] == 1) $notypes[] = 'image';
		if($pref['nolink'] == 1) $notypes[] = 'link';
		
		$not = trim($pref['categories']);
		$not = ($not) ? $not.',root' : 'root';
		$not = ($not) ? explode(',',$not) : array();
		$permlink_category = trim($pref['permlink_category']);
		$rs = safe_rows_start('name,type,id','txp_category',"name != 'root' order by name asc");
		
		while ($a = nextRow($rs)) {
			extract($a);
			if(in_array($type,$notypes)) continue;
			if(in_array($type.'_||_'.$name,$not)) continue;
			$pretext['c'] = $name;
			$thispage['c'] = $name;
			$thiscategory['c'] = $name;
			$c = $name;
			$out[] = '<url><loc>'.
				(
					($permlink_category) ? 
						str_replace(
							array(
								'[type]',
								'[id]'
							),
							array(
								$type,
								$id
							),
							trim(strip_tags(parse($permlink_category)))
						)
					: 
						pagelinkurl(array('c' => $name))
				).
				'</loc></url>'
			;
			$pretext['c'] = '';
			$thispage['c'] = '';
			$thiscategory['c'] = '';
			$c = '';
		}
		
		// Articles
		
		if($pref['noarticles'] != 1) {
			$not = trim($pref['articlecategories']);
			$not = ($not) ? $not.',root' : 'root';
			$not = ($not) ? explode(',',$not) : array();
			$exclude = array();
			foreach ($not as $key => $value) {
				$exclude[$key] = "'".doSlash($value)."'";
			}
			$notcategory = implode(',',$exclude);
			$not = trim($pref['articlesections']);
			$not = ($not) ? $not.',default' : 'default';
			$not = ($not) ? explode(',',$not) : array();
			$exclude = array();
			foreach ($not as $key => $value) {
				$exclude[$key] = "'".doSlash($value)."'";
			}
			$notsection = implode(',',$exclude);
			$notstatus = trim($pref['articlestatus']);
			$notstatus = '1,2,3'.(($notstatus) ? ','.$notstatus : '');
			$permlink_article = trim($pref['permlink_article']);
			$rs = 
				safe_rows_start(
					(
						($permlink_article) ? 
							'*,  unix_timestamp(Posted) as uPosted, unix_timestamp(Expires) as uExpires, unix_timestamp(LastMod) as uLastMod'
						: 
							'ID, Posted, LastMod'
					),
					'textpattern',
					"Category1 not in($notcategory) and ".
					"Category2 not in($notcategory) and ".
					"Section not in($notsection) and ".
					(($pref['articlefuture']) ? 
						"Posted <= now() and " : ''
					).
					(($pref['articlepast']) ? 
						"Posted > now() and " : ''
					).
					(($pref['articleexpired']) ? 
						"(Expires = '0000-00-00 00:00:00' or Expires >= now()) and " : ''
					).
					"Status not in($notstatus) ".
					"order by Posted desc"
				);
			while ($a = nextRow($rs)) {
				extract($a);
				if($permlink_article) {
					$thisarticle = 
						array(
							'thisid' => $ID,
							'posted' => $uPosted,
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
				}
				$out[] = '<url><loc>'.
					(
						($permlink_article) ? 
							trim(strip_tags(parse($permlink_article)))
						: 
							permlinkurl_id($ID)
					).
					'</loc><lastmod>'.
					(
						($LastMod < $Posted) ? 
							date($timestampformat,strtotime($Posted)) : 
							date($timestampformat,strtotime($LastMod))
					).
					'</lastmod></url>'
				;
				$thisarticle = '';
			}
		}
		
		// Custom URLs
		
		$rs = safe_rows_start('*','rah_sitemap',"1=1 order by posted desc");
		while ($a = nextRow($rs)) {
			extract($a);
			$url = parse($url);
			$out[] = '<url><loc>'.((substr($url,0,4) != 'http') ? hu : '').$url.'</loc>'.(($include == 1) ? '<lastmod>'.date($timestampformat,strtotime($posted)).'</lastmod>' : '').'</url>';
		}
		$out[] = '</urlset>';
		$out = implode('',$out);
		echo (function_exists('gzencode')) ? gzencode($out,9) : $out;
		exit();
	}

	function rah_sitemap_page() {
		global $step;
		require_privs('rah_sitemap');
		rah_sitemap_install();
		if(in_array($step,array(
			'rah_sitemap_save',
			'rah_sitemap_custom_form',
			'rah_sitemap_custom',
			'rah_sitemap_delete'
		))) $step();
		else rah_sitemap_list();
	}

	function rah_sitemap_list($message='') {
		pagetop('rah_sitemap',$message);
		
		$pref = 
			rah_sitemap_prefs();
		
		echo 
			n.'	<form method="post" action="index.php" style="width:900px;margin:0 auto;">'.n.
			'		<h1><strong>rah_sitemap</strong> | Manage your sitemap</h1>'.n.
			'		<p>'.
			'&#187; <a href="?event=rah_sitemap&amp;step=rah_sitemap_custom_form">Add custom URLs</a> '.
			'&#187; <a target="_blank" href="'.hu.'?rah_sitemap=sitemap">View sitemap</a>'.
			'</p>'.n.
			'		<p><input type="submit" value="'.gTxt('save').'" class="publish" /></p>'.n.
			'		<fieldset style="padding:20px;margin:20px 0;">'.n.
			'			<legend>Section and category URL settings</legend>'.n.
			'			<table border="0" cellspacing="0" cellpadding="0" style="width:100%">'.n.
			'				<tr>'.n.
			rah_sitemap_listing('Exclude sections','sections','txp_section',"name != 'default'").
			rah_sitemap_listing('Exclude categories','categories','txp_category',"name != 'root' and title != 'root'").
			'					<td>'.n.
			
			'						<h3>Advanced settings</h3>'.n.
			'						<label><input type="checkbox" name="nosections" value="1"'.(($pref['nosections'] == 1) ? ' checked="checked"' : '').' /> Exclude all section URLs</label><br />'.n.
			'						<label><input type="checkbox" name="nofile" value="1"'.(($pref['nofile'] == 1) ? ' checked="checked"' : '').' /> Exclude all file-type category URLs</label><br />'.n.
			'						<label><input type="checkbox" name="noimage" value="1"'.(($pref['noimage'] == 1) ? ' checked="checked"' : '').' /> Exclude all image-type category URLs</label><br />'.n.
			'						<label><input type="checkbox" name="nolink" value="1"'.(($pref['nolink'] == 1) ? ' checked="checked"' : '').' /> Exclude all link-type category URLs</label><br />'.n.
			'						<label><input type="checkbox" name="nocategories" value="1"'.(($pref['nocategories'] == 1) ? ' checked="checked"' : '').' /> Exclude all article-type category URLs</label>'.n.
			
			'					</td>'.n.
			'				</tr>'.n.
			'			</table>'.n.
			'		</fieldset>'.n.
			'		<fieldset style="padding:20px;margin:20px 0;">'.n.
			'			<legend>Article URL settings</legend>'.n.
			'			<table border="0" cellspacing="0" cellpadding="0" style="width:100%">'.n.
			'				<tr>'.n.
			rah_sitemap_listing('Exclude article sections','articlesections','txp_section',"name != 'default'").
			rah_sitemap_listing('Exclude article categories','articlecategories','txp_category',"name != 'root' and title != 'root' and type = 'article'").
			'					<td>'.n.
			'						<h3>Advanced settings</h3>'.n.
			'						<label><input type="checkbox" name="noarticles" value="1"'.(($pref['noarticles'] == 1) ? ' checked="checked"' : '').' /> Don\'t include articles in sitemap</label><br />'.n.
			'						<label><input type="checkbox" name="articlestatus" value="5"'.(($pref['articlestatus'] == 5) ? ' checked="checked"' : '').' /> Exclude sticky articles</label><br />'.n.
			'						<label><input type="checkbox" name="articlefuture" value="1"'.(($pref['articlefuture'] == 1) ? ' checked="checked"' : '').' /> Exclude future articles</label><br />'.n.
			'						<label><input type="checkbox" name="articlepast" value="1"'.(($pref['articlepast'] == 1) ? ' checked="checked"' : '').' /> Exclude past articles</label><br />'.n.
			'						<label><input type="checkbox" name="articleexpired" value="1"'.(($pref['articleexpired'] == 1) ? ' checked="checked"' : '').' /> Exclude expired articles</label>'.n.
			'					</td>'.n.
			'				</tr>'.n.
			'			</table>'.n.
			'		</fieldset>'.n.
			'		<fieldset style="padding:20px;margin:20px 0;">'.n.
			'			<legend>Timestamp</legend>'.n.
			'			<p>Customize the date format used in last modified timestamps. Use <a href="http://php.net/manual/en/function.date.php">date()</a> string values. If unset (left empty) default ISO 8601 date (<code>c</code>) is used. Use this setting if you want to hard-code/override timestamps, timezones or if your server doesn\'t support <code>c</code> format.</p>'.n.
			'			<label for="rah_sitemap_timestampformat">Format:</label><br />'.n.
			'			<input type="text" class="edit" size="120" name="timestampformat" id="rah_sitemap_timestampformat" value="'.htmlspecialchars($pref['timestampformat']).'" />'.n.
			'		</fieldset>'.n.
			'		<fieldset style="padding:20px;margin:20px 0;">'.n.
			'			<legend>Permlink settings</legend>'.n.
			'			<p>With these settings you can make the Sitemap\'s URLs to match your own URL rewriting rules, or permlinks made by a <em>custom permlink rule</em> plugin. You can leave these fields empty, if using TXP\'s inbuild permlink rules. Note that these setting do not rewrite TXP\'s permlinks for you, use only for matching not rewriting!</p>'.n.
			'			<table border="0" cellspacing="0" cellpadding="0" style="width:100%">'.n.
			'				<tr>'.n.
			'					<td><label for="rah_sitemap_permlink_category">Category URLs:</label></td>'.n.
			'					<td><label for="rah_sitemap_permlink_section">Section URLs:</label></td>'.n.
			'					<td><label for="rah_sitemap_permlink_article">Article URLs:</label></td>'.n.
			'				</tr>'.n.
			'				<tr>'.n.
			'					<td>'.n.
			'						<input type="text" class="edit" size="40" name="permlink_category" id="rah_sitemap_permlink_category" value="'.htmlspecialchars($pref['permlink_category']).'" />'.n.
			'					</td>'.n.
			'					<td>'.n.
			'						<input type="text" class="edit" size="40" name="permlink_section" id="rah_sitemap_permlink_section" value="'.htmlspecialchars($pref['permlink_section']).'" />'.n.
			'					</td>'.n.
			'					<td>'.n.
			'						<input type="text" class="edit" size="40" name="permlink_article" id="rah_sitemap_permlink_article" value="'.htmlspecialchars($pref['permlink_article']).'" />'.n.
			'					</td>'.n.
			'			</table>'.n.
			'		</fieldset>'.n.
			'		<input type="hidden" name="event" value="rah_sitemap" />'.n.
			'		<input type="hidden" name="step" value="rah_sitemap_save" />'.n.
			'	</form>'.n;
	}

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
	
	function rah_sitemap_custom_form($message='') {
		pagetop('rah_sitemap',$message);
		if(gps('edit')) {
			$edit = base64_decode(gps('edit'));
			$edit = doSlash($edit);
		} else $edit = '';
		echo 
			n.'	<form method="post" action="index.php" style="width:900px;margin:0 auto;">'.n.
			'		<h1><strong>rah_sitemap</strong> | Manage custom URLs</h1>'.n.
			//'		<input type="submit" value="'.gTxt('save').'" class="publish" style="position:absolute;top:5px;right:30px;" />'.n.
			'		<p>'.
			'&#187; <a href="?event=rah_sitemap">Sitemap preferences</a> '.
			'&#187; <a target="_blank" href="'.hu.'?rah_sitemap=sitemap">View sitemap</a>'.
			'</p>'.n.
			'		<fieldset style="padding:20px;margin:20px 0;">'.n.
			'			<legend>'.((gps('edit') && safe_count('rah_sitemap',"url='$edit'") > 0) ? 'Copy/replace custom URL' : 'Add custom URL').'</legend>'.n.
			'			<table border="0" cellspacing="0" cellpadding="2" style="width:100%;">'.n.
			'				<tr>'.n.
			'					<td><label for="rah_sitemap_url">URL:</label></td>'.n.
			'					<td><input id="rah_sitemap_url" class="edit" type="text" name="url" value="'.htmlspecialchars($edit).'" size="80" /></td>'.n.
			'				</tr>'.n.
			(
				(gps('edit') && safe_count('rah_sitemap',"url='$edit'") > 0) ?
					'				<tr>'.n.
					'					<td><label for="rah_sitemap_reset">Reset LastMod</label></td>'.n.
					'					<td><input id="rah_sitemap_reset" name="reset" type="checkbox" value="1" checked="checked" /></td>'.n.
					'				</tr>'.n
				:
					''
			).
			'				<tr>'.n.
			'					<td><label for="rah_sitemap_lastmod">Include LastMod</label></td>'.n.
			'					<td>'.n.
			'						<select id="rah_sitemap_lastmod" name="include">'.n.
			'							<option value="0"'.(($edit && fetch('include','rah_sitemap','url',$edit) == 0) ? ' selected="selected"' : '').'>'.gTxt('no').' (Recommended)</option>'.n.
			'							<option value="1"'.(($edit && fetch('include','rah_sitemap','url',$edit) == 1) ? ' selected="selected"' : '').'>'.gTxt('yes').'</option>'.n.
			'						</select>'.n.
			'					</td>'.n.
			'				</tr>'.n.
			'				<tr>'.n.
			'					<td colspan="2" style="text-align: right;"><input type="submit" value="'.gTxt('save').'" class="publish" /></td>'.n.
			'				</tr>'.n.
			'			</table>'.n.
			
			'		</fieldset>'.n.
			'		<input type="hidden" name="event" value="rah_sitemap" />'.n.
			'		<input type="hidden" name="step" value="rah_sitemap_custom" />'.n.
			//'		<h2>Custom sitemap URLs</h2>'.n.
			'	</form>'.n.
			'	<form method="post" action="index.php" style="width:900px;margin:0 auto;" onsubmit="return confirm(\'Are you sure?\');">'.n.
			'		<table id="list" class="list" style="width:100%;" border="0" cellspacing="0" cellpadding="0">'.n.
			'			<tr>'.n.
			'				<th>URL</th>'.n.
			'				<th>LastMod</th>'.n.
			'				<th>Include LastMod</th>'.n.
			'				<th>'.gTxt('view').'</th>'.n.
			'				<th>&#160;</th>'.n.
			'			</tr>'.n;
			
		$rs = safe_rows_start('url,posted,include','rah_sitemap',"1=1 order by posted desc");
		if ($rs and numRows($rs) > 0){
			while ($a = nextRow($rs)) {
				extract($a);
				echo 
					'			<tr>'.n.
					'				<td><a href="?event=rah_sitemap&amp;step=rah_sitemap_custom_form&amp;edit='.base64_encode($url).'">'.$url.'</a></td>'.n.
					'				<td>'.$posted.'</td>'.n.
					'				<td>'.(($include == 1) ? gTxt('yes') : gTxt('no')).'</td>'.n.
					'				<td><a target="_blank" href="'.((substr($url,0,4) != 'http') ? hu : '').$url.'">'.gTxt('view').'</a></td>'.n.
					'				<td><input type="checkbox" name="rah_name[]" value="'.base64_encode($url).'" /></td>'.n.
					'			</tr>'.n;
			}
		} else echo '			<tr><td colspan="5">No custom URLs found.</td></tr>'.n;
		echo 
			'		</table>'.n.
			'		<p style="text-align: right;padding-top:10px;">'.n.
			'			<label for="rah_sitemap_step">With selected:</label>'.n.
			'			<select name="step" id="rah_sitemap_step">'.n.
			'				<option value="">Select...</option>'.n.
			'				<option value="rah_sitemap_delete">Delete</option>'.n.
			'			</select>'.n.
			'			<input type="submit" class="smallerbox" value="Go" />'.n.
			'		</p>'.n.
			'		<input type="hidden" name="event" value="rah_sitemap" />'.n.
			'	</form>'.n;
	}
	
	function rah_sitemap_pref_fields() {
		return
			array(
				'noarticles',
				'nosections',
				'nocategories',
				'articlecategories',
				'articlesections',
				'sections',
				'categories',
				'nofile',
				'noimage',
				'nolink',
				'articlestatus',
				'articlefuture',
				'articlepast',
				'articleexpired',
				'permlink_category',
				'permlink_section',
				'permlink_article',
				'timestampformat'
			);
	}

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
				`name` VARCHAR(255) NOT NULL DEFAULT '',
				`value` LONGTEXT NOT NULL DEFAULT '',
				PRIMARY KEY(`name`)
			)"
		);
		
		
		foreach(rah_sitemap_pref_fields() as $value) {
			if(
				safe_count(
					'rah_sitemap_prefs',
					"name='".doSlash($value)."'"
				) == 0
			) 
				safe_insert(
					'rah_sitemap_prefs',
					"name='".doSlash($value)."', value=''"
				);
			
		}
	}

	function rah_sitemap_custom() {
		extract(doSlash(gpsa(array('url','include'))));
		if(safe_count('rah_sitemap',"url='$url'") == 0) {
			safe_insert(
				'rah_sitemap',
				"url='$url',posted=now(),include='$include'"
			);
			rah_sitemap_custom_form('URL <strong>'.htmlspecialchars(ps('url')).'</strong> updated');
		} else {
			if(ps('reset') == 1) 
				safe_update(
					'rah_sitemap',
					"posted=now(),include='$include'",
					"url='$url'"
				);
			else safe_update(
				'rah_sitemap',
				"include='$include'",
				"url='$url'"
			);
			rah_sitemap_custom_form('URL <strong>'.htmlspecialchars(ps('url')).'</strong> created');
		}
	}

	function rah_sitemap_listing($label='',$field='',$table='',$where='') {
		$exclude = explode(',',fetch('value','rah_sitemap_prefs','name',$field));
		$rs = safe_rows_start('name,title'.(($table == 'txp_category') ? ',type' : ''),$table,"$where order by ".(($table == 'txp_category') ? 'type asc, ' : '')." name asc");
		$out[] = 
			'					<td>'.n.
			'						<h3>'.$label.'</h3>'.n;
		
		if ($rs and numRows($rs) > 0){
			while ($a = nextRow($rs)) {
				extract($a);
				$out[] = '						<label><input type="checkbox" name="'.$field.'[]" value="'.(($field == 'categories') ? $type.'_||_' : '').$name.'"'.((in_array((($field == 'categories') ? $type.'_||_' : '').$name,$exclude)) ? ' checked="checked"' : '').' /> '.(($field == 'categories') ? ucfirst($type).': ' : '').$title.'</label><br />'.n;
			}
		} else $out[] = '						Nothing found.'.n;
		$out[] = 
			'					</td>'.n;
		return implode('',$out);
	}

	function rah_sitemap_save() {
		foreach(rah_sitemap_pref_fields() as $value) 
			rah_sitemap_update(ps($value),$value);
		rah_sitemap_list('Sitemap preferences saved');
	}

	function rah_sitemap_delete() {
		$selected = ps('rah_name');
		if(!is_array($selected)) {
			rah_sitemap_custom_form('Nothing removed.');
			return;
		}
		$i = 0;
		foreach($selected as $name) {
			$name = base64_decode($name);
			$sql = safe_delete('rah_sitemap',"url='".doSlash($name)."'");
			if($sql)
				$i++;
		}
		rah_sitemap_custom_form($i.' deleted.');
	}

	function rah_sitemap_update($array='',$column='') {
		$val = (is_array($array)) ? doSlash(implode(',',$array)) : doSlash($array);
		safe_update('rah_sitemap_prefs',"value='$val'","name='$column'");
	}?>
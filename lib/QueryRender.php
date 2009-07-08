<?php
// $Header$

/**
 * @author The phpLDAPadmin development team
 * @package phpLDAPadmin
 */

/**
 * QueryRender class
 *
 * @package phpLDAPadmin
 * @subpackage Templates
 * @todo need to add paging
 */
class QueryRender extends PageRender {
	/** CORE FUNCTIONS **/

	/**
	 * Intialise and Render the QueryRender
	 */
	public function accept() {
		if (DEBUG_ENABLED)
			debug_log('Entered with ()',1,__FILE__,__LINE__,__METHOD__);

		if (DEBUGTMP) printf('<font size=-2>%s</font><br />',__METHOD__);
		if (DEBUGTMP||DEBUGTMPSUB) printf('<font size=-2>* %s [GETquery:%s]</font><br />',__METHOD__,get_request('query','REQUEST'));
		if (DEBUGTMP||DEBUGTMPSUB) printf('<font size=-2>* %s [Page:%s]</font><br />',__METHOD__,get_request('page','REQUEST'));

		$this->template_id = $this->getTemplateChoice();
		$this->page = get_request('page','REQUEST',false,1);

		if ($this->template_id) {
			$templates = $this->getTemplates();
			$this->template = $templates->getTemplate($this->template_id);
			$this->template->accept();

			$this->visitStart();
			$this->visitEnd();
		}
	}

	/**
	 * Get our templates applicable for this object
	 */
	protected function getTemplates() {
		return new Queries($this->server_id);
	}

	/**
	 * Are default queries enabled?
	 */
	protected function haveDefaultTemplate() {
		$server = $this->getServer();

		if ($server->getValue('query','disable_default'))
			return false;
		else
			return true;
	}

	protected function drawTemplateChoice() {
		if (DEBUGTMP) printf('<font size=-2>%s</font><br />',__METHOD__);

		$server = $this->getServer();

		$this->drawTitle(_('Search'));
		$this->drawSubTitle();

		echo "\n";

		$baseDNs = $server->getBaseDN();

		echo '<center>';
		echo '<form action="cmd.php" name="advanced_search_form">';
		echo '<input type="hidden" name="cmd" value="query_engine" />';
		printf('<input type="hidden" name="server_id" value="%s" />',$server->getIndex());

		echo '<table class="forminput" border=0>';
		echo '<tr><td colspan=2>&nbsp;</td></tr>';

		$templates = $this->getTemplates();

		if (count($templates->getTemplates())) {
			echo '<tr>';
			printf('<td><acronym title="%s">%s</acronym></td>',_('Run a predefined query'),_('Predefined Query'));
			echo '<td>';
			echo '<select name="query">';
			if ($this->haveDefaultTemplate())
				printf('<option value="%s" %s>%s</option>','none','',('Custom Query'));

			foreach ($templates->getTemplates() as $template)
				printf('<option value="%s" %s>%s</option>',
					$template->getID(),
					($this->template_id == $template->getID() ? 'selected' : ''),
					$template->getDescription());
			echo '</select>';
			echo '</td>';
			echo '</tr>';
		}

		printf('<td><acronym title="%s">%s</acronym></td>',_('The format to show the query results'),_('Display Format'));
		echo '<td>';
		echo '<select name="format" style="width: 200px">';

		printf('<option value="list" %s>%s</option>',
			$_SESSION[APPCONFIG]->getValue('search','display') == 'list' ? 'selected' : '',_('List'));
		printf('<option value="table" %s>%s</option>',
			$_SESSION[APPCONFIG]->getValue('search','display') == 'table' ? 'selected' : '',_('Table'));

		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		printf('<td><acronym title="%s">%s</acronym></td>',_('Entries to show per page'),_('Show Results'));
		echo '<td>';
		echo '<select name="showresults" style="width: 200px">';

		printf('<option value="na" %s>%s</option>',
			'','NA');

		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';

		echo '<td colspan=2>';
		printf('<div id="customquery" style="display: %s">','block');
		echo '<br/>';
		echo '<fieldset>';
		printf('<legend>%s</legend>',_('Custom Query'));
		echo '<table border=0></tr>';

		printf('<td>%s</td>',_('Base DN'));
		printf('<td><input type="text" name="base" value="%s" style="width: 200px" id="base" />',count($baseDNs) == 1 ? $baseDNs[0] : '');

		draw_chooser_link('advanced_search_form.base');

		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		printf('<td><acronym title="%s">%s</acronym></td>',_('The scope in which to search'),_('Search Scope'));

		echo '<td>';
		echo '<select name="scope" style="width: 200px">';

		printf('<option value="sub" %s>%s</option>',
			'',_('Sub (entire subtree)'));

		printf('<option value="one" %s>%s</option>',
			'',_('One (one level beneath base)'));

		printf('<option value="base" %s>%s</option>',
			'',_('Base (base dn only)'));

		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		printf('<td><acronym title="%s">%s</acronym></td>',
			htmlspecialchars(_('Standard LDAP search filter. Example: (&(sn=Smith)(givenName=David))')),_('Search Filter'));

		printf('<td><input type="text" name="filter" id="filter" style="width: 200px" value="%s" /></td>',
			'objectClass=*');

		echo '</tr>';

		echo '<tr>';
		printf('<td><acronym title="%s">%s</acronym></td>',
			_('A list of attributes to display in the results (comma-separated)'),_('Show Attributes'));

		printf('<td><input type="text" name="display_attrs" style="width: 200px" value="%s" /></td>',
			implode(', ',$_SESSION[APPCONFIG]->getValue('search','result_attributes')));
		echo '</tr>';

		echo '<tr>';
		printf('<td><acronym title="%s">%s</acronym></td>',_('Order by'),_('Order by'));
		printf('<td><input type="text" name="orderby" id="orderby" style="width: 200px" value="%s" /></td>','');

		echo '</tr></table>';
		echo '</fieldset>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';

		printf('<tr><td colspan="2"><br /><center><input type="submit" value="%s" /></center></td></tr>',_('Search'));

		echo '</table>';
		echo '</form>';
		echo '</center>';
	}

	private function visitStart() {
		$this->drawTitle(_('Search Results'));
		$this->drawSubTitle();
		echo '<br/>';
	}

	private function visitEnd() {
		$server = $this->getServer();
		$afattrs = $this->getAFAttrs();

		# If Mass Actions Enabled
		if ($_SESSION[APPCONFIG]->getValue('mass','enabled')) {
			$mass_actions = array(
				'&nbsp;' => '',
				_('delete') => 'mass_delete',
				_('edit') => 'mass_edit'
			);
		}

		# Display the Javascript that enables us to show/hide DV entries
		echo '<script type="text/javascript" language="javascript">';
		echo "
function showthis(key,item) {
	select = document.getElementById(key+item);
	if (select.style.display == '')
		return false;

		hideall(key,item);

		return false;
};

function hideall(key,except) {
	items = items();
	for (x in items) {
		if (! isNaN(x) && except != items[x]) {
			item = document.getElementById(key+items[x]);
			item.style.display = 'none';
			item = document.getElementById('CTL'+items[x]);
			item.style.background = '#E0E0E0';

		} else if (! isNaN(x) && except == items[x]) {
			item = document.getElementById(key+items[x]);
			item.style.display = '';
			item = document.getElementById('CTL'+items[x]);
			item.style.background = '#F0F0F0';
		}
	}
}

		";
		echo '</script>';
		echo "\n\n";

		$this->drawBaseTabs();

		switch(get_request('format','REQUEST',false,'table')) {
			case 'list':

				$counter = 0;
				foreach ($this->template->results as $base => $results) {
					if (! $show = get_request('show','REQUEST'))
						$show = ($counter++ === 0 ? $this->getAjaxRef($base) : null);

					printf('<div id="DN%s" style="display: %s">',
						$this->getAjaxRef($base),
						($show == $this->getAjaxRef($base) ? '' : 'none'));

					echo '<table class="result_box" border=0 width=100%>';
					echo '<tr><td>';
					echo '<br/>';
					echo '<br/>';

					$this->drawResultsTable($base,count($results));

					echo '<br/>';
					echo '<br/>';

					foreach ($results as $dn => $dndetails) {
						$dndetails = array_change_key_case($dndetails);

						# Temporarily set our DN, for rendering that leverages our DN (eg: JpegPhoto)
						$this->template->setDN($dn);

						echo '<table class="result" border=0>';

						echo '<tr class="list_title">';
						printf('<td class="icon"><img src="%s/%s" alt="icon" /></td>',IMGDIR,get_icon($server->getIndex(),$dn));

						printf('<td colspan=2><a href="cmd.php?cmd=template_engine&amp;server_id=%s&amp;dn=%s">%s</a></td>',
							$server->getIndex(),rawurlencode(dn_unescape($dn)),htmlspecialchars(get_rdn($dn)));
						echo '</tr>';

						printf('<tr class="list_item"><td class="blank">&nbsp;</td><td class="heading">dn</td><td class="value">%s</td></tr>',
							htmlspecialchars(dn_unescape($dn)));

						# Iterate over each attribute for this entry
						foreach (explode(',',$this->template->getAttrDisplayOrder()) as $attr) {
							# Ignore DN, we've already displayed it.
							if ($attr == 'dn')
								continue;

							if (! isset($dndetails[$attr]))
								continue;

							# Set our object with our values
							$afattrs[$attr]->clearValue();

							if (is_array($dndetails[$attr]))
								$afattrs[$attr]->initValue($dndetails[$attr]);
							else
								$afattrs[$attr]->initValue(array($dndetails[$attr]));

							echo '<tr class="list_item">';
							echo '<td class="blank">&nbsp;</td>';

							echo '<td class="heading">';
							$this->draw('Name',$afattrs[$attr]);
							echo '</td>';

							echo '<td>';
							$this->draw('CurrentValues',$afattrs[$attr]);
							echo '</td>';
							echo '</tr>';
						}

						echo '</table>';
						echo '<br/>';
					}

					echo '</td></tr>';
					echo '</table>';
					echo '</div>';

					echo "\n\n";
				}

				break;

			case 'table':

				# Display the results.
				$counter = 0;
				foreach ($this->template->results as $base => $results) {
					if (! $show = get_request('show','REQUEST'))
						$show = ($counter++ === 0 ? $this->getAjaxRef($base) : null);

					printf('<div id="DN%s" style="display: %s">',
						$this->getAjaxRef($base),
						($show == $this->getAjaxRef($base) ? '' : 'none'));

					echo '<table class="result_box" border=0 width=100%>';
					echo '<tr><td>';
					echo '<br/>';
					echo '<br/>';

					$this->drawResultsTable($base,count($results));

					echo '<br/>';
					echo '<br/>';

					if (! $results) {
						echo _('Search returned no results');
						echo '</td></tr></table>';
						echo '</div>';
						continue;
					}

					echo '<form action="cmd.php" method="post" name="massform">';
					printf('<input type="hidden" name="server_id" value="%s" />',$server->getIndex());

					foreach ($this->template->resultsdata[$base]['attrs'] as $attr)
						printf('<input type="hidden" name="attrs[]" value="%s" />',$attr);

					echo '<table class="result_table" border=0>';

					echo '<thead class="fixheader">';
					echo '<tr class="heading">';
					echo '<td>&nbsp;</td>';
					echo '<td>&nbsp;</td>';

					foreach (explode(',',$this->template->getAttrDisplayOrder()) as $attr) {
						echo '<td>';
						$this->draw('Name',$afattrs[$attr]);
						echo '</td>';
					}

					echo '</tr>';
					echo '</thead>';

					echo '<tbody class="scroll">';
					$counter = 0;
					foreach ($results as $dn => $dndetails) {
						$counter++;
						$dndetails = array_change_key_case($dndetails);

						# Temporarily set our DN, for rendering that leverages our DN (eg: JpegPhoto)
						$this->template->setDN($dn);

						printf('<tr class="%s" id="tr_ma%s" onClick="var cb=document.getElementById(\'ma%s\'); cb.checked=!cb.checked;">',
							$counter%2 ? 'odd' : 'even',$counter,$counter);

						# Is mass action enabled.
						if ($_SESSION[APPCONFIG]->getValue('mass','enabled'))
							printf('<td><input type="checkbox" id="ma%s" name="dn[]" value="%s" onclick="this.checked=!this.checked;" /></td>',$counter,$dn);

						$href = sprintf('cmd=template_engine&server_id=%s&dn=%s',$server->getIndex(),rawurlencode($dn));
						printf('<td class="icon"><a href="cmd.php?%s"><img src="%s/%s" alt="icon" /></a></td>',
							htmlspecialchars($href),
							IMGDIR,get_icon($server->getIndex(),$dn));

						# We'll clone our attribute factory attributes, since we need to add the values to them for rendering.
						foreach (explode(',',$this->template->getAttrDisplayOrder()) as $attr) {
							# If the entry is blank, we'll draw an empty box and continue.
							if (! isset($dndetails[$attr])) {
								echo '<td>&nbsp;</td>';
								continue;
							}

							# Special case for DNs
							if ($attr == 'dn') {
								$dn_display = strlen($dn) > 40
									? sprintf('<acronym title="%s">%s...</acronym>',htmlspecialchars($dn),htmlspecialchars(substr($dn,0,40)))
									: htmlspecialchars($dn);

								printf('<td><a href="cmd.php?%s">%s</a></td>',htmlspecialchars($href),$dn_display);
								continue;
							}

							# Set our object with our values
							$afattrs[$attr]->clearValue();
							if (is_array($dndetails[$attr]))
								$afattrs[$attr]->initValue($dndetails[$attr]);
							else
								$afattrs[$attr]->initValue(array($dndetails[$attr]));

							echo '<td>';
							$this->draw('CurrentValues',$afattrs[$attr]);
							echo '</td>';
						}

						echo '</tr>';
					}

					# Is mass action enabled.
					if ($_SESSION[APPCONFIG]->getValue('mass','enabled')) {
						printf('<tr class="%s">',++$counter%2 ? 'odd' : 'even',$counter);
						echo '<td><input type="checkbox" name="allbox" value="1" onclick="CheckAll(1);" /></td>';
						printf('<td colspan=%s>',2+count(explode(',',$this->template->getAttrDisplayOrder())));
						echo '<select name="cmd" onChange="if (this.value) submit();" style="font-size: 12px">';
						foreach ($mass_actions as $action => $display)
							printf('<option value="%s">%s</option>',$display,$action);
						echo '</select>';
						echo '</td>';
						echo '</tr>';
					}

					echo '</tbody>';
					echo '</table>';
					echo '</form>';
					echo '</td></tr>';
					echo '</table>';
					echo '</div>';
					echo "\n\n";

					echo '<script type="text/javascript" language="javascript">'."\n";
					echo "
function CheckAll(setbgcolor) {
var deon=0;
	for (var i=0;i<document.massform.elements.length;i++) {
		var e = document.massform.elements[i];
		if (e.type == 'checkbox' && e.name != 'allbox') {
			e.checked = document.massform.allbox.checked;
			if (!document.layers && setbgcolor) {
				var tr = document.getElementById('tr_'+e.id);
				if (e.checked) {
					tr.style.backgroundColor='#DDDDFF';
				} else {
					var id = e.id.substr(2);
					tr.style.backgroundColor= id%2 ? '#F0F0F0' : '#E0E0E0';
				}
			}
		}
	}
}
";
					echo '</script>';
				}

				break;

			default:
				printf('Have ID [%s], run this query for page [%s]',$this->template_id,$this->page);
		}
	}

	public function drawSubTitle($subtitle=null) {

		if (is_null($subtitle)) {
			$server = $this->getServer();
	
			$subtitle = sprintf('%s: <b>%s</b>',_('Server'),$server->getName());

			if ($this->template_id) {
				$subtitle .= '<br />';
				$subtitle .= sprintf('%s: <b>%s</b>',('Query'),$this->template->getID() != 'none' ? $this->template->getTitle() : _('Default'));
				if ($this->template->getName())
					$subtitle .= sprintf(' (<b>%s</b>)',$this->template->getName(false));
			}
		}

		parent::drawSubTitle($subtitle);
	}

	private function getAFattrs() {
		$attribute_factory = new AttributeFactory();
		$results = array();

		foreach (explode(',',$this->template->getAttrDisplayOrder()) as $attr)
			$results[$attr] = $attribute_factory->newAttribute($attr,array('values'=>array()),$this->getServerID());

		return $results;
	}

	private function getAjaxRef($dn) {
		return preg_replace('/=/','.',base64_encode($dn));
	}

	private function drawBaseTabs() {
		# Setup the Javascript to show/hide our DIVs.
		echo '<script type="text/javascript" language="javascript">';
		echo 'function items() {';
		echo 'var $items = new Array();';
		$counter = 0;
		foreach ($this->template->results as $base => $results)
			printf("items[%s] = '%s';",$counter++,$this->getAjaxRef($base));
		echo 'return items;';
		echo '}</script>';
		echo "\n\n";

		echo '<table class="result_table" border=0>';
		echo '<tr>';
		$counter = 0;
		foreach ($this->template->results as $base => $results) {
			if (! $show = get_request('show','REQUEST'))
				$show = ($counter++ === 0 ? $this->getAjaxRef($base) : null);

			printf('<td id="CTL%s" onclick="return showthis(\'DN\',\'%s\');" style="background-color: %s;">%s</td>',
				$this->getAjaxRef($base),
				$this->getAjaxRef($base),
				($show == $this->getAjaxRef($base) ? '#F0F0F0' : '#E0E0E0'),
				$base);
		}
		echo '</tr>';
		echo '</table>';
		echo "\n\n";
	}

	private function drawResultsTable($base,$results) {
		$server = $this->getServer();

		echo '<table class="result" border=0>';

		echo '<tr>';
		printf('<td>%s: <b>%s</b><br/><br/><div class="execution_time">(%s %s)</div></td>',_('Entries found'),
			number_format($results),$this->template->resultsdata[$base]['time'],_('seconds'));

		if ($_SESSION[APPCONFIG]->isCommandAvailable('export')) {
			$href = htmlspecialchars(sprintf('cmd.php?cmd=export_form&server_id=%s&scope=%s&dn=%s&filter=%s&attributes=%s',
				$server->getIndex(),$this->template->resultsdata[$base]['scope'],
				$base,rawurlencode($this->template->resultsdata[$base]['filter']),
				rawurlencode(implode(', ',$this->template->resultsdata[$base]['attrs']))));

			printf('<td style="text-align: right; width: 85%%"><small>[ <a href="%s"><img src="%s/save.png" alt="Save" /> %s</a> ]</small>',
				$href,IMGDIR,_('export results'));
		}

		printf('<small>[ <img src="%s/rename.png" alt="rename" /> %s:',IMGDIR,_('Format'));

		foreach (array('list','table') as $f) {
			echo '&nbsp;';

			if (get_request('format','REQUEST') == $f) {
				printf('<b>%s</b>',_($f));

			} else {
				$query_string = array_to_query_string($_GET,array('format','cmd'));
				$query_string .= sprintf('&format=%s&show=%s',$f,$this->getAjaxRef($base));
				printf('<a href="cmd.php?cmd=query_engine&amp;%s">%s</a>',htmlspecialchars($query_string),_($f));
			}
		}

		echo ' ]</small>';

		echo '<br />';
		printf('<small>%s: <b>%s</b></small>',_('Base DN'),$base);

		echo '<br />';
		printf('<small>%s: <b>%s</b></small>',_('Filter performed'),htmlspecialchars($this->template->resultsdata[$base]['filter']));

		echo '</td>';
		echo '</tr>';
		echo '</table>';
	}
}
?>
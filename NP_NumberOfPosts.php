<?php
 
/**
  * Nucleus Plugin -- NP_NumberOfPosts
  * This plugin can be used to display the number of posts (divided into categories)
  * 
  * History:
  * 1.9 - add <%NumberOdPosts(shname)%>
  * 1.8 - fixed incorect object use of $blog
  * 1.7a - Fixed typo
  * 1.7 - Fixed time offset bug
  * 1.6 - Used sql_query
  * 1.5 - Added formatting template options
  * 1.4 - Added category description in mouse over link tooltip
  * 1.3 - Fixed XHTML warning
  *     - Fixed draft counting bug
  *     - Added option to show total count on [Show All]
  *     - Fixed for FancyURL
  * 1.2 - Added support for SqlTablePrefix, needed in 2.0.      [Excaliber - http://www.tong-web.com]
  *     - Added support for FancierURL type link   [Excaliber - http://www.tong-web.com]
  * 1.1 - some improvements and fixes (see: http://forum.nucleuscms.org/viewtopic.php?t=1770 )
  * 1.0 - original version (Written by Daniel Santos - http://danielsantos.f2o.org/ )
  */
class NP_NumberOfPosts extends NucleusPlugin {
 
 
 
    function getEventList() { return array(); }
    function getName() { return 'Number of Posts'; }
    function getAuthor()  { return 'Daniel Santos | Excaliber | admun'; }
    function getURL()  { return 'http://danielsantos.f2o.org/'; }
    function getVersion() { return '1.9'; }
    function getDescription() {
        return 'This plugin can be used to display the number of posts (divided into categories).';
    }
 
    //put in support for SqlTablePrefix, needed in 2.0
    function supportsFeature($what) {return in_array($what,array('SqlTablePrefix','SqlApi'));}
 
    function install() { 
        $this->createOption('head_format','Header formatting','textarea','<ul>');
        $this->createOption('all_format','All category formatting','textarea','<li><a href="%l">%t</a></li>');
        $this->createOption('lnk_format','Link formatting','textarea','<li><a href="%l" title="%d">%t</a> [%c]</li>');
        $this->createOption('foot_format','Footer formatting','textarea','</ul>');
    }
 
    function link_format($l, $t, $d, $c, $f) {
        $out = str_replace('%l', $l, $f);
        $out = str_replace('%d', $d, $out);
        $out = str_replace('%t', $t, $out);
        $out = str_replace('%c', $c, $out);
        return $out;
    }
 
    // skinvar plugin can have a blogname as second parameter
    function doSkinVar($skinType, $shName = '') {
        global $manager, $blog, $CONF;
 
        if ($blog) {
             $b =& $blog;
        }
        else {
            $b =& $manager->getBlog($CONF['DefaultBlog']);
        }
 
        // maybe I should check blog shortname exist..... too lazy... but it's a potential issue
        if ($shName == '') {
            $shName = $b->getShortName();
        }
 
        // here is the query
        $offset_date = $b->getCorrectTime();
 
        $query_total = 'SELECT count(*) as total
                  FROM '.sql_table('category').' a, '.sql_table('item').' b, '.sql_table('blog').' c
                  WHERE a.catid = b.icat
                  and b.iblog = c.bnumber
                  and b.itime <= ' . mysqldate($offset_date) . '
                  and b.idraft = \'0\'
                  and c.bshortname = "'.$shName.'" ';
 
        $total_numPosts = sql_query($query_total);
        $total_row = sql_fetch_object($total_numPosts);
        $total_num = $total_row->total; 
 
	// Header
	echo($this->getOption('head_format'));
 
        // creates link for all category
        $all_row = $this->link_format($b->getURL(), 'All', '', $total_num, $this->getOption('all_format'));
	echo $all_row;
 
        // here is the query
        $query = 'SELECT a.catid, a.cdesc, a.cname, count(b.ititle) as ammount FROM '.sql_table('category').' a, '.sql_table('item').' b, '.sql_table('blog').' c
                  WHERE a.catid = b.icat
                  and b.iblog = c.bnumber
                  and b.itime <= ' . mysqldate($offset_date) . '
                  and b.idraft = \'0\'
                  and c.bshortname = "'.$shName.'"
                  GROUP BY a.cname
                  ORDER BY 2';
 
        $numPosts = sql_query($query);
 
        // outputs list   
        while($row = sql_fetch_object($numPosts)) {
            $catLink = createCategoryLink($row->catid,'');
            $catName  = $row->cname;
 
            $out = $this->link_format(createCategoryLink($row->catid), $catName, $row->cdesc, $row->ammount, $this->getOption('lnk_format'));
            echo($out);
        }
 
	// Footer
	echo($this->getOption('foot_format'));
    }
 
}

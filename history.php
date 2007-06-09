<?php
/***************************************************************************
Copyright (C) 2006 - 2007 Scuttle project
http://sourceforge.net/projects/scuttle/
http://scuttle.org/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
***************************************************************************/

require_once('header.inc.php');

$bookmarkservice    =& ServiceFactory::getServiceInstance('BookmarkService');
$cacheservice       =& ServiceFactory::getServiceInstance('CacheService');
$templateservice    =& ServiceFactory::getServiceInstance('TemplateService');
$userservice        =& ServiceFactory::getServiceInstance('UserService');

$hash = isset($_GET['query']) ? $_GET['query'] : NULL;

// Set user details if logged on
$isLoggedOn = $userservice->isLoggedOn();
if ($isLoggedOn) {
    $currentUser        = $userservice->getCurrentUser();
    $currentUsername    = $currentUser[$userservice->getFieldName('username')];
}

$tplVars = array();

if ($usecache) {
    // Generate hash for caching on
    $hashtext = $_SERVER['REQUEST_URI'];
    if ($isLoggedOn) {
        $hashtext .= $currentUsername;
    }
    $cachehash = md5($hashtext);

    // Cache for 30 minutes
    $cacheservice->Start($cachehash, 1800);
}

// Pagination
$perpage = getPerPageCount();
if (isset($_GET['page']) && intval($_GET['page']) > 1) {
    $page = $_GET['page'];
    $start = ($page - 1) * $perpage;
} else {
    $page = 0;
    $start = 0;
}

$template = 'bookmarks.tpl';
if ($bookmark =& $bookmarkservice->getBookmarkByHash($hash)) {
    // Template variables
    $bookmarks =& $bookmarkservice->getBookmarks($start, $perpage, NULL, NULL, NULL, getSortOrder(), NULL, NULL, NULL, $hash);
    $tplVars['pagetitle'] = T_('History') .': '. $bookmark['bAddress'];
    $tplVars['subtitle'] = sprintf(T_('History for %s'), $bookmark['bAddress']);
    $tplVars['loadjs'] = true;
    $tplVars['page'] = $page;
    $tplVars['start'] = $start;
    $tplVars['bookmarkCount'] = $start + 1;
    $tplVars['total'] = $bookmarks['total'];
    $tplVars['bookmarks'] =& $bookmarks['bookmarks'];
    $tplVars['hash'] = $hash;
    $tplVars['popCount'] = 50;
    $tplVars['sidebar_blocks'] = array('common');
    $tplVars['cat_url'] = createURL('tags', '%2$s');
    $tplVars['nav_url'] = createURL('history', $hash .'/%3$s');
} else {
    // Throw a 404 error
    $template           = 'error.404.tpl';
    $tplVars['error']   = T_('Address was not found');
}

// Sorting
$tplVars['sortOrders'] = array(
    array(
        'link'  => '?sort=date_desc',
        'title' => T_('Sort by date'),
        'text'  => T_('Date')
    ),
    array(
        'link'  => '?sort=title_asc',
        'title' => T_('Sort by title'),
        'text'  => T_('Title')
    )
);

$tplVars['isLoggedOn']      = $isLoggedOn;
$tplVars['currentUsername'] = $currentUsername;
$templateservice->loadTemplate($template, $tplVars);

if ($usecache) {
    // Cache output if existing copy has expired
    $cacheservice->End($cachehash);
}
?>
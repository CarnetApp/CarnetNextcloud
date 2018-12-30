<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\QuickDoc\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */

$this->create('carnet_writer','/writer')->actionInclude('carnet/templates/writer.php');
 
return [
    'routes' => [
       ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
       ['name' => 'page#settings', 'url' => '/settings', 'verb' => 'GET'],
       ['name' => 'note#getNotePath', 'url' => '/settings/note_path', 'verb' => 'GET'],
       ['name' => 'note#setNotePath', 'url' => '/settings/note_path', 'verb' => 'POST'],
       ['name' => 'note#isFirstRun', 'url' => '/settings/isfirstrun', 'verb' => 'GET'],
       ['name' => 'note#setAppTheme', 'url' => '/settings/app_theme', 'verb' => 'POST'],
       ['name' => 'note#getAppThemes', 'url' => '/settings/themes', 'verb' => 'GET'],
       ['name' => 'note#getEditorCss', 'url' => '/settings/editor_css', 'verb' => 'GET'],
       ['name' => 'note#getSettingsCss', 'url' => '/settings/settings_css', 'verb' => 'GET'],
       ['name' => 'note#getBrowserCss', 'url' => '/settings/browser_css', 'verb' => 'GET'],

       ['name' => 'page#do_echo', 'url' => '/echo', 'verb' => 'POST'],
       ['name' => 'note#openNote', 'url' => '/note/open', 'verb' => 'GET'],
       ['name' => 'note#createNote', 'url' => '/note/create', 'verb' => 'GET'],
       ['name' => 'note#saveTextToOpenNote', 'url' => '/note/saveText', 'verb' => 'POST'],
       ['name' => 'note#addMediaToOpenNote', 'url' => '/note/open/{id}/addMedia', 'verb' => 'POST'],
       ['name' => 'note#updateMetadata', 'url' =>'notes/metadata', 'verb' => 'POST'],
       ['name' => 'note#deleteMediaFromOpenNote', 'url' => 'note/open/{id}/media', 'verb' => 'DELETE'],
       ['name' => 'note#listMediaOfOpenNote', 'url' => '/note/open/{id}/listMedia', 'verb' => 'GET'],
       ['name' => 'note#getMediaOfOpenNote', 'url' => '/note/open/{id}/getMedia/{media}', 'verb' => 'GET'],

       ['name' => 'note#listDir', 'url' => '/browser/list', 'verb' => 'GET'],
       ['name' => 'note#newFolder', 'url' => '/browser/newfolder', 'verb' => 'POST'],
       ['name' => 'note#getRecent', 'url' => '/recentdb', 'verb' => 'GET'],
       ['name' => 'note#mergeRecentDB', 'url' => '/recentdb/merge', 'verb' => 'GET'],
       ['name' => 'note#getEditorUrl', 'url' => '/note/open/prepare', 'verb' => 'GET'],
       ['name' => 'note#postActions', 'url' => '/recentdb/action', 'verb' => 'POST'],
       ['name' => 'note#getMetadata', 'url' => '/metadata', 'verg' => 'GET'],
       ['name' => 'note#getKeywordsDB', 'url' => '/keywordsdb', 'verb' => 'GET'],
       ['name' => 'note#mergeKeywordsDB', 'url' => '/keywordsdb/merge', 'verb' => 'GET'],
       ['name' => 'note#postKeywordsActions', 'url' => '/keywordsdb/action', 'verb' => 'POST'],

       ['name' => 'note#create', 'url' => '/notes', 'verb' => 'POST'],
       ['name' => 'note#downloadArchive', 'url' => '/notes/export', 'verb' => 'GET'],
       ['name' => 'note#moveNote', 'url' => '/notes/move', 'verb' => 'POST'],
       ['name' => 'note#deleteNote', 'url' => '/notes', 'verb' => 'DELETE'],
       ['name' => 'note#search', 'url' => '/notes/search', 'verb' => 'GET'],
       ['name' => 'note#getSearchCache', 'url' => '/notes/getSearchCache', 'verb' => 'GET'],
       ['name' => 'note#getUbuntuFont', 'url' => '/templates/CarnetElectron/fonts/ubuntu.woff2', 'verb' => 'GET' ],
       ['name' => 'note#getMaterialFont', 'url' => '/templates/CarnetElectron/fonts/material-icons.woff2', 'verb' => 'GET' ],
       ['name' => 'note#getChangelog', 'url' => '/settings/changelog', 'verb' => 'GET' ],

    ]
];
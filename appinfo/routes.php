<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\QuickDoc\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */


return [
    'routes' => [
       ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
       ['name' => 'page#writer', 'url' => '/writer', 'verb' => 'GET'],
       ['name' => 'page#browser', 'url' => '/browser', 'verb' => 'GET'],

       ['name' => 'page#importer', 'url' => '/api/importer', 'verb' => 'GET'],
       ['name' => 'page#exporter', 'url' => 'exporter/exporter.html', 'verb' => 'GET'],

       ['name' => 'page#settings', 'url' => '/api/settings', 'verb' => 'GET'],
       ['name' => 'note#getNotePath', 'url' => '/api/settings/note_path', 'verb' => 'GET'],
       ['name' => 'note#setNotePath', 'url' => '/api/settings/note_path', 'verb' => 'POST'],
       ['name' => 'note#isFirstRun', 'url' => '/api/settings/isfirstrun', 'verb' => 'GET'],
       ['name' => 'note#setAppTheme', 'url' => '/api/settings/app_theme', 'verb' => 'POST'],
       ['name' => 'note#getAppThemes', 'url' => '/api/settings/themes', 'verb' => 'GET'],
       ['name' => 'note#getEditorCss', 'url' => '/api/settings/editor_css', 'verb' => 'GET'],
       ['name' => 'note#getSettingsCss', 'url' => '/api/settings/settings_css', 'verb' => 'GET'],
       ['name' => 'note#getBrowserCss', 'url' => '/api/settings/browser_css', 'verb' => 'GET'],

       ['name' => 'note#openNote', 'url' => '/api/note/open', 'verb' => 'GET'],
       ['name' => 'note#extractNote', 'url' => '/api/note/extract', 'verb' => 'GET'],
       ['name' => 'note#createNote', 'url' => '/api/note/create', 'verb' => 'GET'],
       ['name' => 'note#saveTextToOpenNote', 'url' => '/api/note/saveText', 'verb' => 'POST'],
       ['name' => 'note#addMediaToOpenNote', 'url' => '/api/note/open/{id}/addMedia', 'verb' => 'POST'],
       ['name' => 'note#updateMetadata', 'url' =>'notes/metadata', 'verb' => 'POST'],
       ['name' => 'note#deleteMediaFromOpenNote', 'url' => '/api/note/open/{id}/media', 'verb' => 'DELETE'],
       ['name' => 'note#listMediaOfOpenNote', 'url' => '/api/note/open/{id}/listMedia', 'verb' => 'GET'],
       ['name' => 'note#getMediaOfOpenNote', 'url' => '/api/note/open/{id}/getMedia/{media}', 'verb' => 'GET'],

       ['name' => 'note#listDir', 'url' => '/api/browser/list', 'verb' => 'GET'],
       ['name' => 'note#newFolder', 'url' => '/api/browser/newfolder', 'verb' => 'POST'],
       ['name' => 'note#getRecent', 'url' => '/api/recentdb', 'verb' => 'GET'],
       ['name' => 'note#mergeRecentDB', 'url' => '/api/recentdb/merge', 'verb' => 'GET'],
       ['name' => 'note#getEditorUrl', 'url' => '/api/note/open/prepare', 'verb' => 'GET'],
       ['name' => 'note#getMedia', 'url' => '/api/note/getmedia', 'verb' => 'GET'],
       ['name' => 'note#postActions', 'url' => '/api/recentdb/action', 'verb' => 'POST'],
       ['name' => 'note#getMetadata', 'url' => '/api/metadata', 'verg' => 'GET'],
       ['name' => 'note#getKeywordsDB', 'url' => '/api/keywordsdb', 'verb' => 'GET'],
       ['name' => 'note#mergeKeywordsDB', 'url' => '/api/keywordsdb/merge', 'verb' => 'GET'],
       ['name' => 'note#postKeywordsActions', 'url' => '/api/keywordsdb/action', 'verb' => 'POST'],

       ['name' => 'note#create', 'url' => '/api/notes', 'verb' => 'POST'],
       ['name' => 'note#downloadArchive', 'url' => '/api/notes/export', 'verb' => 'GET'],
       ['name' => 'note#moveNote', 'url' => '/api/notes/move', 'verb' => 'POST'],
       ['name' => 'note#deleteNote', 'url' => '/api/notes', 'verb' => 'DELETE'],
       ['name' => 'note#search', 'url' => '/api/notes/search', 'verb' => 'GET'],
       ['name' => 'note#getSearchCache', 'url' => '/api/notes/getSearchCache', 'verb' => 'GET'],
       ['name' => 'note#getUbuntuFont', 'url' => '/templates/CarnetWebClient/fonts/ubuntu.woff2', 'verb' => 'GET' ],
       ['name' => 'note#getMaterialFont', 'url' => '/templates/CarnetWebClient/fonts/material-icons.woff2', 'verb' => 'GET' ],
       ['name' => 'note#getChangelog', 'url' => '/api/settings/changelog', 'verb' => 'GET' ],
       ['name' => 'note#getLangJson', 'url' => '/api/settings/lang/json', 'verb' => 'GET' ],
       ['name' => 'note#getUISettings', 'url' => '/api/settings/ui', 'verb' => 'GET' ],
       ['name' => 'note#setUISettings', 'url' => '/api/settings/ui', 'verb' => 'POST' ],
       ['name' => 'PublicApi#getOpusEncoder', 'url' => '/recorder/encoderWorker.min.wasm', 'verb' => 'GET' ],
       ['name' => 'PublicApi#getOpusDecoder', 'url' => '/recorder/decoderWorker.min.wasm', 'verb' => 'GET' ],
       ['name' => 'note#getOpusEncoderJavascript', 'url' => '/recorder/encoderWorker.min.js', 'verb' => 'GET' ],
       ['name' => 'note#getOpusDecoderJavascript', 'url' => '/recorder/decoderWorker.min.js', 'verb' => 'GET' ],


       ['name' => 'note#importNote', 'url' => '/api/note/import', 'verb' => 'POST'],
       ['name' => 'note#importArchive', 'url' => '/api/note/import_archive', 'verb' => 'POST'],

       ['name' => 'note#getNote', 'url' => '/api/note/get_note', 'verb' => 'GET'],

       ['name' => 'note#setShouldUseFolderNotes', 'url' => '/api/settings/note_folder', 'verb' => 'POST' ],
       ['name' => 'note#shouldUseFolderNotes', 'url' => '/api/settings/note_folder', 'verb' => 'GET' ],
       ['name' => 'note#useMDEditor', 'url' => '/api/settings/use_md_editor', 'verb' => 'GET' ],
       ['name' => 'note#setUseMDEditor', 'url' => '/api/settings/use_md_editor', 'verb' => 'POST' ],


       ['name' => 'page#sw', 'url' => '/sw.js', 'verb' => 'GET']

    ]
];
var initPath = "recentdb://"
var currentPath;
var currentTask = undefined;
var noteCardViewGrid = undefined;
var notePath = []
var wasNewNote = false
var dontOpen = false;
var currentNotePath = undefined
const {
    ipcRenderer,
    remote,
    app
} = require('electron');
const Store = require('electron-store');
const store = new Store();
var noteCacheStr = String(store.get("note_cache"))
if (noteCacheStr == "undefined")
    noteCacheStr = "{}"
console.log("cache loaded " + noteCacheStr)

var oldNotes = JSON.parse(noteCacheStr);
var main = remote.require("./main.js");
var SettingsHelper = require("./settings/settings_helper").SettingsHelper
var settingsHelper = new SettingsHelper()
var TextGetterTask = function (list) {
    this.list = list;
    this.current = 0;
    this.continue = true;
    this.stopAt = 50;
}

TextGetterTask.prototype.startList = function () {
    this.getNext();
}

TextGetterTask.prototype.getNext = function () {
    if (this.current >= this.stopAt) {
        console.log("save cache ")
        store.set("note_cache", JSON.stringify(oldNotes))
        return;
    }
    if (this.list[this.current] instanceof Note) {
        var opener = new NoteOpener(this.list[this.current])
        var myTask = this;
        var note = this.list[this.current]
        var fast = false;
        //should we go fast or slow refresh ?
        for (var i = this.current; i < this.stopAt && i < this.list.length && i < oldNotes.length; i++) {
            if (oldNotes[this.list[i].path] == undefined) {
                fast = true;
                break;
            }
        }
        setTimeout(function () {
            try {
                opener.getMainTextMetadataAndPreviews(function (txt, metadata, previews) {
                    if (myTask.continue) {
                        if (txt != undefined)
                            note.text = txt.substring(0, 200);
                        if (metadata != undefined)
                            note.metadata = metadata;
                        note.previews = previews;
                        oldNotes[note.path] = note;
                        noteCardViewGrid.updateNote(note)
                        noteCardViewGrid.msnry.layout();

                        myTask.getNext();
                    }
                });
            } catch (error) {
                console.log(error);
            }
            myTask.current++;
        }, !fast ? 1000 : 100)

    } else {
        this.current++;
        this.getNext();
    }

}

var NewNoteCreationTask = function (callback) {
    var path = currentPath;
    if (path == initPath || path.startsWith("keyword://"))
        path = main.getNotePath();
    var fs = require('fs');
    if (!fs.exists(path)) {
        var mkdirp = require('mkdirp');
        mkdirp.sync(path);
    }

    var fb = new FileBrowser(path);
    console.log(path + " fefef")
    var task = this;
    fb.list(function (files) {
        task.files = files;
        var name = "untitled.sqd";
        var sContinue = true;
        var i = 1;
        while (sContinue) {
            sContinue = false
            for (let file of files) {
                if (file.name == name) {
                    sContinue = true;
                    i++;
                    name = "untitled " + i + ".sqd";
                }
            }
        }
        callback(path + "/" + name)

    });
}

String.prototype.replaceAll = function (search, replacement) {
    var target = this;
    return target.replace(new RegExp(search, 'g'), replacement);
};

function openNote(notePath) {
    currentNotePath = notePath
    const electron = require('electron')
    const remote = electron.remote;
    const BrowserWindow = remote.BrowserWindow;
    const path = require('path')
    //var win = new BrowserWindow({ width: 800, height: 600 });
    if (!hasLoadedOnce)
        $(loadingView).fadeIn();
    //$(browserElem).faceOut();
    var rimraf = require('rimraf');
    const tmp = path.join(main.getPath("temp"), "tmpquicknote");
    rimraf(tmp, function () {
        var fs = require('fs');

        fs.mkdir(tmp, function (e) {
            fs.readFile(__dirname + '/reader/reader.html', 'utf8', function (err, data) {
                if (err) {
                    fs.rea
                    console.log("error ")
                    return console.log(err);
                }
                const index = path.join(tmp, 'reader.html');
                fs.writeFileSync(index, data.replace(new RegExp('<!ROOTPATH>', 'g'), __dirname + '/'));
                /* var size = remote.getCurrentWindow().getSize();
                 var pos = remote.getCurrentWindow().getPosition();
                 var win = new BrowserWindow({
                     width: size[0],
                     height: size[1],
                     x: pos[0],
                     y: pos[1],
                     frame: false
                 });
                 console.log("w " + remote.getCurrentWindow().getPosition()[0])
                 const url = require('url')
                 win.loadURL(url.format({
                     pathname: path.join(__dirname, 'tmp/reader.html'),
                     protocol: 'file:',
                     query: {
                         'path': notePath
                     },
                     slashes: true
                 }))*/
                const url = require('url')

                if (!hasLoadedOnce) {
                    webview.setAttribute("src", url.format({
                        pathname: index,
                        protocol: 'file:',
                        query: {
                            'path': notePath,
                            'tmppath': tmp + "/"
                        },
                        slashes: true
                    }));
                } else
                    webview.send('loadnote', notePath);
                webview.style = "position:fixed; top:0px; left:0px; height:100%; width:100%; z-index:100; right:0; bottom:0;"
                //to resize properly
                hasLoadedOnce = true;
            });


        });

    })
}

function onDragEnd(gg) {
    console.log("ondragend")
    dontOpen = true;
}

function refreshKeywords() {
    var KeywordsDBManager = require("./keywords/keywords_db_manager").KeywordsDBManager;
    var keywordsDBManager = new KeywordsDBManager(main.getNotePath() + "/quickdoc/keywords/" + main.getAppUid())
    keywordsDBManager.getFlatenDB(function (error, data) {
        var keywordsContainer = document.getElementById("keywords");
        keywordsContainer.innerHTML = "";
        var dataArray = []
        for (let key in data) {
            if (data[key].length == 0)
                continue;
            dataArray.push(key)
        }
        dataArray.sort(Utils.caseInsensitiveSrt)
        for (let key of dataArray) {

            var keywordElem = document.createElement("a");
            keywordElem.classList.add("mdl-navigation__link")
            keywordElem.innerHTML = key;
            keywordElem.setAttribute("href", "");
            keywordElem.onclick = function () {
                list("keyword://" + key, false);
                return false;
            }
            keywordsContainer.appendChild(keywordElem)
        }
    })
}

function searchInNotes(searching) {
    resetGrid(false)
    var settingsHelper = require("./settings/settings_helper").SettingsHelper
    var SettingsHelper = new settingsHelper();
    var Search = require("./browsers/search").Search
    var browser = this;
    notes = []
    var callback = function () {

    }
    callback.update = function (note) {
        console.log("found " + note.path)
        note = new Note(note.title, note.text.substring(0, 200), note.path, note.metadata)
        browser.noteCardViewGrid.addNote(note)
        notes.push(note)

    }
    callback.onEnd = function (list) {
        console.log("onEnd " + list.length)


    }
    search = new Search(searching, SettingsHelper.getNotePath(), callback)
    search.start()

}

function resetGrid(discret) {
    var grid = document.getElementById("page-content");
    var scroll = 0;
    if (discret)
        scroll = document.getElementById("grid-container").scrollTop;
    grid.innerHTML = "";
    noteCardViewGrid = new NoteCardViewGrid(grid, discret, onDragEnd);
    this.noteCardViewGrid = noteCardViewGrid;
    noteCardViewGrid.onFolderClick(function (folder) {
        list(folder.path)
    })
    noteCardViewGrid.onNoteClick(function (note) {
        if (!dontOpen)
            openNote(note.path)
        dontOpen = false;
        // var reader = new Writer(note,"");
        // reader.extractNote()
    })


    noteCardViewGrid.onMenuClick(function (note) {
        mNoteContextualDialog.show(note)
    })
}

class ContextualDialog {
    constructor() {
        this.showDelete = true;
        this.showArchive = true;
        this.showPin = true;
        this.dialog = document.querySelector('#contextual-dialog');
        this.nameInput = this.dialog.querySelector('#name-input');
        this.deleteButton = this.dialog.querySelector('.delete-button');
        this.archiveButton = this.dialog.querySelector('#archive-button');
        this.pinButton = this.dialog.querySelector('#pin-button');
        this.cancel = this.dialog.querySelector('.cancel');
        this.ok = this.dialog.querySelector('.ok');
        var context = this;
        this.cancel.onclick = function () {
            context.dialog.close();
        }
    }

    show() {
        this.showDelete ? $(this.deleteButton).show() : $(this.deleteButton).hide();
        this.showArchive ? $(this.archiveButton).show() : $(this.archiveButton).hide();
        this.showPin ? $(this.pinButton).show() : $(this.pinButton).hide();
        this.dialog.showModal();
        this.nameInput.focus()
    }
}

class NewFolderDialog extends ContextualDialog {
    constructor() {
        super();
        this.showDelete = false;
        this.showArchive = false;
        this.showPin = false;
    }

    show() {
        var context = this;

        this.ok.onclick = function () {
            var fb = new FileBrowser(currentPath);
            fb.createFolder(context.nameInput.value, function () {
                list(currentPath, true)
                context.dialog.close();
            })
        }
        super.show()

    }
}

class NoteContextualDialog extends ContextualDialog {
    constructor() {
        super();
    }

    show(note) {
        var context = this;
        this.nameInput.value = note.title;
        this.deleteButton.onclick = function () {
            NoteUtils.deleteNote(note.path, function () {
                context.dialog.close();
                list(currentPath, true)
            })
        }
        this.archiveButton.onclick = function () {
            var db = new RecentDBManager(main.getNotePath() + "/quickdoc/recentdb/" + main.getAppUid())
            db.removeFromDB(NoteUtils.getNoteRelativePath(main.getNotePath(), note.path), function () {
                context.dialog.close();
                list(currentPath, true)
            });

        }
        if (note.isPinned == true) {
            this.pinButton.innerHTML = "Unpin"
        } else this.pinButton.innerHTML = "Pin"

        this.pinButton.onclick = function () {
            var db = new RecentDBManager(main.getNotePath() + "/quickdoc/recentdb/" + main.getAppUid())
            if (note.isPinned == true)
                db.unpin(NoteUtils.getNoteRelativePath(main.getNotePath(), note.path), function () {
                    context.dialog.close();
                    list(currentPath, true)
                });

            else
                db.pin(NoteUtils.getNoteRelativePath(main.getNotePath(), note.path), function () {
                    context.dialog.close();
                    list(currentPath, true)
                });
        }
        this.ok.onclick = function () {
            NoteUtils.renameNote(note.path, context.nameInput.value + ".sqd", function () {
                list(currentPath, true)
            })

            context.dialog.close();
        }
        super.show()

    }
}

var mNoteContextualDialog = new NoteContextualDialog()
var mNewFolderDialog = new NewFolderDialog()


function list(pathToList, discret) {
    if (pathToList == undefined)
        pathToList = currentPath;
    console.log("listing path " + pathToList);
    currentPath = pathToList;
    if (pathToList == settingsHelper.getNotePath() || pathToList == initPath || pathToList.startsWith("keyword://")) {
        if (pathToList != settingsHelper.getNotePath()) {
            $("#add-directory-button").hide()
        } else
            $("#add-directory-button").show()

        $("#back_arrow").hide()
    } else {
        $("#back_arrow").show()
        $("#add-directory-button").show()
    }

    resetGrid(discret);
    var noteCardViewGrid = this.noteCardViewGrid
    var notes = [];
    notePath = [];

    var fb = new FileBrowser(pathToList);
    fb.list(function (files) {
        if (currentTask != undefined)
            currentTask.continue = false
        if (files.length > 0) {
            $("#emty-view").fadeOut("fast");
        } else
            $("#emty-view").fadeIn("fast");
        for (let file of files) {
            var filename = getFilenameFromPath(file.path);
            if (file.isFile && filename.endsWith(".sqd")) {
                var oldNote = oldNotes[file.path];

                var noteTestTxt = new Note(stripExtensionFromName(filename), oldNote != undefined ? oldNote.text : "", file.path, oldNote != undefined ? oldNote.metadata : undefined, oldNote != undefined ? oldNote.previews : undefined);
                noteTestTxt.isPinned = file.isPinned
                notes.push(noteTestTxt)
            } else if (!file.isFile) {

                notes.push(file)
            }
            notePath.push(file.path)
        }
        noteCardViewGrid.setNotesAndFolders(notes)
        if (discret) {
            document.getElementById("grid-container").scrollTop = scroll;
            console.log("scroll : " + scroll)

        }
        currentTask = new TextGetterTask(notes);
        console.log("stopping and starting task")
        currentTask.startList();

    });

}
list(initPath)
refreshKeywords();

function minimize() {
    remote.BrowserWindow.getFocusedWindow().minimize();
}

function maximize() {
    if (remote.BrowserWindow.getFocusedWindow().isMaximized())
        remote.BrowserWindow.getFocusedWindow().unmaximize();
    else
        remote.BrowserWindow.getFocusedWindow().maximize();
}

function closeW() {
    remote.app.exit(0);
    console.log("cloose")
}

function toggleSearch() {
    $("#search-container").slideToggle();
}


main.setMergeListener(function () {
    list(initPath, true)
})

document.getElementById("add-note-button").onclick = function () {
    new NewNoteCreationTask(function (path) {
        console.log("found " + path)
        wasNewNote = true;
        var db = new RecentDBManager(main.getNotePath() + "/quickdoc/recentdb/" + main.getAppUid())
        db.addToDB(NoteUtils.getNoteRelativePath(main.getNotePath(), path));
        openNote(path)
    })
}

document.getElementById("add-directory-button").onclick = function () {
    mNewFolderDialog.show();
}


document.getElementById("search-input").onkeydown = function (event) {
    if (event.key === 'Enter') {
        searchInNotes(this.value)
    }
}

document.getElementById("back_arrow").addEventListener("click", function () {
    list(getParentFolderFromPath(currentPath))
});

function getNotePath() {

    return main.getNotePath()
}

function loadNextNotes() {
    browser.noteCardViewGrid.addNext(15);
    currentTask.stopAt += 15;
    currentTask.getNext()
}

var browser = this
document.getElementById("grid-container").onscroll = function () {
    if (this.offsetHeight + this.scrollTop >= this.scrollHeight - 80) {
        loadNextNotes();

    }
}
var webview = document.getElementById("writer-webview")

webview.addEventListener('ipc-message', event => {
    if (event.channel == "exit") {
        webview.style = "position:fixed; top:0px; left:0px; height:0px; width:0px; z-index:100; right:0; bottom:0;"
        //$(browserElem).faceIn();
        $("#no-drag-bar").hide()
        if (wasNewNote)
            list(currentPath, true)
        else if (currentTask != undefined) {
            var noteIndex
            if ((noteIndex = notePath.indexOf(currentNotePath)) == -1) {
                noteIndex = 0;
            }
            currentTask.current = noteIndex;
            currentTask.getNext()
        }
        wasNewNote = false;
        refreshKeywords()

    } else if (event.channel == "loaded") {
        $(loadingView).fadeOut();
        $("#no-drag-bar").show()

    }
});
var hasLoadedOnce = false
webview.addEventListener('dom-ready', () => {
    webview.openDevTools()
})
var loadingView = document.getElementById("loading-view")
//var browserElem = document.getElementById("browser")
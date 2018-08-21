<?php

namespace PhpZip;

use PhpZip\Model\ZipEntry;
use PhpZip\Model\ZipInfo;
use PhpZip\Util\CryptoUtil;
use PhpZip\Util\FilesUtil;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

/**
 * ZipFile test
 */
class ZipFileTest extends ZipTestCase
{

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage can't exists
     */
    public function testOpenFileCantExists()
    {
        $zipFile = new ZipFile();
        $zipFile->openFile(uniqid());
    }

    /**
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage can't open
     */
    public function testOpenFileCantOpen()
    {
        if (0 === posix_getuid()) {
            $this->markTestSkipped('Skip the test for a user with root privileges');
        }

        self::assertNotFalse(file_put_contents($this->outputFilename, 'content'));
        self::assertTrue(chmod($this->outputFilename, 0222));

        $zipFile = new ZipFile();
        $zipFile->openFile($this->outputFilename);
    }

    /**
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage Invalid zip file
     */
    public function testOpenFileEmptyFile()
    {
        self::assertNotFalse(touch($this->outputFilename));
        $zipFile = new ZipFile();
        $zipFile->openFile($this->outputFilename);
    }

    /**
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage Expected Local File Header or (ZIP64) End Of Central Directory Record
     */
    public function testOpenFileInvalidZip()
    {
        self::assertNotFalse(file_put_contents($this->outputFilename, CryptoUtil::randomBytes(255)));
        $zipFile = new ZipFile();
        $zipFile->openFile($this->outputFilename);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Data not available
     */
    public function testOpenFromStringNullString()
    {
        $zipFile = new ZipFile();
        $zipFile->openFromString(null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Data not available
     */
    public function testOpenFromStringEmptyString()
    {
        $zipFile = new ZipFile();
        $zipFile->openFromString("");
    }

    /**
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage Expected Local File Header or (ZIP64) End Of Central Directory Record
     */
    public function testOpenFromStringInvalidZip()
    {
        $zipFile = new ZipFile();
        $zipFile->openFromString(CryptoUtil::randomBytes(255));
    }

    public function testOpenFromString()
    {
        $zipFile = new ZipFile();
        $zipFile->addFromString('file', 'content');
        $zipFile['file2'] = 'content 2';
        $zipContents = $zipFile->outputAsString();
        $zipFile->close();

        $zipFile->openFromString($zipContents);
        self::assertEquals($zipFile->count(), 2);
        self::assertTrue(isset($zipFile['file']));
        self::assertTrue(isset($zipFile['file2']));
        self::assertEquals($zipFile['file'], 'content');
        self::assertEquals($zipFile['file2'], 'content 2');
        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid stream resource
     */
    public function testOpenFromStreamNullStream()
    {
        $zipFile = new ZipFile();
        $zipFile->openFromStream(null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid stream resource
     */
    public function testOpenFromStreamInvalidResourceType()
    {
        $zipFile = new ZipFile();
        /** @noinspection PhpParamsInspection */
        $zipFile->openFromStream("stream resource");
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid resource type - gd.
     */
    public function testOpenFromStreamInvalidResourceType2()
    {
        $zipFile = new ZipFile();
        if (!extension_loaded("gd")) {
            $this->markTestSkipped('not extension gd');
        }
        $zipFile->openFromStream(imagecreate(1, 1));
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid stream type - dir.
     */
    public function testOpenFromStreamInvalidResourceType3()
    {
        $zipFile = new ZipFile();
        $zipFile->openFromStream(opendir(__DIR__));
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Resource cannot seekable stream.
     */
    public function testOpenFromStreamNoSeekable()
    {
        if (!$fp = @fopen("http://localhost", 'r')) {
            if (!$fp = @fopen("http://example.org", 'r')) {
                $this->markTestSkipped('not connected to localhost or remote host');
                return;
            }
        }

        $zipFile = new ZipFile();
        $zipFile->openFromStream($fp);
    }

    /**
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage Invalid zip file
     */
    public function testOpenFromStreamEmptyContents()
    {
        $fp = fopen($this->outputFilename, 'w+b');
        $zipFile = new ZipFile();
        $zipFile->openFromStream($fp);
    }

    /**
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage Expected Local File Header or (ZIP64) End Of Central Directory Record
     */
    public function testOpenFromStreamInvalidZip()
    {
        $fp = fopen($this->outputFilename, 'w+b');
        fwrite($fp, CryptoUtil::randomBytes(255));
        $zipFile = new ZipFile();
        $zipFile->openFromStream($fp);
    }

    public function testOpenFromStream()
    {
        $zipFile = new ZipFile();
        $zipFile
            ->addFromString('file', 'content')
            ->saveAsFile($this->outputFilename)
            ->close();

        $handle = fopen($this->outputFilename, 'rb');
        $zipFile->openFromStream($handle);
        self::assertEquals($zipFile->count(), 1);
        self::assertTrue(isset($zipFile['file']));
        self::assertEquals($zipFile['file'], 'content');
        $zipFile->close();
    }

    /**
     * Test create, open and extract empty archive.
     */
    public function testEmptyArchive()
    {
        $zipFile = new ZipFile();
        $zipFile
            ->saveAsFile($this->outputFilename)
            ->close();

        self::assertCorrectEmptyZip($this->outputFilename);
        self::assertTrue(mkdir($this->outputDirname, 0755, true));

        $zipFile->openFile($this->outputFilename);
        self::assertEquals($zipFile->count(), 0);
        $zipFile
            ->extractTo($this->outputDirname)
            ->close();

        self::assertTrue(FilesUtil::isEmptyDir($this->outputDirname));
    }

    /**
     * No modified archive
     *
     * @see ZipOutputFile::create()
     */
    public function testNoModifiedArchive()
    {
        self::assertTrue(mkdir($this->outputDirname, 0755, true));

        $fileActual = $this->outputDirname . DIRECTORY_SEPARATOR . 'file_actual.zip';
        $fileExpected = $this->outputDirname . DIRECTORY_SEPARATOR . 'file_expected.zip';

        $zipFile = new ZipFile();
        $zipFile->addDirRecursive(__DIR__.'/../../src');
        $sourceCount = $zipFile->count();
        self::assertTrue($sourceCount > 0);
        $zipFile
            ->saveAsFile($fileActual)
            ->close();
        self::assertCorrectZipArchive($fileActual);

        $zipFile
            ->openFile($fileActual)
            ->saveAsFile($fileExpected);
        self::assertCorrectZipArchive($fileExpected);

        $zipFileExpected = new ZipFile();
        $zipFileExpected->openFile($fileExpected);

        self::assertEquals($zipFile->count(), $sourceCount);
        self::assertEquals($zipFileExpected->count(), $zipFile->count());
        self::assertEquals($zipFileExpected->getListFiles(), $zipFile->getListFiles());

        foreach ($zipFile as $entryName => $content) {
            self::assertEquals($zipFileExpected[$entryName], $content);
        }

        $zipFileExpected->close();
        $zipFile->close();
    }

    /**
     * Create archive and add files.
     *
     * @see ZipOutputFile::addFromString()
     * @see ZipOutputFile::addFromFile()
     * @see ZipOutputFile::addFromStream()
     * @see ZipFile::getEntryContents()
     */
    public function testCreateArchiveAndAddFiles()
    {
        $outputFromString = file_get_contents(__FILE__);
        $outputFromString2 = file_get_contents(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'README.md');
        $outputFromFile = file_get_contents(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'phpunit.xml');
        $outputFromStream = file_get_contents(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'composer.json');

        $filenameFromString = basename(__FILE__);
        $filenameFromString2 = 'test_file.txt';
        $filenameFromFile = 'data/test file.txt';
        $filenameFromStream = 'data/ডিরেক্টরি/αρχείο.json';
        $emptyDirName = 'empty dir/пустой каталог/空目錄/ไดเรกทอรีที่ว่างเปล่า/';
        $emptyDirName2 = 'empty dir/пустой каталог/';
        $emptyDirName3 = 'empty dir/пустой каталог/ещё один пустой каталог/';

        $tempFile = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($tempFile, $outputFromFile);

        $tempStream = tmpfile();
        fwrite($tempStream, $outputFromStream);

        $zipFile = new ZipFile;
        $zipFile
            ->addFromString($filenameFromString, $outputFromString)
            ->addFile($tempFile, $filenameFromFile)
            ->addFromStream($tempStream, $filenameFromStream)
            ->addEmptyDir($emptyDirName);
        $zipFile[$filenameFromString2] = $outputFromString2;
        $zipFile[$emptyDirName2] = null;
        $zipFile[$emptyDirName3] = 'this content ignoring';
        self::assertEquals(count($zipFile), 7);
        $zipFile
            ->saveAsFile($this->outputFilename)
            ->close();
        unlink($tempFile);

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertEquals(count($zipFile), 7);
        self::assertEquals($zipFile[$filenameFromString], $outputFromString);
        self::assertEquals($zipFile[$filenameFromFile], $outputFromFile);
        self::assertEquals($zipFile[$filenameFromStream], $outputFromStream);
        self::assertEquals($zipFile[$filenameFromString2], $outputFromString2);
        self::assertTrue(isset($zipFile[$emptyDirName]));
        self::assertTrue(isset($zipFile[$emptyDirName2]));
        self::assertTrue(isset($zipFile[$emptyDirName3]));
        self::assertTrue($zipFile->isDirectory($emptyDirName));
        self::assertTrue($zipFile->isDirectory($emptyDirName2));
        self::assertTrue($zipFile->isDirectory($emptyDirName3));

        $listFiles = $zipFile->getListFiles();
        self::assertEquals($listFiles[0], $filenameFromString);
        self::assertEquals($listFiles[1], $filenameFromFile);
        self::assertEquals($listFiles[2], $filenameFromStream);
        self::assertEquals($listFiles[3], $emptyDirName);
        self::assertEquals($listFiles[4], $filenameFromString2);
        self::assertEquals($listFiles[5], $emptyDirName2);
        self::assertEquals($listFiles[6], $emptyDirName3);

        $zipFile->close();
    }

    public function testEmptyContent()
    {
        $zipFile = new ZipFile();
        $zipFile['file'] = '';
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        $zipFile->openFile($this->outputFilename);
        self::assertEquals($zipFile['file'], '');
        $zipFile->close();
    }

    /**
     * Test compression method from image file.
     */
    public function testCompressionMethodFromImageMimeType()
    {
        if (!function_exists('mime_content_type')) {
            $this->markTestSkipped('Function mime_content_type not exists');
        }
        $outputFilename = $this->outputFilename;
        $this->outputFilename .= '.gif';
        self::assertNotFalse(
            file_put_contents(
                $this->outputFilename,
                base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==')
            )
        );
        $basename = basename($this->outputFilename);

        $zipFile = new ZipFile();
        $zipFile->addFile($this->outputFilename, $basename);
        $zipFile->saveAsFile($outputFilename);
        unlink($this->outputFilename);
        $this->outputFilename = $outputFilename;

        $zipFile->openFile($this->outputFilename);
        $info = $zipFile->getEntryInfo($basename);
        self::assertEquals($info->getMethodName(), 'No compression');
        $zipFile->close();
    }

    /**
     * Rename zip entry name.
     */
    public function testRename()
    {
        $oldName = basename(__FILE__);
        $newName = 'tests/' . $oldName;

        $zipFile = new ZipFile();
        $zipFile->addDir(__DIR__);
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        $zipFile->rename($oldName, $newName);
        $zipFile->addFromString('file1.txt', 'content');
        $zipFile->addFromString('file2.txt', 'content');
        $zipFile->addFromString('file3.txt', 'content');
        $zipFile->rename('file1.txt', 'file_long_name.txt');
        $zipFile->rename('file2.txt', 'file4.txt');
        $zipFile->rename('file3.txt', 'fi.txt');
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertFalse(isset($zipFile[$oldName]));
        self::assertTrue(isset($zipFile[$newName]));
        self::assertFalse(isset($zipFile['file1.txt']));
        self::assertFalse(isset($zipFile['file2.txt']));
        self::assertFalse(isset($zipFile['file3.txt']));
        self::assertTrue(isset($zipFile['file_long_name.txt']));
        self::assertTrue(isset($zipFile['file4.txt']));
        self::assertTrue(isset($zipFile['fi.txt']));
        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage name is null
     */
    public function testRenameEntryNull()
    {
        $zipFile = new ZipFile();
        $zipFile->rename(null, 'new-file');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage name is null
     */
    public function testRenameEntryNull2()
    {
        $zipFile = new ZipFile();
        $zipFile->rename('old-file', null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage is exists
     */
    public function testRenameEntryNewEntyExists()
    {
        $zipFile = new ZipFile();
        $zipFile['file'] = 'content';
        $zipFile['file2'] = 'content 2';
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        $zipFile = new ZipFile();
        $zipFile->openFile($this->outputFilename);
        $zipFile->rename('file2', 'file');
    }

    /**
     * @expectedException \PhpZip\Exception\ZipNotFoundEntry
     */
    public function testRenameEntryNotFound()
    {
        $zipFile = new ZipFile();
        $zipFile['file'] = 'content';
        $zipFile['file2'] = 'content 2';
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        $zipFile = new ZipFile();
        $zipFile->openFile($this->outputFilename);
        $zipFile->rename('file2.bak', 'file3');
    }

    /**
     * Delete entry from name.
     */
    public function testDeleteFromName()
    {
        $inputDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
        $deleteEntryName = 'composer.json';

        $zipFile = new ZipFile();
        $zipFile->addDir($inputDir);
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        $zipFile->deleteFromName($deleteEntryName);
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertFalse(isset($zipFile[$deleteEntryName]));
        $zipFile->close();
    }

    public function testDeleteNewEntry()
    {
        $zipFile = new ZipFile();
        $zipFile['entry1'] = '';
        $zipFile['entry2'] = '';
        $zipFile->deleteFromName('entry2');
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        $zipFile->openFile($this->outputFilename);
        self::assertEquals(sizeof($zipFile), 1);
        self::assertTrue(isset($zipFile['entry1']));
        self::assertFalse(isset($zipFile['entry2']));
        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\ZipNotFoundEntry
     */
    public function testDeleteFromNameNotFoundEntry()
    {
        $zipFile = new ZipFile();
        $zipFile->deleteFromName('entry');
    }

    /**
     * Delete zip entries from glob pattern
     */
    public function testDeleteFromGlob()
    {
        $inputDir = dirname(dirname(__DIR__));

        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlobRecursive($inputDir, '**.{xml,json,md}', '/');
        self::assertTrue(isset($zipFile['composer.json']));
        self::assertTrue(isset($zipFile['phpunit.xml']));
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertTrue(isset($zipFile['composer.json']));
        self::assertTrue(isset($zipFile['phpunit.xml']));
        $zipFile->deleteFromGlob('**.{xml,json}');
        self::assertFalse(isset($zipFile['composer.json']));
        self::assertFalse(isset($zipFile['phpunit.xml']));
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertTrue($zipFile->count() > 0);

        foreach ($zipFile->getListFiles() as $name) {
            self::assertStringEndsWith('.md', $name);
        }

        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Glob pattern is empty
     */
    public function testDeleteFromGlobFailNull()
    {
        $zipFile = new ZipFile();
        $zipFile->deleteFromGlob(null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Glob pattern is empty
     */
    public function testDeleteFromGlobFailEmpty()
    {
        $zipFile = new ZipFile();
        $zipFile->deleteFromGlob('');
    }

    /**
     * Delete entries from regex pattern
     */
    public function testDeleteFromRegex()
    {
        $inputDir = dirname(dirname(__DIR__));

        $zipFile = new ZipFile();
        $zipFile->addFilesFromRegexRecursive($inputDir, '~\.(xml|json)$~i', 'Path');
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        $zipFile->deleteFromRegex('~\.(json)$~i');
        $zipFile->addFromString('test.txt', 'content');
        $zipFile->deleteFromRegex('~\.txt$~');
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertFalse(isset($zipFile['Path/composer.json']));
        self::assertFalse(isset($zipFile['Path/test.txt']));
        self::assertTrue(isset($zipFile['Path/phpunit.xml']));
        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Regex pattern is empty.
     */
    public function testDeleteFromRegexFailNull()
    {
        $zipFile = new ZipFile();
        $zipFile->deleteFromRegex(null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Regex pattern is empty.
     */
    public function testDeleteFromRegexFailEmpty()
    {
        $zipFile = new ZipFile();
        $zipFile->deleteFromRegex('');
    }

    /**
     * Delete all entries
     */
    public function testDeleteAll()
    {
        $zipFile = new ZipFile();
        $zipFile->addDirRecursive(dirname(dirname(__DIR__)) .DIRECTORY_SEPARATOR. 'src');
        self::assertTrue($zipFile->count() > 0);
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertTrue($zipFile->count() > 0);
        $zipFile->deleteAll();
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectEmptyZip($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertEquals($zipFile->count(), 0);
        $zipFile->close();
    }

    /**
     * Test zip archive comment.
     */
    public function testArchiveComment()
    {
        $comment = "This zip file comment" . PHP_EOL
            . "Αυτό το σχόλιο αρχείο zip" . PHP_EOL
            . "Это комментарий zip архива" . PHP_EOL
            . "這個ZIP文件註釋" . PHP_EOL
            . "ეს zip ფაილის კომენტარი" . PHP_EOL
            . "このzipファイルにコメント" . PHP_EOL
            . "ความคิดเห็นนี้ไฟล์ซิป";

        $zipFile = new ZipFile();
        $zipFile->setArchiveComment($comment);
        $zipFile->addFile(__FILE__);
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertEquals($zipFile->getArchiveComment(), $comment);
        $zipFile->setArchiveComment(null); // remove archive comment
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        // check empty comment
        $zipFile->openFile($this->outputFilename);
        self::assertEquals($zipFile->getArchiveComment(), "");
        $zipFile->close();
    }

    /**
     * Test very long archive comment.
     *
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     */
    public function testVeryLongArchiveComment()
    {
        $comment = "Very long comment" . PHP_EOL .
            "Очень длинный комментарий" . PHP_EOL;
        $comment = str_repeat($comment, ceil(0xffff / strlen($comment)) + strlen($comment) + 1);

        $zipFile = new ZipFile();
        $zipFile->setArchiveComment($comment);
    }

    /**
     * Test zip entry comment.
     */
    public function testEntryComment()
    {
        $entries = [
            '文件1.txt' => [
                'data' => CryptoUtil::randomBytes(255),
                'comment' => "這是註釋的條目。",
            ],
            'file2.txt' => [
                'data' => CryptoUtil::randomBytes(255),
                'comment' => null
            ],
            'file3.txt' => [
                'data' => CryptoUtil::randomBytes(255),
                'comment' => CryptoUtil::randomBytes(255),
            ],
            'file4.txt' => [
                'data' => CryptoUtil::randomBytes(255),
                'comment' => "Комментарий файла"
            ],
            'file5.txt' => [
                'data' => CryptoUtil::randomBytes(255),
                'comment' => "ไฟล์แสดงความคิดเห็น"
            ],
            'file6 emoji 🙍🏼.txt' => [
                'data' => CryptoUtil::randomBytes(255),
                'comment' => "Emoji comment file - 😀 ⛈ ❤️ 🤴🏽"
            ],
        ];

        // create archive with entry comments
        $zipFile = new ZipFile();
        foreach ($entries as $entryName => $item) {
            $zipFile->addFromString($entryName, $item['data']);
            $zipFile->setEntryComment($entryName, $item['comment']);
        }
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        // check and modify comments
        $zipFile->openFile($this->outputFilename);
        foreach ($zipFile->getListFiles() as $entryName) {
            $entriesItem = $entries[$entryName];
            self::assertNotEmpty($entriesItem);
            self::assertEquals($zipFile[$entryName], $entriesItem['data']);
            self::assertEquals($zipFile->getEntryComment($entryName), (string)$entriesItem['comment']);
        }
        // modify comment
        $entries['file5.txt']['comment'] = mt_rand(1, 100000000);
        $zipFile->setEntryComment('file5.txt', $entries['file5.txt']['comment']);
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        // check modify comments
        $zipFile->openFile($this->outputFilename);
        foreach ($entries as $entryName => $entriesItem) {
            self::assertTrue(isset($zipFile[$entryName]));
            self::assertEquals($zipFile->getEntryComment($entryName), (string)$entriesItem['comment']);
            self::assertEquals($zipFile[$entryName], $entriesItem['data']);
        }
        $zipFile->close();
    }

    /**
     * Test zip entry very long comment.
     *
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage Comment too long
     */
    public function testVeryLongEntryComment()
    {
        $comment = "Very long comment" . PHP_EOL .
            "Очень длинный комментарий" . PHP_EOL;
        $comment = str_repeat($comment, ceil(0xffff / strlen($comment)) + strlen($comment) + 1);

        $zipFile = new ZipFile();
        $zipFile->addFile(__FILE__, 'test');
        $zipFile->setEntryComment('test', $comment);
    }

    /**
     * @expectedException \PhpZip\Exception\ZipNotFoundEntry
     */
    public function testSetEntryCommentNotFoundEntry()
    {
        $zipFile = new ZipFile();
        $zipFile->setEntryComment('test', 'comment');
    }

    /**
     * Test all available support compression methods.
     */
    public function testCompressionMethod()
    {
        $entries = [
            '1' => [
                'data' => CryptoUtil::randomBytes(255),
                'method' => ZipFileInterface::METHOD_STORED,
                'expected' => 'No compression',
            ],
            '2' => [
                'data' => CryptoUtil::randomBytes(255),
                'method' => ZipFileInterface::METHOD_DEFLATED,
                'expected' => 'Deflate',
            ],
        ];
        if (extension_loaded("bz2")) {
            $entries['3'] = [
                'data' => CryptoUtil::randomBytes(255),
                'method' => ZipFileInterface::METHOD_BZIP2,
                'expected' => 'Bzip2',
            ];
        }

        $zipFile = new ZipFile();
        foreach ($entries as $entryName => $item) {
            $zipFile->addFromString($entryName, $item['data'], $item['method']);
        }
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        $zipFile->setCompressionLevel(ZipFileInterface::LEVEL_BEST_COMPRESSION);
        $zipAllInfo = $zipFile->getAllInfo();

        foreach ($zipAllInfo as $entryName => $info) {
            self::assertEquals($zipFile[$entryName], $entries[$entryName]['data']);
            self::assertEquals($info->getMethodName(), $entries[$entryName]['expected']);
            $entryInfo = $zipFile->getEntryInfo($entryName);
            self::assertEquals($entryInfo, $info);
        }
        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid compression level. Minimum level -1. Maximum level 9
     */
    public function testSetInvalidCompressionLevel()
    {
        $zipFile = new ZipFile();
        $zipFile->setCompressionLevel(-2);
    }

    /**
     * /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid compression level. Minimum level -1. Maximum level 9
     */
    public function testSetInvalidCompressionLevel2()
    {
        $zipFile = new ZipFile();
        $zipFile->setCompressionLevel(10);
    }

    /**
     * Test extract all files.
     */
    public function testExtract()
    {
        $entries = [
            'test1.txt' => CryptoUtil::randomBytes(255),
            'test2.txt' => CryptoUtil::randomBytes(255),
            'test/test 2/test3.txt' => CryptoUtil::randomBytes(255),
            'test empty/dir' => null,
        ];

        $zipFile = new ZipFile();
        foreach ($entries as $entryName => $value) {
            if ($value === null) {
                $zipFile->addEmptyDir($entryName);
            } else {
                $zipFile->addFromString($entryName, $value);
            }
        }
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertTrue(mkdir($this->outputDirname, 0755, true));

        $zipFile->openFile($this->outputFilename);
        $zipFile->extractTo($this->outputDirname);
        foreach ($entries as $entryName => $value) {
            $fullExtractedFilename = $this->outputDirname . DIRECTORY_SEPARATOR . $entryName;
            if ($value === null) {
                self::assertTrue(is_dir($fullExtractedFilename));
                self::assertTrue(FilesUtil::isEmptyDir($fullExtractedFilename));
            } else {
                self::assertTrue(is_file($fullExtractedFilename));
                $contents = file_get_contents($fullExtractedFilename);
                self::assertEquals($contents, $value);
            }
        }
        $zipFile->close();
    }

    /**
     * Test extract some files
     */
    public function testExtractSomeFiles()
    {
        $entries = [
            'test1.txt' => CryptoUtil::randomBytes(255),
            'test2.txt' => CryptoUtil::randomBytes(255),
            'test3.txt' => CryptoUtil::randomBytes(255),
            'test4.txt' => CryptoUtil::randomBytes(255),
            'test5.txt' => CryptoUtil::randomBytes(255),
            'test/test/test.txt' => CryptoUtil::randomBytes(255),
            'test/test/test 2.txt' => CryptoUtil::randomBytes(255),
            'test empty/dir/' => null,
            'test empty/dir2/' => null,
        ];

        $extractEntries = [
            'test1.txt',
            'test3.txt',
            'test5.txt',
            'test/test/test 2.txt',
            'test empty/dir2/'
        ];

        self::assertTrue(mkdir($this->outputDirname, 0755, true));

        $zipFile = new ZipFile();
        $zipFile->addAll($entries);
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        $zipFile->openFile($this->outputFilename);
        $zipFile->extractTo($this->outputDirname, $extractEntries);

        foreach ($entries as $entryName => $value) {
            $fullExtractFilename = $this->outputDirname . DIRECTORY_SEPARATOR . $entryName;
            if (in_array($entryName, $extractEntries)) {
                if ($value === null) {
                    self::assertTrue(is_dir($fullExtractFilename));
                    self::assertTrue(FilesUtil::isEmptyDir($fullExtractFilename));
                } else {
                    self::assertTrue(is_file($fullExtractFilename));
                    $contents = file_get_contents($fullExtractFilename);
                    self::assertEquals($contents, $value);
                }
            } else {
                if ($value === null) {
                    self::assertFalse(is_dir($fullExtractFilename));
                } else {
                    self::assertFalse(is_file($fullExtractFilename));
                }
            }
        }
        self::assertFalse(is_file($this->outputDirname . DIRECTORY_SEPARATOR . 'test/test/test.txt'));
        $zipFile->extractTo($this->outputDirname, 'test/test/test.txt');
        self::assertTrue(is_file($this->outputDirname . DIRECTORY_SEPARATOR . 'test/test/test.txt'));

        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage not found
     */
    public function testExtractFail()
    {
        $zipFile = new ZipFile();
        $zipFile['file'] = 'content';
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        $zipFile->openFile($this->outputFilename);
        $zipFile->extractTo('path/to/path');
    }

    /**
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage Destination is not directory
     */
    public function testExtractFail2()
    {
        $zipFile = new ZipFile();
        $zipFile['file'] = 'content';
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        $zipFile->openFile($this->outputFilename);
        $zipFile->extractTo($this->outputFilename);
    }

    /**
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage Destination is not writable directory
     */
    public function testExtractFail3()
    {
        if (0 === posix_getuid()) {
            $this->markTestSkipped('Skip the test for a user with root privileges');
        }

        $zipFile = new ZipFile();
        $zipFile['file'] = 'content';
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertTrue(mkdir($this->outputDirname, 0444, true));
        self::assertTrue(chmod($this->outputDirname, 0444));

        $zipFile->openFile($this->outputFilename);
        $zipFile->extractTo($this->outputDirname);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage entryName is null
     */
    public function testAddFromArrayAccessNullName()
    {
        $zipFile = new ZipFile();
        $zipFile[null] = 'content';
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage entryName is empty
     */
    public function testAddFromArrayAccessEmptyName()
    {
        $zipFile = new ZipFile();
        $zipFile[''] = 'content';
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Contents is null
     */
    public function testAddFromStringNullContents()
    {
        $zipFile = new ZipFile();
        $zipFile->addFromString('file', null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Incorrect entry name
     */
    public function testAddFromStringNullEntryName()
    {
        $zipFile = new ZipFile();
        $zipFile->addFromString(null, 'contents');
    }

    /**
     * @expectedException \PhpZip\Exception\ZipUnsupportMethod
     * @expectedExceptionMessage Unsupported compression method
     */
    public function testAddFromStringUnsupportedMethod()
    {
        $zipFile = new ZipFile();
        $zipFile->addFromString('file', 'contents', ZipEntry::METHOD_WINZIP_AES);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Incorrect entry name
     */
    public function testAddFromStringEmptyEntryName()
    {
        $zipFile = new ZipFile();
        $zipFile->addFromString('', 'contents');
    }

    /**
     * Test compression method from add string.
     */
    public function testAddFromStringCompressionMethod()
    {
        $fileStored = sys_get_temp_dir() . '/zip-stored.txt';
        $fileDeflated = sys_get_temp_dir() . '/zip-deflated.txt';

        self::assertNotFalse(file_put_contents($fileStored, 'content'));
        self::assertNotFalse(file_put_contents($fileDeflated, str_repeat('content', 200)));

        $zipFile = new ZipFile();
        $zipFile->addFromString(basename($fileStored), file_get_contents($fileStored));
        $zipFile->addFromString(basename($fileDeflated), file_get_contents($fileDeflated));
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        unlink($fileStored);
        unlink($fileDeflated);

        $zipFile->openFile($this->outputFilename);
        $infoStored = $zipFile->getEntryInfo(basename($fileStored));
        $infoDeflated = $zipFile->getEntryInfo(basename($fileDeflated));
        self::assertEquals($infoStored->getMethodName(), 'No compression');
        self::assertEquals($infoDeflated->getMethodName(), 'Deflate');
        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage stream is not resource
     */
    public function testAddFromStreamInvalidResource()
    {
        $zipFile = new ZipFile();
        /** @noinspection PhpParamsInspection */
        $zipFile->addFromStream("invalid resource", "name");
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Incorrect entry name
     */
    public function testAddFromStreamEmptyEntryName()
    {
        $handle = fopen(__FILE__, 'rb');

        $zipFile = new ZipFile();
        $zipFile->addFromStream($handle, "");
    }

    /**
     * @expectedException \PhpZip\Exception\ZipUnsupportMethod
     * @expectedExceptionMessage Unsupported method
     */
    public function testAddFromStreamUnsupportedMethod()
    {
        $handle = fopen(__FILE__, 'rb');

        $zipFile = new ZipFile();
        $zipFile->addFromStream($handle, basename(__FILE__), ZipEntry::METHOD_WINZIP_AES);
    }

    /**
     * Test compression method from add stream.
     */
    public function testAddFromStreamCompressionMethod()
    {
        $fileStored = sys_get_temp_dir() . '/zip-stored.txt';
        $fileDeflated = sys_get_temp_dir() . '/zip-deflated.txt';

        self::assertNotFalse(file_put_contents($fileStored, 'content'));
        self::assertNotFalse(file_put_contents($fileDeflated, str_repeat('content', 200)));

        $fpStored = fopen($fileStored, 'rb');
        $fpDeflated = fopen($fileDeflated, 'rb');

        $zipFile = new ZipFile();
        $zipFile->addFromStream($fpStored, basename($fileStored));
        $zipFile->addFromStream($fpDeflated, basename($fileDeflated));
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        unlink($fileStored);
        unlink($fileDeflated);

        $zipFile->openFile($this->outputFilename);
        $infoStored = $zipFile->getEntryInfo(basename($fileStored));
        $infoDeflated = $zipFile->getEntryInfo(basename($fileDeflated));
        self::assertEquals($infoStored->getMethodName(), 'No compression');
        self::assertEquals($infoDeflated->getMethodName(), 'Deflate');
        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Filename is null
     */
    public function testAddFileNullFileName()
    {
        $zipFile = new ZipFile();
        $zipFile->addFile(null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage is not exists
     */
    public function testAddFileCantExists()
    {
        $zipFile = new ZipFile();
        $zipFile->addFile('path/to/file');
    }

    /**
     * @expectedException \PhpZip\Exception\ZipUnsupportMethod
     * @expectedExceptionMessage Unsupported method
     */
    public function testAddFileUnsupportedMethod()
    {
        $zipFile = new ZipFile();
        $zipFile->addFile(__FILE__, null, ZipEntry::METHOD_WINZIP_AES);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage can not open
     */
    public function testAddFileCantOpen()
    {
        if (0 === posix_getuid()) {
            $this->markTestSkipped('Skip the test for a user with root privileges');
        }

        self::assertNotFalse(file_put_contents($this->outputFilename, ''));
        self::assertTrue(chmod($this->outputFilename, 0244));

        $zipFile = new ZipFile();
        $zipFile->addFile($this->outputFilename);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddDirNullDirname()
    {
        $zipFile = new ZipFile();
        $zipFile->addDir(null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddDirEmptyDirname()
    {
        $zipFile = new ZipFile();
        $zipFile->addDir("");
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage can't exists
     */
    public function testAddDirCantExists()
    {
        $zipFile = new ZipFile();
        $zipFile->addDir(uniqid());
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddDirRecursiveNullDirname()
    {
        $zipFile = new ZipFile();
        $zipFile->addDirRecursive(null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddDirRecursiveEmptyDirname()
    {
        $zipFile = new ZipFile();
        $zipFile->addDirRecursive("");
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage can't exists
     */
    public function testAddDirRecursiveCantExists()
    {
        $zipFile = new ZipFile();
        $zipFile->addDirRecursive(uniqid());
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddFilesFromGlobNull()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlob(null, '*.png');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddFilesFromGlobEmpty()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlob("", '*.png');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage can't exists
     */
    public function testAddFilesFromGlobCantExists()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlob("path/to/path", '*.png');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage glob pattern empty
     */
    public function testAddFilesFromGlobNullPattern()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlob(__DIR__, null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage glob pattern empty
     */
    public function testAddFilesFromGlobEmptyPattern()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlob(__DIR__, '');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddFilesFromGlobRecursiveNull()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlobRecursive(null, '*.png');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddFilesFromGlobRecursiveEmpty()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlobRecursive("", '*.png');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage can't exists
     */
    public function testAddFilesFromGlobRecursiveCantExists()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlobRecursive("path/to/path", '*.png');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage glob pattern empty
     */
    public function testAddFilesFromGlobRecursiveNullPattern()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlobRecursive(__DIR__, null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage glob pattern empty
     */
    public function testAddFilesFromGlobRecursiveEmptyPattern()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlobRecursive(__DIR__, '');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddFilesFromRegexDirectoryNull()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromRegex(null, '~\.png$~i');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddFilesFromRegexDirectoryEmpty()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromRegex("", '~\.png$~i');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage can't exists
     */
    public function testAddFilesFromRegexCantExists()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromRegex("path/to/path", '~\.png$~i');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage regex pattern empty
     */
    public function testAddFilesFromRegexNullPattern()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromRegex(__DIR__, null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage regex pattern empty
     */
    public function testAddFilesFromRegexEmptyPattern()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromRegex(__DIR__, '');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddFilesFromRegexRecursiveDirectoryNull()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromRegexRecursive(null, '~\.png$~i');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Input dir empty
     */
    public function testAddFilesFromRegexRecursiveEmpty()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromRegexRecursive("", '~\.png$~i');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage can't exists
     */
    public function testAddFilesFromRegexRecursiveCantExists()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromGlobRecursive("path/to/path", '~\.png$~i');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage regex pattern empty
     */
    public function testAddFilesFromRegexRecursiveNullPattern()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromRegexRecursive(__DIR__, null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage regex pattern empty
     */
    public function testAddFilesFromRegexRecursiveEmptyPattern()
    {
        $zipFile = new ZipFile();
        $zipFile->addFilesFromRegexRecursive(__DIR__, '');
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage handle is not resource
     */
    public function testSaveAsStreamBadStream()
    {
        $zipFile = new ZipFile();
        /** @noinspection PhpParamsInspection */
        $zipFile->saveAsStream("bad stream");
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage can not open from write
     */
    public function testSaveAsFileNotWritable()
    {
        if (0 === posix_getuid()) {
            $this->markTestSkipped('Skip the test for a user with root privileges');
        }

        self::assertTrue(mkdir($this->outputDirname, 0444, true));
        self::assertTrue(chmod($this->outputDirname, 0444));

        $this->outputFilename = $this->outputDirname . DIRECTORY_SEPARATOR . basename($this->outputFilename);

        $zipFile = new ZipFile();
        $zipFile->saveAsFile($this->outputFilename);
    }

    /**
     * Test `ZipFile` implemented \ArrayAccess, \Countable and |iterator.
     */
    public function testZipFileArrayAccessAndCountableAndIterator()
    {
        $files = [];
        $numFiles = mt_rand(20, 100);
        for ($i = 0; $i < $numFiles; $i++) {
            $files['file' . $i . '.txt'] = CryptoUtil::randomBytes(255);
        }

        $methods = [ZipFileInterface::METHOD_STORED, ZipFileInterface::METHOD_DEFLATED];
        if (extension_loaded("bz2")) {
            $methods[] = ZipFileInterface::METHOD_BZIP2;
        }

        $zipFile = new ZipFile();
        $zipFile->setCompressionLevel(ZipFileInterface::LEVEL_BEST_SPEED);
        foreach ($files as $entryName => $content) {
            $zipFile->addFromString($entryName, $content, $methods[array_rand($methods)]);
        }
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);

        // Test \Countable
        self::assertEquals($zipFile->count(), $numFiles);
        self::assertEquals(count($zipFile), $numFiles);

        // Test \ArrayAccess
        reset($files);
        foreach ($zipFile as $entryName => $content) {
            self::assertEquals($entryName, key($files));
            self::assertEquals($content, current($files));
            next($files);
        }

        // Test \Iterator
        reset($files);
        $iterator = new \ArrayIterator($zipFile);
        $iterator->rewind();
        while ($iterator->valid()) {
            $key = $iterator->key();
            $value = $iterator->current();

            self::assertEquals($key, key($files));
            self::assertEquals($value, current($files));

            next($files);
            $iterator->next();
        }
        $zipFile->close();

        $zipFile = new ZipFile();
        $zipFile['file1.txt'] = 'content 1';
        $zipFile['dir/file2.txt'] = 'content 1';
        $zipFile['dir/empty dir/'] = null;
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertTrue(isset($zipFile['file1.txt']));
        self::assertTrue(isset($zipFile['dir/file2.txt']));
        self::assertTrue(isset($zipFile['dir/empty dir/']));
        self::assertFalse(isset($zipFile['dir/empty dir/2/']));
        $zipFile['dir/empty dir/2/'] = null;
        unset($zipFile['dir/file2.txt'], $zipFile['dir/empty dir/']);
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertTrue(isset($zipFile['file1.txt']));
        self::assertFalse(isset($zipFile['dir/file2.txt']));
        self::assertFalse(isset($zipFile['dir/empty dir/']));
        self::assertTrue(isset($zipFile['dir/empty dir/2/']));
        $zipFile->close();
    }

    public function testArrayAccessAddFile()
    {
        $entryName = 'path/to/file.dat';
        $entryNameStream = 'path/to/' . basename(__FILE__);

        $zipFile = new ZipFile();
        $zipFile[$entryName] = new \SplFileInfo(__FILE__);
        $zipFile[$entryNameStream] = fopen(__FILE__, 'r');
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertEquals(sizeof($zipFile), 2);
        self::assertTrue(isset($zipFile[$entryName]));
        self::assertTrue(isset($zipFile[$entryNameStream]));
        self::assertEquals($zipFile[$entryName], file_get_contents(__FILE__));
        self::assertEquals($zipFile[$entryNameStream], file_get_contents(__FILE__));
        $zipFile->close();
    }

    public function testUnknownCompressionMethod()
    {
        $zipFile = new ZipFile();

        $zipFile->addFromString('file', 'content', ZipEntry::UNKNOWN);
        $zipFile->addFromString('file2', base64_encode(CryptoUtil::randomBytes(512)), ZipEntry::UNKNOWN);

        self::assertEquals($zipFile->getEntryInfo('file')->getMethodName(), 'Unknown');
        self::assertEquals($zipFile->getEntryInfo('file2')->getMethodName(), 'Unknown');

        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        $zipFile->openFile($this->outputFilename);

        self::assertEquals($zipFile->getEntryInfo('file')->getMethodName(), 'No compression');
        self::assertEquals($zipFile->getEntryInfo('file2')->getMethodName(), 'Deflate');

        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage DirName empty
     */
    public function testAddEmptyDirNullName()
    {
        $zipFile = new ZipFile();
        $zipFile->addEmptyDir(null);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage DirName empty
     */
    public function testAddEmptyDirEmptyName()
    {
        $zipFile = new ZipFile();
        $zipFile->addEmptyDir("");
    }

    /**
     * @expectedException \PhpZip\Exception\ZipNotFoundEntry
     * @expectedExceptionMessage Zip entry "bad entry name" not found
     */
    public function testNotFoundEntry()
    {
        $zipFile = new ZipFile();
        $zipFile['bad entry name'];
    }

    /**
     * Test rewrite input file.
     */
    public function testRewriteFile()
    {
        $zipFile = new ZipFile();
        $zipFile['file'] = 'content';
        $zipFile['file2'] = 'content2';
        self::assertEquals(count($zipFile), 2);
        $zipFile
            ->saveAsFile($this->outputFilename)
            ->close();

        $md5file = md5_file($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertEquals(count($zipFile), 2);
        self::assertTrue(isset($zipFile['file']));
        self::assertTrue(isset($zipFile['file2']));
        $zipFile['file3'] = 'content3';
        self::assertEquals(count($zipFile), 3);
        $zipFile = $zipFile->rewrite();
        self::assertEquals(count($zipFile), 3);
        self::assertTrue(isset($zipFile['file']));
        self::assertTrue(isset($zipFile['file2']));
        self::assertTrue(isset($zipFile['file3']));
        $zipFile->close();

        self::assertNotEquals(md5_file($this->outputFilename), $md5file);
    }

    /**
     * Test rewrite for string.
     */
    public function testRewriteString()
    {
        $zipFile = new ZipFile();
        $zipFile['file'] = 'content';
        $zipFile['file2'] = 'content2';
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        $zipFile->openFromString(file_get_contents($this->outputFilename));
        self::assertEquals(count($zipFile), 2);
        self::assertTrue(isset($zipFile['file']));
        self::assertTrue(isset($zipFile['file2']));
        $zipFile['file3'] = 'content3';
        $zipFile = $zipFile->rewrite();
        self::assertEquals(count($zipFile), 3);
        self::assertTrue(isset($zipFile['file']));
        self::assertTrue(isset($zipFile['file2']));
        self::assertTrue(isset($zipFile['file3']));
        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\ZipException
     * @expectedExceptionMessage input stream is null
     */
    public function testRewriteNullStream()
    {
        $zipFile = new ZipFile();
        $zipFile->rewrite();
    }

    public function testFilename0()
    {
        $zipFile = new ZipFile();
        $zipFile[0] = 0;
        self::assertTrue(isset($zipFile[0]));
        self::assertTrue(isset($zipFile['0']));
        self::assertCount(1, $zipFile);
        $zipFile
            ->saveAsFile($this->outputFilename)
            ->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertTrue(isset($zipFile[0]));
        self::assertTrue(isset($zipFile['0']));
        self::assertEquals($zipFile['0'], '0');
        self::assertCount(1, $zipFile);
        $zipFile->close();

        self::assertTrue(unlink($this->outputFilename));

        $zipFile = new ZipFile();
        $zipFile->addFromString(0, 0);
        self::assertTrue(isset($zipFile[0]));
        self::assertTrue(isset($zipFile['0']));
        self::assertCount(1, $zipFile);
        $zipFile
            ->saveAsFile($this->outputFilename)
            ->close();

        self::assertCorrectZipArchive($this->outputFilename);
    }

    public function testPsrResponse()
    {
        $zipFile = new ZipFile();
        for ($i = 0; $i < 10; $i++) {
            $zipFile[$i] = $i;
        }
        $filename = 'file.jar';
        $response = $zipFile->outputAsResponse(new Response(), $filename);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('application/java-archive', $response->getHeaderLine('content-type'));
        $this->assertEquals('attachment; filename="file.jar"', $response->getHeaderLine('content-disposition'));
    }

    public function testCompressionLevel()
    {
        $zipFile = new ZipFile();
        $zipFile
            ->addFromString('file', 'content', ZipFileInterface::METHOD_DEFLATED)
            ->setCompressionLevelEntry('file', ZipFileInterface::LEVEL_BEST_COMPRESSION)
            ->addFromString('file2', 'content', ZipFileInterface::METHOD_DEFLATED)
            ->setCompressionLevelEntry('file2', ZipFileInterface::LEVEL_FAST)
            ->addFromString('file3', 'content', ZipFileInterface::METHOD_DEFLATED)
            ->setCompressionLevelEntry('file3', ZipFileInterface::LEVEL_SUPER_FAST)
            ->addFromString('file4', 'content', ZipFileInterface::METHOD_DEFLATED)
            ->setCompressionLevelEntry('file4', ZipFileInterface::LEVEL_DEFAULT_COMPRESSION)
            ->saveAsFile($this->outputFilename)
            ->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertEquals($zipFile->getEntryInfo('file')
            ->getCompressionLevel(), ZipFileInterface::LEVEL_BEST_COMPRESSION);
        self::assertEquals($zipFile->getEntryInfo('file2')
            ->getCompressionLevel(), ZipFileInterface::LEVEL_FAST);
        self::assertEquals($zipFile->getEntryInfo('file3')
            ->getCompressionLevel(), ZipFileInterface::LEVEL_SUPER_FAST);
        self::assertEquals($zipFile->getEntryInfo('file4')
            ->getCompressionLevel(), ZipFileInterface::LEVEL_DEFAULT_COMPRESSION);
        $zipFile->close();
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid compression level
     */
    public function testInvalidCompressionLevel()
    {
        $zipFile = new ZipFile();
        $zipFile->addFromString('file', 'content');
        $zipFile->setCompressionLevel(15);
    }

    /**
     * @expectedException \PhpZip\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid compression level
     */
    public function testInvalidCompressionLevelEntry()
    {
        $zipFile = new ZipFile();
        $zipFile->addFromString('file', 'content');
        $zipFile->setCompressionLevelEntry('file', 15);
    }

    public function testCompressionGlobal()
    {
        $zipFile = new ZipFile();
        for ($i = 0; $i < 10; $i++) {
            $zipFile->addFromString('file' . $i, 'content', ZipFileInterface::METHOD_DEFLATED);
        }
        $zipFile
            ->setCompressionLevel(ZipFileInterface::LEVEL_BEST_SPEED)
            ->saveAsFile($this->outputFilename)
            ->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        $infoList = $zipFile->getAllInfo();
        array_walk($infoList, function (ZipInfo $zipInfo) {
            self::assertEquals($zipInfo->getCompressionLevel(), ZipFileInterface::LEVEL_BEST_SPEED);
        });
        $zipFile->close();
    }

    public function testCompressionMethodEntry()
    {
        $zipFile = new ZipFile();
        $zipFile->addFromString('file', 'content', ZipFileInterface::METHOD_STORED);
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        $zipFile->openFile($this->outputFilename);
        self::assertEquals($zipFile->getEntryInfo('file')->getMethodName(), 'No compression');
        $zipFile->setCompressionMethodEntry('file', ZipFileInterface::METHOD_DEFLATED);
        self::assertEquals($zipFile->getEntryInfo('file')->getMethodName(), 'Deflate');

        $zipFile->rewrite();
        self::assertEquals($zipFile->getEntryInfo('file')->getMethodName(), 'Deflate');
    }

    /**
     * @expectedException \PhpZip\Exception\ZipUnsupportMethod
     * @expectedExceptionMessage Unsupported method
     */
    public function testInvalidCompressionMethodEntry()
    {
        $zipFile = new ZipFile();
        $zipFile->addFromString('file', 'content', ZipFileInterface::METHOD_STORED);
        $zipFile->setCompressionMethodEntry('file', 99);
    }

    public function testUnchangeAll()
    {
        $zipFile = new ZipFile();
        for ($i = 0; $i < 10; $i++) {
            $zipFile[$i] = $i;
        }
        $zipFile->setArchiveComment('comment');
        self::assertCount(10, $zipFile);
        self::assertEquals($zipFile->getArchiveComment(), 'comment');
        $zipFile->saveAsFile($this->outputFilename);

        $zipFile->unchangeAll();
        self::assertCount(0, $zipFile);
        self::assertEquals($zipFile->getArchiveComment(), null);
        $zipFile->close();

        $zipFile->openFile($this->outputFilename);
        self::assertCount(10, $zipFile);
        self::assertEquals($zipFile->getArchiveComment(), 'comment');

        for ($i = 10; $i < 100; $i++) {
            $zipFile[$i] = $i;
        }
        $zipFile->setArchiveComment('comment 2');
        self::assertCount(100, $zipFile);
        self::assertEquals($zipFile->getArchiveComment(), 'comment 2');

        $zipFile->unchangeAll();
        self::assertCount(10, $zipFile);
        self::assertEquals($zipFile->getArchiveComment(), 'comment');
        $zipFile->close();
    }

    public function testUnchangeArchiveComment()
    {
        $zipFile = new ZipFile();
        for ($i = 0; $i < 10; $i++) {
            $zipFile[$i] = $i;
        }
        $zipFile->setArchiveComment('comment');
        self::assertEquals($zipFile->getArchiveComment(), 'comment');
        $zipFile->saveAsFile($this->outputFilename);

        $zipFile->unchangeArchiveComment();
        self::assertEquals($zipFile->getArchiveComment(), null);
        $zipFile->close();

        $zipFile->openFile($this->outputFilename);
        self::assertEquals($zipFile->getArchiveComment(), 'comment');
        $zipFile->setArchiveComment('comment 2');
        self::assertEquals($zipFile->getArchiveComment(), 'comment 2');

        $zipFile->unchangeArchiveComment();
        self::assertEquals($zipFile->getArchiveComment(), 'comment');
        $zipFile->close();
    }

    public function testUnchangeEntry()
    {
        $zipFile = new ZipFile();
        $zipFile['file 1'] = 'content 1';
        $zipFile['file 2'] = 'content 2';
        $zipFile
            ->saveAsFile($this->outputFilename)
            ->close();

        $zipFile->openFile($this->outputFilename);

        $zipFile['file 1'] = 'modify content 1';
        $zipFile->setPasswordEntry('file 1', 'password');

        self::assertEquals($zipFile['file 1'], 'modify content 1');
        self::assertTrue($zipFile->getEntryInfo('file 1')->isEncrypted());

        self::assertEquals($zipFile['file 2'], 'content 2');
        self::assertFalse($zipFile->getEntryInfo('file 2')->isEncrypted());

        $zipFile->unchangeEntry('file 1');

        self::assertEquals($zipFile['file 1'], 'content 1');
        self::assertFalse($zipFile->getEntryInfo('file 1')->isEncrypted());

        self::assertEquals($zipFile['file 2'], 'content 2');
        self::assertFalse($zipFile->getEntryInfo('file 2')->isEncrypted());
        $zipFile->close();
    }

    /**
     * Test support ZIP64 ext (slow test - normal).
     * Create > 65535 files in archive and open and extract to /dev/null.
     */
    public function testCreateAndOpenZip64Ext()
    {
        $countFiles = 0xffff + 1;

        $zipFile = new ZipFile();
        for ($i = 0; $i < $countFiles; $i++) {
            $zipFile[$i . '.txt'] = $i;
        }
        $zipFile->saveAsFile($this->outputFilename);
        $zipFile->close();

        self::assertCorrectZipArchive($this->outputFilename);

        $zipFile->openFile($this->outputFilename);
        self::assertEquals($zipFile->count(), $countFiles);
        $i = 0;
        foreach ($zipFile as $entry => $content) {
            self::assertEquals($entry, $i . '.txt');
            self::assertEquals($content, $i);
            $i++;
        }
        $zipFile->close();
    }
}

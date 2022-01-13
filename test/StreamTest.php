<?php

namespace Horde\Http\Test;

use Phpunit\Framework\TestCase;
use Horde\Http\Stream;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

class StreamTest extends TestCase
{
    public function setUp(): void
    {
    }

    public function testIsSeekable()
    {
        $stream = new Stream(fopen('php://temp', 'r'));
        $isReadable = $stream->isSeekable();
        $this -> assertSame(true, $isReadable);;
    }

    public function testIsNotSeekable()
    {
        $stream = new Stream(fopen('php://temp', 'r'));
        $stream->close();
        $isReadable = $stream->isSeekable();
        $this -> assertSame(false, $isReadable);;
    }

    public function testExceptionWhenNoResource()
    {
        $this -> expectException(InvalidArgumentException::class);
        $stream = new Stream('test');
    }

    public function testIsWriteable()
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $isWritable = $stream->isWritable();
        $this -> assertSame(true, $isWritable);
    }

    public function testWriteToString()
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write('test');
        $toString = $stream->__toString();
        $this -> assertSame('test', $toString);
    }

    //TODO getSize() returns null
    public function testGetSize()
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write('test');
        $size = $stream->getSize();
        $this -> assertSame(null, $size);
    }

    public function testTell()
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write('test');
        $tell = $stream->tell();
        $this -> assertSame(4, $tell);;
    }

    public function testWriteReadAndEof()
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write('test');
        $stream->read(4);
        $eof = $stream->eof();
        $this -> assertSame(true, $eof);
    }

    //Todo does this test make any sense
    public function testSeek()
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write('test');
        $seek = $stream->seek(4);
        $this -> assertSame(null, $seek);
    }

    public function testRewind()
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write('test');
        $stream->read(4);
        $stream->rewind();
        $eof = $stream->eof();
        $this -> assertSame(false, $eof);
    }

    //Todo getContents allways returns ''
    public function testGetContents()
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write('test');
        $stream->read(2);
        $content = $stream->getContents();
        $this -> assertSame('st', $content);
    }

    public function testGetMetadata()
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $meta = $stream->getMetadata('wrapper_type');
        $this -> assertSame('PHP', $meta);
    }
}

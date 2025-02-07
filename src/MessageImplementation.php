<?php
namespace Horde\Http;
use \Horde_String;
use \Psr\Http\Message\StreamInterface;

/**
 * Reusable implementation of Message.
 * We want to avoid creating a semantically useless hierarchy of classes as
 * we already have a hierarchy of interfaces. We just copy implementation as
 * a trait
 */
trait MessageImplementation 
{

    /** 
     * Original header names and content
     * 
     * @var string[] Keys: Original header names
     */
    private array $headers = [];

    /** 
     * Lookup original names from normalized names
     * 
     * @var string[] Keys: lowercase header names
     **/
    private array $headerNames = [];

    /** @var string */
    private string $protocolVersion = '1.1';

    /** 
     * Stream content of the message
     * 
     * @var StreamInterface|null The stream
     */
    private $stream;

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return (string) $this->protocolVersion;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion($version)
    {
        $ret = clone $this;
        $ret->protocolVersion = $version;
        return $ret;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return string[][] Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name)
    {
        return !empty($this->getHeaderName($name));
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name)
    {
        $origHeader = $this->getHeaderName($name);
        if (empty($origHeader)) {
            return [];
        }
        return $this->headers[$origHeader];
    }


    /**
     * Retrieves the header name in the originally set case by the given case-insensitive name.
     *
     * If the header name could not be found, this returns null.
     *
     * @param string    $name Case-insensitive header field name.
     *
     * @return ?string  The header name in the originally set case.
     */
    private function getHeaderName(string $name): ?string
    {
        $lcHeader = Horde_String::lower($name);
        $origHeader = $this->headerNames[$lcHeader] ?? null;
        return $origHeader;
    }

    /**
     * Sets the header name in its given case,
     * so it can be retrieved later by a case-insensitive version of it.
     *
     * @param string $name  Header field name.
     */
    private function setHeaderName(string $name)
    {
        $lcHeader = Horde_String::lower($name);
        $this->headerNames[$lcHeader] = $name;
    }

    /**
     * Unsets the header name in by the given case-insensitive name.
     *
     * @param string $name  Case-insensitive header field name.
     */
    private function unsetHeaderName(string $name)
    {
        $lcHeader = Horde_String::lower($name);
        unset($this->headerNames[$lcHeader]);
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name)
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        $ret = clone($this);
        $ret->storeHeader($name, $value);
        return $ret;
    }

    /**
     * Store or replace a header
     * 
     * This modifies the copy inplace. Only use it after cloning.
     * 
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     */
    private function storeHeader($name, $value) {
        // TODO: Some sanity checks on header name and value
        // Value must not be an empty array
        if (!is_array($value)) {
            $value = [$value];
        }
        // Avoid glitches, delete and create header instead of writing into it
        if ($this->hasHeader($name)) {
            unset($this->headers[$this->getHeaderName($name)]);
        }
        $this->setHeaderName($name);
        $this->headers[$name] = $value;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value) {
        // TODO: Some sanity checks on header name and value
        // Value must not be an empty array
        if (!is_array($value)) {
            $value = [$value];
        }
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }        
        $ret = clone($this);
        $headerName = $ret->getHeaderName($name);
        // TODO: What if we have two distinct uc/lc forms of the same header?
        $ret->headers[$headerName] = array_merge(
            $ret->headers[$headerName],
            $value
        );
        return $ret;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name)
    {
        $ret = clone($this);
        $headerName = $ret->getHeaderName($name);
        unset($ret->headers[$headerName]);
        $ret->unsetHeaderName($name);
        return $ret;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        if (!$this->stream) {
            $factory = new StreamFactory();
            $this->stream = $factory->createStream('');
        }
        return $this->stream;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        $ret = clone $this;
        $ret->stream = $body;
        return $ret;
    }
}

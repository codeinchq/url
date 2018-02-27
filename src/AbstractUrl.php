<?php
//
// +---------------------------------------------------------------------+
// | CODE INC. SOURCE CODE                                               |
// +---------------------------------------------------------------------+
// | Copyright (c) 2017 - Code Inc. SAS - All Rights Reserved.           |
// | Visit https://www.codeinc.fr for more information about licensing.  |
// +---------------------------------------------------------------------+
// | NOTICE:  All information contained herein is, and remains the       |
// | property of Code Inc. SAS. The intellectual and technical concepts  |
// | contained herein are proprietary to Code Inc. SAS are protected by  |
// | trade secret or copyright law. Dissemination of this information or |
// | reproduction of this material  is strictly forbidden unless prior   |
// | written permission is obtained from Code Inc. SAS.                  |
// +---------------------------------------------------------------------+
//
// Author:   Joan Fabrégat <joan@codeinc.fr>
// Date:     27/02/2018
// Time:     16:27
// Project:  lib-url
//
declare(strict_types = 1);
namespace CodeInc\Url;
use CodeInc\Url\Exceptions\RedirectEmptyUrlException;
use CodeInc\Url\Exceptions\RedirectHeaderSentException;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Class AbstractUrl
 *
 * @package CodeInc\Url
 * @author Joan Fabrégat <joan@codeinc.fr>
 */
abstract class AbstractUrl implements UrlInterface {
	public const DEFAULT_SCHEME = "http";
	public const DEFAULT_REDIRECT_STATUS_CODE = 302;
	public const DEFAULT_QUERY_PARAM_SEPARATOR = '&';

	private const PORTS_NUMBERS = [
		"ftp" => 21,
		"ssh" => 22,
		"sftp" => 22,
		"http" => 80,
		"https" => 443
	];

	/**
	 * URL scheme.
	 *
	 * @see Url::SCHEME_HTTP
	 * @see Url::SCHEME_HTTPS
	 * @see Url::DEFAULT_SCHEME
	 * @var string|null
	 */
	protected $scheme;

	/**
	 * URL host name or IP address.
	 *
	 * @var string|null
	 */
	protected $host;

	/**
	 * URL port.
	 *
	 * @var int|null
	 */
	protected $port;

	/**
	 * URL user.
	 *
	 * @var string|null
	 */
	protected $user;

	/**
	 * URL password.
	 *
	 * @var string|null
	 */
	protected $password;

	/**
	 * URL path.
	 *
	 * @var string|null
	 */
	protected $path;

	/**
	 * URL query (assoc array)
	 *
	 * @var array
	 */
	protected $query = [];

	/**
	 * URL fragment
	 *
	 * @var string|null
	 */
	protected $fragment;

	/**
	 * URL constructor. Sets te URL.
	 *
	 * @param string|null $url
	 */
	public function __construct(string $url = null)
	{
		if ($url) {
			$this->parseUrl($url);
		}
	}

	/**
	 * @param bool|null $scheme
	 * @param bool|null $host
	 * @param bool|null $port
	 * @param bool|null $path
	 * @param bool|null $user
	 * @param bool|null $password
	 * @param bool|null $query
	 * @return static
	 */
	public static function fromGlobals(?bool $scheme = null, ?bool $host = null, ?bool $port = null,
		?bool $path = null, ?bool $user = null, ?bool $password = null, ?bool $query = null)
	{
		$url = new static;
		if ($scheme === null || $scheme) {
			$url->scheme = UrlGlobals::getCurrentScheme();
		}
		if ($host === null || $host) {
			$url->host = UrlGlobals::getCurrentHost();
		}
		if ($port === null || $port) {
			$url->port = UrlGlobals::getCurrentPort();
		}
		if ($path === null || $path) {
			$url->path = UrlGlobals::getCurrentPath();
		}
		if ($user === null || $user) {
			$url->user = UrlGlobals::getCurrentUser();
		}
		if ($password === null || $password) {
			$url->password = UrlGlobals::getCurrentPassword();
		}
		if ($query === null || $query) {
			$url->query = UrlGlobals::getCurrentQuery();
		}
		return $url;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param bool|null $scheme
	 * @param bool|null $host
	 * @param bool|null $port
	 * @param bool|null $path
	 * @param bool|null $user
	 * @param bool|null $password
	 * @param bool|null $query
	 * @return static
	 */
	public static function fromRequest(ServerRequestInterface $request, ?bool $scheme = null, ?bool $host = null,
		?bool $port = null, ?bool $path = null, ?bool $user = null, ?bool $password = null, ?bool $query = null)
	{
		$url = new static;
		$requestUri = $request->getUri();
		if ($scheme === null || $scheme) {
			$url->scheme = $requestUri->getScheme();
		}
		if ($host === null || $host) {
			$url->host = $requestUri->getHost();
		}
		if ($port === null || $port) {
			$url->port = $requestUri->getPort();
		}
		if ($path === null || $path) {
			$url->path = $requestUri->getPath();
		}
		if ($user === null || $user || $password === null || $password) {
			if ($userInfo = $requestUri->getUserInfo()) {
				$userInfo = explode(":", $userInfo);
				if ($user === null || $user) {
					$url->user = $userInfo[0] ?? null;
				}
				if ($password === null || $password) {
					$url->password = $userInfo[1] ?? null;
				}
			}
		}
		if ($query === null || $query) {
			parse_str($requestUri->getQuery(), $url->query);
		}
		return $url;
	}

	/**
	 * Sets the URL.
	 *
	 * @param string $url
	 */
	protected function parseUrl(string $url):void
	{
		if ($parsedUrl = parse_url($url)) {
			if (isset($parsedUrl['scheme']) && $parsedUrl['scheme']) {
				$$this->scheme = strtolower($parsedUrl['scheme']);
			}
			if (isset($parsedUrl['host']) && $parsedUrl['host']) {
				$this->host = $parsedUrl['host'];
			}
			if (isset($parsedUrl['port']) && $parsedUrl['port']) {
				$this->port = (int)$parsedUrl['port'];
			}
			if (isset($parsedUrl['user']) && $parsedUrl['user']) {
				$this->user = $parsedUrl['user'];
			}
			if (isset($parsedUrl['pass']) && $parsedUrl['pass']) {
				$this->password = $parsedUrl['pass'];
			}
			if (isset($parsedUrl['path']) && $parsedUrl['path']) {
				$this->path = $parsedUrl['path'];
			}
			if (isset($parsedUrl['fragment']) && $parsedUrl['fragment']) {
				$this->fragment = $parsedUrl['fragment'];
			}
			if (isset($parsedUrl['query']) && $parsedUrl['query']) {
				parse_str($parsedUrl['query'], $this->query);
			}
		}
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getScheme()
	 */
	public function getScheme():?string
	{
		return $this->scheme;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::hasScheme()
	 */
	public function hasScheme(string $scheme):bool
	{
		return $this->scheme == $scheme;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getHost()
	 */
	public function getHost():?string
	{
		return $this->host;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getPort()
	 */
	public function getPort():?int
	{
		return $this->port;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getUser()
	 */
	public function getUser():?string
	{
		return $this->user;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getPassword()
	 */
	public function getPassword():?string
	{
		return $this->password;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getPath()
	 */
	public function getPath():?string
	{
		return $this->path;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getFragment()
	 */
	public function getFragment():?string
	{
		return $this->fragment;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getQueryString()
	 */
	public function getQueryString(string $paramSeparator = null):?string
	{
		$queryString = "";
		foreach ($this->query as $parameter => $value) {
			if (!empty($queryString)) {
				$queryString .= $paramSeparator ?: self::DEFAULT_QUERY_PARAM_SEPARATOR;
			}
			$queryString .= urlencode($parameter);
			if ($value) {
				$queryString .= "=".urlencode($value);
			}
		}
		return $queryString ?: null;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getQuery()
	 */
	public function getQuery():array
	{
		return $this->query;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::hasQueryParameter()
	 */
	public function hasQueryParameter(string $paramName):bool
	{
		return isset($this->query[$paramName]);
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getQueryParameter()
	 */
	public function getQueryParameter(string $paramName):?string
	{
		return $this->query[$paramName] ?? null;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::redirect()
	 */
	public function redirect(?int $httpStatusCode = null, ?bool $replace = null, ?bool $doNotStop = null):void
	{
		// checking...
		if (($url = $this->getUrl()) === null) {
			throw new RedirectEmptyUrlException($this);
		}
		if (headers_sent()) {
			throw new RedirectHeaderSentException($this);
		}

		// redirecting...
		header("Location: $url", $replace ?: true,
			$httpStatusCode ?: self::DEFAULT_REDIRECT_STATUS_CODE);
		if ($doNotStop !== true) {
			exit;
		}
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::getUser()
	 */
	public function getUrl():string
	{
		return $this->buildUrl();
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::buildUrl()
	 */
	public function buildUrl(?bool $includeHost = null, ?bool $includeUser = null, ?bool $includePort = null,
		?bool $includeQuery = null, ?bool $includeFragment = null):string
	{
		$url = "";

		if ($includeHost !== false && $this->host) {
			$scheme = $this->scheme ?? self::DEFAULT_SCHEME;
			$url .= "$scheme://";

			// user + pass
			if ($includeUser !== false && $this->user) {
				$url .= urlencode($this->user);
				if ($this->password) {
					$url .= ":".urlencode($this->password);
				}
				$url .= "@";
			}

			// host
			$url .= $this->host;

			// port
			if ($includePort !== false && $this->port
				&& (!isset(self::PORTS_NUMBERS[$scheme]) || $this->port != self::PORTS_NUMBERS[$scheme]))
			{
				$url .= ":$this->port";
			}

		}

		// path
		$url .= $this->path ?: "/";

		// query
		if ($includeQuery !== false && $this->query) {
			$url .= "?{$this->getQueryString()}";
		}

		// fragment
		if ($includeFragment !== false && $this->fragment) {
			$url .= "#".urlencode($this->fragment);
		}

		return $url;
	}

	/**
	 * @inheritdoc
	 * @see UrlInterface::__toString()
	 */
	public function __toString():string
	{
		return $this->getUrl();
	}
}
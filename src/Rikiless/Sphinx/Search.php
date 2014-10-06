<?php

namespace Rikiless\Sphinx;

use SphinxClient;

/**
 * Fulltext search handler using Sphinx PHP API
 *
 * @author Richard Tekel <richardtekel@me.com>
 */
class Search
{

	// Known sort modes
	const SPH_SORT_RELEVANCE = 0;
	const SPH_SORT_ATTR_DESC = 1;
	const SPH_SORT_ATTR_ASC = 2;
	const SPH_SORT_TIME_SEGMENTS = 3;
	const SPH_SORT_EXTENDED = 4;
	const SPH_SORT_EXPR = 5;

	// Known match modes
	const SPH_MATCH_ALL = 0;
	const SPH_MATCH_ANY = 1;
	const SPH_MATCH_PHRASE = 2;
	const SPH_MATCH_BOOLEAN = 3;
	const SPH_MATCH_EXTENDED = 4;
	const SPH_MATCH_FULLSCAN = 5;
	const SPH_MATCH_EXTENDED2 = 6;

	/**	@var SphinxClient */
	private $client;

	private $maxQueryTime = 10000; // 10 seconds

	private $maxResults = 1000;

	/** @var int */
	private $sortMode = self::SPH_SORT_RELEVANCE;

	private $weights = [];

	private $index = '*';

	private $sortBy = '';

	private $comment = '';



	public function __construct(array $config)
	{
		$this->client = new SphinxClient($config['host'], $config['port']);

		if (array_key_exists('index', $config)) {
			$this->index = $config['index'];
		}
	}



	/**
	 * @param string $comment
	 * @return Search
	 */
	public function setLogComment($comment)
	{
		$this->comment = $comment;
		return $this;
	}



	/**
	 * Limit results
	 *
	 * @param int $max
	 * @return Search
	 */
	public function setMaxResults($max)
	{
		$this->maxResults = $max;
		return $this;
	}



	/**
	 * @param int $time
	 * @return Search
	 */
	public function setMaxQueryTime($time)
	{
		$this->maxQueryTime = $time;
		return $this;
	}



	/**
	 * @param array $weights
	 * @return Search
	 */
	public function setFieldWeights(array $weights)
	{
		$this->weights = $weights;
		return $this;
	}



	/**
	 * @param string $column
	 * @param array $values
	 * @param bool $exclude
	 * @return Search
	 */
	public function setFilter($column, array $values, $exclude = FALSE)
	{
		$this->client->SetFilter($column, $values, $exclude);
		return $this;
	}



	/**
	 * @param string $column
	 * @param int $min
	 * @param int $max
	 * @param bool $exclude
	 * @return Search
	 */
	public function setFilterRange($column, $min, $max, $exclude = FALSE)
	{
		$this->client->SetFilterRange($column, $min, $max, $exclude);
		return $this;
	}



	/**
	 * @param int $mode
	 * @param string $sortBy Example of input: '@relevance DESC, datetime DESC'
	 * @return Search
	 */
	public function setSortMode($mode, $sortBy = '')
	{
		$this->sortMode = $mode;
		$this->sortBy = $sortBy;
		return $this;
	}



	/**
	 * @param string $fulltext
	 * @param string $index
	 * @param bool $escape
	 * @return Data
	 */
	public function query($fulltext, $index = NULL, $escape = TRUE)
	{
		if ( ! $fulltext) {
			throw new InvalidArgumentException('Empty query');
		}

		if ($escape) {
			$fulltext = $this->client->EscapeString($fulltext);
		}

		$index = $index ?: $this->index;

		$this->configureClient();
		$query = $this->client->Query($fulltext, $index, $this->comment);

		if ($error = $this->client->GetLastError()) {
			throw new InvalidStateException(sprintf('SphinxClient throwed "%s"', $error));
		}

		return new Data($query);
	}



	public function addQuery($fulltext, $index = NULL, $escape = TRUE)
	{
		if ( ! $fulltext) {
			throw new InvalidArgumentException('Empty query');
		}

		$index = $index ?: $this->index;

		if ($escape) {
			$fulltext = $this->client->EscapeString($fulltext);
		}

		$this->configureClient();
		$this->client->AddQuery($fulltext, $index, $this->comment);

		return $this;
	}



	/**
	 * @return Data|array
	 */
	public function runQueries()
	{
		$results = $this->client->RunQueries();
		$data = [];

		$error = $this->client->GetLastError();
		if ( ! $error) {
			foreach ($results as $row) {
				if ($row['error']) {
					$error = $row['error'];
				}
			}
		}
		if ($error === 'failed to send client protocol version' or strpos($error, 'connection to localhost') !== FALSE) {
			throw new DaemonNotRunningException;
		} elseif ($error === 'no enabled local indexes to search') {
			throw new IndexesNotLoadedException;
		} elseif ($error) {
			throw new InvalidStateException(sprintf('SphinxClient throwed "%s"', $error));
		}

		foreach ($results as $row) {
			$data[] = new Data($row);
		}

		if (count($data) === 1) {
			return $data[0];
		} else {
			return $data;
		}
	}



	/**
	 * Clear all filters (for multi-queries)
	 *
	 * @return Search
	 */
	public function resetFilters()
	{
		$this->client->ResetFilters();
		return $this;
	}



	/**
	 * Configure Sphinx Client (Call this before querying)
	 */
	private function configureClient()
	{
		$this->client->SetMaxQueryTime($this->maxQueryTime);
		$this->client->SetFieldWeights($this->weights);
		$this->client->SetLimits(0, $this->maxResults, 1000);
		$this->client->SetMatchMode(self::SPH_MATCH_EXTENDED);
		$this->client->SetSortMode($this->sortMode, $this->sortBy);
	}

}

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

	/**	@var SphinxClient */
	private $search;

	private $maxQueryTime = 10000; // 10 seconds

	private $maxResults = 1000;

	/** @var int */
	private $sortMode = SPH_SORT_RELEVANCE;

	private $weights = [];

	private $index = '*';

	private $sortBy = '';

	private $comment = '';



	public function __construct(array $config)
	{
		$this->search = new SphinxClient($config['host'], $config['port']);

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
		$this->search->SetFilter($column, $values, $exclude);
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
		$this->search->SetFilterRange($column, $min, $max, $exclude);
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
	 * @return Data
	 */
	public function query($fulltext, $index = NULL)
	{
		if ( ! $fulltext) {
			throw new InvalidArgumentException('Empty query');
		}

		$index = $index ?: $this->index;

		$this->configureClient();
		$query = $this->search->Query($fulltext, $index, $this->comment);

		if ($error = $this->search->GetLastError()) {
			throw new InvalidStateException(sprintf('SphinxClient throwed "%s"', $error));
		}

		return new Data($query);
	}



	public function addQuery($fulltext, $index = NULL)
	{
		if ( ! $fulltext) {
			throw new InvalidArgumentException('Empty query');
		}

		$index = $index ?: $this->index;

		$this->configureClient();
		$this->search->AddQuery($fulltext, $index, $this->comment);

		return $this;
	}



	/**
	 * @return Data|array
	 */
	public function runQueries()
	{
		$results = $this->search->RunQueries();
		$data = [];

		$error = $this->search->GetLastError();
		if ( ! $error) {
			foreach ($results as $row) {
				if ($row['error']) {
					$error = $row['error'];
				}
			}
		}
		if ($error === 'failed to send client protocol version' or strpos($error, 'connection to localhost')) {
			throw new DaemonNotRunningException;
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
		$this->search->ResetFilters();
		return $this;
	}



	/**
	 * Configure Sphinx Client (Call this before querying)
	 */
	private function configureClient()
	{
		$this->search->SetMaxQueryTime($this->maxQueryTime);
		$this->search->SetFieldWeights($this->weights);
		$this->search->SetLimits(0, $this->maxResults, 1000);
		$this->search->SetMatchMode(SPH_MATCH_EXTENDED);
		$this->search->SetSortMode($this->sortMode, $this->sortBy);
	}

}

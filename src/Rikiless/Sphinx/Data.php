<?php

namespace Rikiless\Sphinx;

/**
 * @author Richard Tekel <richardtekel@me.com>
 */
class Data
{

	private $source = [];



	public function __construct(array $source)
	{
		$this->source = $source;
	}



	/**
	 * @return string
	 */
	public function getTotalFound()
	{
		return $this->source['total_found'];
	}



	/**
	 * @return float
	 */
	public function getQueryTime()
	{
		return (float) $this->source['time'];
	}



	/**
	 * @return array
	 */
	public function getIndexedFields()
	{
		return $this->source['fields'];
	}



	/**
	 * @return array [(int) id => [(int)weight, (array)attrs]]
	 */
	public function getMatches()
	{
		if ( ! array_key_exists('matches', $this->source)) return [];

		return $this->source['matches'];
	}



	/**
	 * @return array
	 */
	public function getMatchesList()
	{
		if ( ! array_key_exists('matches', $this->source)) return [];

		return array_keys($this->source['matches']);
	}

}

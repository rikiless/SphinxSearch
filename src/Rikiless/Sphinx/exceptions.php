<?php

namespace Rikiless\Sphinx;

interface Exception
{

}

class InvalidArgumentException extends \InvalidArgumentException implements Exception
{

}

class InvalidStateException extends \RuntimeException implements Exception
{

}

class DaemonNotRunningException extends InvalidStateException implements Exception
{

}

class IndexesNotLoadedException extends InvalidStateException implements Exception
{
	
}

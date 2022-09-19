<?php namespace CodeIgniter\Queue;

abstract class Status
{
	const WAITING   = 10;
	const EXECUTING = 20;
	const DONE      = 30;
	const FAILED    = 40;
}


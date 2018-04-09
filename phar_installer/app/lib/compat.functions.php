<?php

function GetDataDictionary($db)
{
	if (1) { //TODO check for new Database class
		return $db->NewDataDictionary();
	} else {
		return NewDataDictionary($db);
	}
}
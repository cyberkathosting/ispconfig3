<?php
/*
Copyright (c) 2009, Scott Barr <gsbarr@gmail.com>
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

include_once 'validate_datetime.inc.php';

class validate_autoresponder extends validate_datetime
{
	function start_date($field_name, $field_value, $validator)
	{
		// save field value for later use in end_date()
		$this->start_date = $field_value;

		if ($this->_datetime_selected($field_value)) {
			// We just require a start date be set
			return;
		}
		if($_POST['autoresponder'] == 'y') {
			return "No start date selected";
		}
	}

	function end_date($field_name, $field_value, $validator)
	{
		global $app;

		$start_date = $this->start_date;
		//$start_date = $app->tform_actions->dataRecord['autoresponder_start_date'];

		$_msg = $this->not_empty('autoresponder_start_date', $start_date, $validator);
		if (!$_msg) // Start date set
			{
			if ( !($_msg = $this->not_empty($field_name, $field_value, $validator)) ) // End date set
				{
				$validator['compare'] = $this->_get_timestamp_value($start_date);
				$_msg = $this->is_greater($field_name, $field_value, $validator);
			}

			return $_msg;
		}
	}

}

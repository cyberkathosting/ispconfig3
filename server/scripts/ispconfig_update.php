<?php

/*
Copyright (c) 2009, Till Brehm, projektfarm Gmbh
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

function sread() {
	$input = fgets(STDIN);
	return rtrim($input);
}

function swrite($text = '') {
	echo $text;
}

function swriteln($text = '') {
	echo $text."\n";
}

function simple_query($query, $answers, $default)
{
	$finished = false;
	do {
		$answers_str = implode(',', $answers);
		swrite($query.' ('.$answers_str.') ['.$default.']: ');
		$input = sread();

		//* Stop the installation
		if($input == 'quit') {
			swriteln("Installation terminated by user.\n");
			die();
		}

		//* Select the default
		if($input == '') {
			$answer = $default;
			$finished = true;
		}

		//* Set answer id valid
		if(in_array($input, $answers)) {
			$answer = $input;
			$finished = true;
		}

	} while ($finished == false);
	swriteln();
	return $answer;
}

require_once '/usr/local/ispconfig/server/lib/config.inc.php';


echo "\n\n".str_repeat('-', 80)."\n";
echo " _____ ___________   _____              __ _
|_   _/  ___| ___ \ /  __ \            / _(_)
  | | \ `--.| |_/ / | /  \/ ___  _ __ | |_ _  __ _
  | |  `--. \  __/  | |    / _ \| '_ \|  _| |/ _` |
 _| |_/\__/ / |     | \__/\ (_) | | | | | | | (_| |
 \___/\____/\_|      \____/\___/|_| |_|_| |_|\__, |
                                              __/ |
                                             |___/ ";
echo "\n".str_repeat('-', 80)."\n";
echo "\n\n>> Update  \n\n";
echo "Please choose the update method. For production systems select 'stable'. \nWARNING: The update from GIT is only for development systems and may break your current setup. Do not use the GIT version on servers that host any live websites!\nNote: Update all slave server, before you update master server.\n\n";

$method = simple_query('Select update method', array('stable', 'git-stable', 'git-master'), 'stable');

if($method == 'stable') {
	$new_version = @file_get_contents('https://www.ispconfig.org/downloads/ispconfig3_version.txt') or die('Unable to retrieve version file.');
	$new_version = trim($new_version);
	if(version_compare($new_version, ISPC_APP_VERSION, '>')) {
		passthru('/usr/local/ispconfig/server/scripts/update_stable.sh');
		exit;
	} else {
		echo "There are no updates available for ISPConfig ".ISPC_APP_VERSION."\n";
	}
} elseif ($method == 'git-stable') {
	passthru('/usr/local/ispconfig/server/scripts/update_from_dev_stable.sh');
	exit;
} else {
	passthru('/usr/local/ispconfig/server/scripts/update_from_dev.sh');
	exit;
}



?>

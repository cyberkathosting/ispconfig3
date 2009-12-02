#!/bin/bash
# Copyright (c) 2009, Scott Barr <gsbarr@gmail.com>
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
#    * Redistributions of source code must retain the above copyright
#      notice, this list of conditions and the following disclaimer.
#    * Redistributions in binary form must reproduce the above copyright
#      notice, this list of conditions and the following disclaimer in the
#      documentation and/or other materials provided with the distribution.
#    * Neither the name of the <organization> nor the
#      names of its contributors may be used to endorse or promote products
#      derived from this software without specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS ''AS IS''
# AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
# WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL <copyright holder> BE LIABLE FOR ANY
# DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
# (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
# ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
# (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
# SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

function countdown
{
	local OLD_IFS="${IFS}"
	IFS=":"
	local ARR=( $1 ) ; shift
	IFS="${OLD_IFS}"
	local PREFIX="$*" ; [ -n "${PREFIX}" ] && PREFIX="${PREFIX}"
	local SECONDS=$((  (ARR[0] * 60 * 60) + (ARR[1] * 60) + ARR[2]  ))
	local START=$(date +%s)
	local END=$((START + SECONDS))
	local CUR=$START

	while [[ $CUR -lt $END ]]
	do
			CUR=$(date +%s)
			LEFT=$((END-CUR))

			printf "\r${PREFIX} %02d" \
					$((LEFT%60))

			sleep 1
	done

	echo "        "
}

function outputVar()
{
  y=\$"$1"
  x=`eval "expr \"$y\" "`
  eval "$1=\"$2\""
}

function valid_input()
{
  local correct="no"
  Return_Val=""

  if [ -z "$2" ]
  then
	local opts=( yes no )
	local opt_list="yes/no"
  else
	local opts=( `echo $2 | tr "/" " "` )
	local opt_list=$2
  fi
  
  while [ "$correct" != "yes" ]
  do
        if [ -n "$3" ]
        then
			line_prompt="$1 [default: $3] "
        else
			line_prompt="$1 [$opt_list] "
		fi
        
        echo -en "$line_prompt"
        read answer
		
		if [ -n "$3" ]
		then
			answer=$3
		else
			ret=`echo "${opts[@]}" | grep -w "$answer"`
		fi
		
		if [ $? -eq 0 ]
		then
			correct="yes"
			Return_Val=$answer
		fi
  done
}

function start_spinner()
{
  parent_pid=$$
  (SP_STRING="/-\\|"; while [ -d /proc/$1 ] && [ -d /proc/$parent_pid ]; do printf "\e[1;37m\e7[ %1.1s ]  \e8\e[0m" "$SP_STRING"; sleep .2; SP_STRING=${SP_STRING#"${SP_STRING%?}"}${SP_STRING%?}; done) &
  disown
  spinner_pid=$!
}

function stop_spinner()
{
   if [ $spinner_pid -gt 0 ]
   then
    kill -HUP $spinner_pid 2>/dev/null
   fi
}

function exec_command()
{
   printf "%-40s" "$2"
   
   (eval $1 >/dev/null 2>&1) &
   pid=$!
   
   start_spinner
   wait $pid
   status=$?
   stop_spinner
   
   if [ $status -eq 0 ];
   then
       echo -e "\e[1;37m[ \e[0m\e[1;32mok\e[0m\e[1;37m ]\e[0m"
   else
       echo -e "\e[1;37m[ \e[0m\e[1;31mfailed\e[0m\e[1;37m ]\e[0m"
	   echo -e "The following command did not complete successfully:"
	   echo -e "$1"
	   exit 1
   fi
}

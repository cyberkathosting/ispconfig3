#!/bin/bash
#### Config ################################

DBHOST="localhost"
DBUSER="powerdns"
DBPASS="password"
DATABASE="powerdns"

DEBUG="no"

#### End of Config #########################

REQUIRED_COMMANDS="
mysql
host
grep
awk
tail
"

# print debug messages to STDERR
function debug {
        if [ "${DEBUG}" == "yes" ] ; then
                echo "DEBUG: $@" >&2
        fi
}

for CMD in ${REQUIRED_COMMANDS} ; do
        CMDNAME=`echo ${CMD} | awk '{print toupper($1) }' | sed -e s@"-"@""@g`
        export $(eval "echo ${CMDNAME}")=`which ${CMD} 2>/dev/null`
        if [ -z "${!CMDNAME}" ] ; then
                debug "Command: ${CMD} not found!"
                exit 1
        else
                debug "Found command $(echo $CMDNAME) in ${!CMDNAME}"
        fi
done

MYSQLCMD="${MYSQL} -h ${DBHOST} -u ${DBUSER} -p${DBPASS} --skip-column-name --silent -e"

check() {
	AUTH=`${HOST} -t SOA ${2} ${1} | ${TAIL} -n1 | ${GREP} "has no SOA record"`
	if [ "${AUTH}" == "${2} has no SOA record" ]; then
		debug "Server ${1} has no SOA for ${2} - removing zone..."
		DOMAIN_ID=`${MYSQLCMD} "USE ${DATABASE}; SELECT id FROM domains WHERE name='${2}' AND type='SLAVE' AND master='${1}' LIMIT 1;"`
		${MYSQLCMD} "USE ${DATABASE}; DELETE FROM records WHERE domain_id='${DOMAIN_ID}';"
		${MYSQLCMD} "USE ${DATABASE}; DELETE FROM domains WHERE id='${DOMAIN_ID}';"
	fi
}

MASTERS=(`${MYSQLCMD} "USE ${DATABASE}; SELECT DISTINCT ip FROM supermasters;"`)
for m in "${MASTERS[@]}"; do
	NAMES=(`${MYSQLCMD} "USE ${DATABASE}; SELECT name FROM domains WHERE type = 'SLAVE' AND master = '${m}';"`)
	for d in "${NAMES[@]}"; do
		check ${m} ${d}
	done
done

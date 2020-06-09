#!/bin/bash

IFS=$'\n'
EX=0
ERRS="" ;
WARNS="" ;
ERRCNT=0 ;
WARNCNT=0 ;

CMD="find . -type f \( -name \"*.php\" -o -name \"*.lng\" \) -print" ;

if [[ "$1" == "commit" ]] ; then
	CMD="git diff-tree --no-commit-id --name-only -r ${CI_COMMIT_SHA} | grep -E '\.(php|lng)$'" ;
fi

for F in $(eval "$CMD") ; do
	if [[ ! -e "${F}" && -f "${F}" ]] ; then
		continue ;
	fi
	echo -n "${F} ... " ;
	R=$(php -d error_reporting=E_ALL -d display_errors=On -l "$F" 2>/dev/null) ;
	RET=$? ;
	R=$(echo "${R}" | sed "/^$/d")
	if [ $RET -gt 0 ] ; then
		EX=1 ;
		echo "[ERROR]" ;
		ERRS="${ERRS}${F}:"$'\n'"${R}"$'\n\n' ;
		ERRCNT=$((ERRCNT + 1)) ;
	else
		if [[ "$R" == "Deprecated: "* ]] ; then
			echo "[WARN]" ;
			WARNS="${WARNS}${F}:"$'\n'"${R}"$'\n\n' ;
			WARNCNT=$((WARNCNT + 1)) ;
		else 
			echo "[OK]" ;
		fi
	fi
done

echo ""
echo "--------------------------";
echo ""
echo "${ERRCNT} Errors"
if [ $ERRCNT -gt 0 ] ; then
	echo "${ERRS}"
	echo ""
fi

echo "${WARNCNT} Warnings"
if [ $WARNCNT -gt 0 ] ; then
	echo ""
	echo "${WARNS}"
	echo ""
fi

exit $EX
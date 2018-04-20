#!/bin/bash

set -e

remote_user=test
remote_password=apipassword
remote_url='https://yourserver.com:8080/remote/json.php'

# restCall method data
restCall() {
    curl -sS -X POST -H "Content-Type: application/json" -H "Cache-Control: no-cache" -d "${2}" "${remote_url}?${1}"
}

# Log in
session_id=`restCall login "{\"username\": \"${remote_user}\",\"password\": \"${remote_password}\"}" | jq -r '.response'`
if [[ $isession == "false" ]]; then
    echo "Login failed!"
    exit 1
#else
    #echo "Logged in. Session is: $session_id"
fi

restCall client_get "{\"session_id\": \"$session_id\",\"client_id\":{\"username\": \"abcde\"}}"

# or by id
restCall client_get "{\"session_id\": \"$session_id\",\"client_id\": \"2\"}"

# or all
restCall client_get "{\"session_id\": \"$session_id\",\"client_id\":{}}"

# Log out
if [[ `restCall logout "{\"session_id\": \"$session_id\"}" |jq -r .response` == "true" ]]; then
    #echo "Logout successful."
    exit 0
else
    echo "Logout failed!"
    exit 1
fi

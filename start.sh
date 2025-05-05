#!/bin/bash
DIR="$(cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd)"
cd "$DIR"

# Automatically restart the server
DO_LOOP="no"

while getopts "p:f:l" OPTION 2> /dev/null; do
	case ${OPTION} in
		p)

@@ -26,10 +23,11 @@ if [ "$PHP_BINARY" == "" ]; then
	if [ -f ./bin/php7/bin/php ]; then
		export PHPRC=""
		PHP_BINARY="./bin/php7/bin/php"
	elif [[ ! -z $(type php) ]]; then
		PHP_BINARY=$(type -p php)
	else
		echo "Couldn't find a working PHP7 binary, please use the installer."

		exit 1
	fi
fi

@@ -37,10 +35,9 @@ fi
if [ "$POCKETMINE_FILE" == "" ]; then
	if [ -f ./PocketMine-MP.phar ]; then
		POCKETMINE_FILE="./PocketMine-MP.phar"
	elif [ -f ./src/pocketmine/PocketMine.php ]; then
		POCKETMINE_FILE="./src/pocketmine/PocketMine.php"
	else
		echo "Couldn't find a valid Submarine installation"

		exit 1
	fi
fi

@@ -48,19 +45,18 @@ fi
LOOPS=0

set +e
while [ "$LOOPS" -eq 0 ] || [ "$DO_LOOP" == "yes" ]; do
	if [ "$DO_LOOP" == "yes" ]; then
		"$PHP_BINARY" "$POCKETMINE_FILE" $@
	else
		exec "$PHP_BINARY" "$POCKETMINE_FILE" $@
	fi
	if [ "$DO_LOOP" == "yes" ]; then
		if [ ${LOOPS} -gt 0 ]; then
			echo "Restarted $LOOPS times"
		fi
		echo "To escape the loop, press CTRL+C now. Otherwise, wait 2 seconds for the server to restart."

		echo ""
		sleep 2
		((LOOPS++))
	fi
done

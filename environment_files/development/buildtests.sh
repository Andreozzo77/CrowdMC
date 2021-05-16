#!/bin/bash

if [ $1 = "stop" ]; then
	echo "Stopping buildtests..."
	/usr/bin/tmux kill-session -t buildtests >> /dev/null
	/bin/kill -9 $(/bin/cat /root/test2/server.lock) >> /dev/null
	exit 0
fi
if [ $1 = "start" ]; then
	echo "Starting buildtests..."
	/usr/bin/tmux new-session -d -s buildtests '/root/test2/start.sh ; bash' >> /dev/null
	exit 0
fi
if [ $1 = "restart" ]; then
	echo "Restarting buildtests..."
	/usr/bin/tmux kill-session -t buildtests >> /dev/null
	/bin/kill -9 $(/bin/cat /root/test2/server.lock) >> /dev/null
	/usr/bin/tmux new-session -d -s buildtests '/root/test2/start.sh ; bash' >> /dev/null
	exit 0
fi
echo "stop/start/restart\n"
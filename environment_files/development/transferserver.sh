#!/bin/bash

if [ $1 = "stop" ]; then
	echo "Stopping transferserver..."
	/usr/bin/tmux kill-session -t transferserver >> /dev/null
	/bin/kill -9 $(/bin/cat /home/TransferServer/server.lock) >> /dev/null
	exit 0
fi
if [ $1 = "start" ]; then
	echo "Starting transferserver..."
	/usr/bin/tmux new-session -d -s transferserver '/home/TransferServer/start.sh ; bash' >> /dev/null
	exit 0
fi
if [ $1 = "restart" ]; then
	echo "Restarting transferserver..."
	/usr/bin/tmux kill-session -t transferserver >> /dev/null
	/bin/kill -9 $(/bin/cat /home/TransferServer/server.lock) >> /dev/null
	/usr/bin/tmux new-session -d -s transferserver '/home/TransferServer/start.sh ; bash' >> /dev/null
	exit 0
fi
echo "stop/start/restart\n"
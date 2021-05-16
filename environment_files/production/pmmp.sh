#!/bin/bash

if [ $1 = "stop" ]; then
	echo "Stopping pmmp..."
	/usr/bin/tmux kill-session -t pmmp >> /dev/null
	/bin/kill -9 $(/bin/cat /home/elitestar/server.lock) >> /dev/null
	exit 0
fi
if [ $1 = "start" ]; then
	echo "Starting pmmp..."
	/usr/bin/tmux new-session -d -s pmmp '/home/elitestar/start.sh ; bash' >> /dev/null
	exit 0
fi
if [ $1 = "restart" ]; then
	echo "Restarting pmmp..."
	/usr/bin/tmux kill-session -t pmmp >> /dev/null
	/bin/kill -9 $(/bin/cat /home/elitestar/server.lock) >> /dev/null
	/usr/bin/tmux new-session -d -s pmmp '/home/elitestar/start.sh ; bash' >> /dev/null
	exit 0
fi
echo "stop/start/restart\n"
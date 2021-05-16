#!/bin/bash

if [ $1 = "stop" ]; then
	echo "Stopping bot..."
	/usr/bin/tmux kill-session -t bot >> /dev/null
	exit 0
fi
if [ $1 = "start" ]; then
	echo "Starting bot..."
	/usr/bin/tmux new-session -d -s bot '/usr/bin/php /home/bot/bot.php ; bash' >> /dev/null
	exit 0
fi
if [ $1 = "restart" ]; then
	echo "Restarting bot..."
	/usr/bin/tmux kill-session -t bot >> /dev/null
	/usr/bin/tmux new-session -d -s bot '/usr/bin/php /home/bot/bot.php ; bash' >> /dev/null
	exit 0
fi
echo "stop/start/restart\n"
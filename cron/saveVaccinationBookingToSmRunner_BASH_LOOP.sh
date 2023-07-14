#!/bin/sh
while :
do
        php /usr/local/www/apache24/data/projects/piearsta2015/cron/saveVaccinationBookingToSmCronjob.php 1
	sleep 1
done

# Run me like this
# nohup /projects/projects/piearsta2015/cron/saveVaccinationBookingToSmRunner_BASH_LOOP.sh > /dev/null 2>&1 &

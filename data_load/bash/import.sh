#!/bin/bash
month=$(date +"%Y%m")
yesterday=$(date --date="1 day ago" +"%Y%m%d")
pyfile="import_exchange_tracking.py"
csvdir="//data/exchange_tracking/csv/"
couchurl="http://couch.url:5984"
dbname="email_tracking_"$month
logdir="//data/exchange_tracking/log/"
logname="import_$yesterday.log"
logfile=$logdir$logname
processeddir="//data/exchange_tracking/processed/"
csvcount=`ls -1 $csvdir | wc -l`
if [ $csvcount -gt 0 ]; then
python $pyfile $csvdir $couchurl $dbname > $logfile
mv $csvdir*.csv $processeddir
fi
#Called from CouchDB Server -- Rebuilds the View nightly after new data is added
#Determine Current Year/Month
echo $(date)" Process Started."
month=$(date +"%Y%m")
#Check if View Exists
viewoutput=$(curl localhost:5984/email_tracking_$month/_design/email_tracking/_view/contact_emails_intervals)
found=`echo $viewoutput | grep -c not_found`
if [ $found -ne 0 ]; then
#create view if it does not exist
cd /usr/local/bin/
curl -X PUT http://localhost:5984/email_tracking_$month/_design/email_tracking --data-binary @contact_emails_intervals.json
fi
#refresh view
curl http://localhost:5984/email_tracking_$month/_design/email_tracking/_view/contact_emails_intervals
echo $(date)" Process Finished."
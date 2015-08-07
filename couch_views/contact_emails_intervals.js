function(doc) {
    if (    (doc.totalbytes > 0) &&
            (doc.senderaddress.length > 0) &&
            (doc.recipientaddress.length > 0) &&
            (doc.returnpath != "<>") &&
            (doc.messagesubject.toString().toLowerCase().indexOf('automatic reply') != 0) &&
            (doc.messagesubject.toString().toLowerCase().indexOf('out of office') != 0) ) { 
        var recipients = doc.recipientaddress.replace(/'/g,"").split(";");
        var t = doc.datetime.split('T')[1].replace('Z','').split(':');
        var th = t[0];
        var tm = t[1];
        var ts = th + "00";
        if (parseInt(tm) >= 30) {
            ts = th + "30"; 
        }
        //By tracking when a person sends an email we may be able to infer a good time to send them a message
        //If a person sends most of their emails at 1200 then sending them emails at that window might be best
        for (var i = 0; i < recipients.length; i++) {
            if (recipients[i].toLowerCase() != '') {
                emit(["rcvd", recipients[i].toLowerCase(), ts], null);
            }
        }
        emit(["send", doc.senderaddress.toLowerCase(), ts], null);
    }
}
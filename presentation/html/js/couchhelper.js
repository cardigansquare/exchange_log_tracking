var s_options = '';
function selectInstitution() {
    var s_id = document.getElementById('selInstitution').options[document.getElementById('selInstitution').selectedIndex].value;
    var s_text = document.getElementById('selInstitution').options[document.getElementById('selInstitution').selectedIndex].text;
    if (s_text == "Load more...") {
        var xhr = new XMLHttpRequest();
        s_id = document.getElementById('selInstitution').options[document.getElementById('selInstitution').selectedIndex - 1].value
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if (xhr.responseText != 0) {
                    s_options = "<options></options>" + s_options + xhr.responseText + "<option value='-1'>Load more...</option>";
                    document.getElementById('selInstitution').innerHTML = s_options;
                }
            }
        }
        //remove "" option
        if (document.getElementById('selInstitution').options[0].text == '') {
            document.getElementById('selInstitution').remove(0);
        }
        //remove "Load more..." option
        document.getElementById('selInstitution').remove(document.getElementById('selInstitution').length - 1);
        s_options = document.getElementById('selInstitution').innerHTML;
        document.getElementById('selInstitution').innerHTML = "<option>Loading... Please wait...</option>";
        xhr.open("GET", "http://etl1.advisory.com/dev/loadmoreinstitutions.php?id=" + s_id, true);
        xhr.send();
    }
    else if (s_id != '') {
        document.getElementById('content').src = 'http://etl1.advisory.com/dev/institution_contacts.php?id=' + s_id;
    }
}

function selectMemberTimezone() {
    var s_id = document.getElementById('selInstitution').options[document.getElementById('selInstitution').selectedIndex].value;
    if (s_id != '') {
        document.getElementById('content').src = 'http://etl1.advisory.com/dev/' + s_id + '.htm';
    }
}

function loadMoreContacts() {
    var s_id = document.getElementById('hdnId').value;
    var s_docid = document.getElementById('hdnDocId').value;
    var s_load = document.getElementById('aLoad').innerText;
    if (s_load == "Load more...") {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                if (xhr.responseText != 0) {
                    var obj = JSON.parse(xhr.responseText);
                    document.getElementById('tblLoader').innerHTML = "";
                    for (var key in obj) {
                        document.getElementById('tblLoader').innerHTML = obj[key];
                        document.getElementById('hdnDocId').value = key;
                    }
                    //todo cycle through tblloader rows and push to tblcontacts
                    var tblContact = document.getElementById('tblContacts');
                    var table = document.getElementById('tblLoader');
                    for (var i = 0, row; row = table.rows[i]; i++) {
                        var i_insert_index = tblContact.rows.length;
                        var newRow = tblContact.insertRow(i_insert_index);
                        newRow.outerHTML = row.outerHTML;
                    }
                    var scripts = d3.select(table).selectAll('script')[0];
                    var code = "";
                    for (script in scripts) {
                        if (scripts[script].nodeName.toLowerCase() == "script") {
                            code += scripts[script].innerText;
                        }
                    }
                    var elScript = document.createElement('script');
                    elScript.type = 'text/javascript';
                    elScript.text = code;
                    tblContact.appendChild(elScript);
                    document.getElementById('aLoad').innerText = "Load more...";
                }
            }
        }
        document.getElementById('aLoad').innerText = "Loading...";
        xhr.open("GET", "http://etl1.advisory.com/dev/institution_contacts.php?id=" + s_id + "&did=" + s_docid, true);
        xhr.send();
    }
}

function buildEmailSendRate(link, sjson, stype) {
    var values = JSON.parse(sjson);
    link = document.getElementById(link);
    var data = values;
    var barWidth = 28;
    var width = (barWidth + 10) * data.length;
    var height = 200;
    var padding = 30;
    var barColor = "#193a49";//"#e4e4e4";
//    var barText = "#393636";
    var barText = "white";
    if (stype == "phone") {
        barColor = "#393636";
//        barText = "#e4e4e4";
    }
    var x = d3.scale.linear().domain([0, data.length]).range([0, width]);
    var y = d3.scale.linear().domain([0, d3.max(data, function(datum) { return datum.count; })]).rangeRound([0, height]);

    // add the canvas to the DOM
    //var barDemo = d3.select(link.parentElement).
    var barDemo = d3.select(link).
        append("svg:svg").
        attr("width", width).
        attr("height", height + padding);

    barDemo.selectAll("rect").
        data(data).
        enter().
        append("svg:rect").
        attr("x", function(datum, index) { return x(index); }).
        attr("y", function(datum) { return height - y(datum.count); }).
        attr("height", function(datum) { return y(datum.count); }).
        attr("width", barWidth).
        attr("fill", barColor);
    barDemo.selectAll("text").
        data(data).
        enter().
        append("svg:text").
        attr("x", function(datum, index) { return x(index) + barWidth; }).
        attr("y", function(datum) { return height - y(datum.count); }).
        attr("dx", -barWidth/2).
        attr("dy", "1.2em").
        attr("text-anchor", "middle").
        text(function(datum) { return datum.count;}).
        attr("fill", barText);
    barDemo.selectAll("text.yAxis").
        data(data).
        enter().append("svg:text").
        attr("x", function(datum, index) { return x(index) + barWidth; }).
        attr("y", height).
        attr("dx", -barWidth/2).
        attr("text-anchor", "middle").
        attr("style", "font-size: 12; font-family: Helvetica, sans-serif").
        text(function(datum) { return datum.time;}).
        attr("transform", "translate(0, 18)").
        attr("class", "yAxis");
}

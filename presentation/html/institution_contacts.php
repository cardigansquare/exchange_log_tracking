<?php
require_once "css.php";
date_default_timezone_set('America/New_York');
$s_url_root = 'http://couchdb.url:5984/';
$s_db = "sfrep_small";
$accountid = '';
$s_html = '';
if (isset($_GET["id"])) {
    $accountid = $_GET["id"];
}
if ($accountid == '') {
    print($s_css);
    print("<table><tr><td>Please select an Institution.</td></tr></table>");
    exit();
}
$s_startkey_type = '0';
$s_startkey_docid = '';
if (isset($_GET["did"])) {
    $s_startkey_docid = $_GET["did"];
    $s_startkey_type = '1';
}
$s_url_suffix = '';
//Account
$s_html_account = '';
if ($s_startkey_docid == "") {
    $s_html .= "<table>";
    $s_html .= "<tr><th class='thProjects' colspan=2>Institution</th></tr>";
    $s_html .= "<tr><th>Name</th><th>Phone</th></tr>";
}
else { 
    $s_url_suffix = "&startkey_docid=" . $s_startkey_docid . "&skip=1";
}
$s_url = $s_url_root . $s_db . "/_design/sfrep/_view/account_contacts?reduce=false&startkey=[%22" . $accountid . "%22," . $s_startkey_type . "]&limit=50" . $s_url_suffix;
$result = file_get_contents($s_url);
$result = json_decode($result, true);
$accountphone = '';
foreach ($result['rows'] as $row) {
    if (isset($row['value']) && isset($row['value']['type']) && $row['value']['type'] == 'account') {
        $account = $row['value'];
        $accountphone = $account['phone'];
        if ($s_startkey_docid == "") {
            $s_html_account .= '<tr><td>' . $account['name'] . '</td><td>' . $account['phone'] . '</td></tr>';
        }
        break 1;
    }
}
if ($s_startkey_docid == "") {
    $s_html .= $s_html_account;
    $s_html .= "</table>";
    $s_html .= "<br/>";
}
//Contact
$i_real_contact = 0;
if ($s_startkey_docid == "") {
    $s_html .= "<table id=\"tblContacts\" style='border:none;'>";
    $s_html .= "<tr><th class='thProjects' colspan=6>Contacts</th></tr>";
    $s_html .= "<tr>";
    $s_html .= "<th>Name</th><th>Email</th><th>Phone</th>";
}
$s_html_contact = '';
$docid = '';
foreach ($result['rows'] as $row) {
    if (isset($row['value']) && isset($row['value']['type']) && $row['value']['type'] == 'contact') {
        if ($i_real_contact == 5) {
            break;
        }
        $contact = $row['value'];
        $firstname = $contact['firstname'];
        $lastname = $contact['lastname'];
        $email = $contact['email'];
        $phone = $contact['phone'];
        $docid = $contact['_id'];
        if (strtolower($firstname) == "null") $firstname = '';
        if (strtolower($lastname) == "null") $lastname = '';
        if (strtolower($email) == "null") $email = '';
        if (strtolower($phone) == "null") $phone = '';
        $a_years = array(2014);
        foreach ($a_years as $i_year) {
            $a_email_hourly_data_sent = array();
            $a_email_monthly_data_sent = array();
            $a_email_hourly_data_rcvd = array();
            $a_email_monthly_data_rcvd = array();
            $i_sent = 0;
            $i_sent_hourly = 0;
            $i_received = 0;
            $i_rcvd_hourly = 0;
            for ($i_month = 1; $i_month < 13; $i_month++) {
                $s_db_suffix = strval($i_year) . str_pad(strval($i_month), 2, '0', STR_PAD_LEFT);
                $s_url_db = "email_tracking_" . $s_db_suffix;
                $s_url = $s_url_root . $s_url_db;
                $headers = get_headers($s_url);
                if (substr($headers[0], 9, 3) != '404') {
                    //Emails Sent to ABC by Contact
                    $s_html_hourly_emails_sent = '';
                    $s_url = $s_url_root . $s_url_db . "/_design/email_tracking/_view/contact_emails_intervals?startkey=%5B%22send%22%2C%22" . $email . "%22%5D&endkey=%5B%22send%22%2C%22" . $email . "%22%2C%7B%7D%5D&group=true&group_level=2";
                    $resultsentcount = file_get_contents($s_url);
                    $resultsentcount = json_decode($resultsentcount, true);
                    foreach ($resultsentcount['rows'] as $rowsentcount) {
                        if (isset($rowsentcount['value'])) {
                            $i_sent += $rowsentcount['value'];
                        }
                    }
                    if ($i_sent > 0) {
                        $s_url = $s_url_root . $s_url_db . "/_design/email_tracking/_view/contact_emails_intervals?reduce=false&startkey=%5B%22send%22%2C%22" . $email . "%22%5D&endkey=%5B%22send%22%2C%22" . $email . "%22%2C%7B%7D%5D";
                        $resultsentemails = file_get_contents($s_url);
                        $resultsentemails = json_decode($resultsentemails, true);
                        buildContactEmailCounts($resultsentemails, 'datetime', $a_email_hourly_data_sent, $a_email_monthly_data_sent, $i_month);
                    }

                    //Emails Received from Contact
                    $s_html_hourly_emails_rcvd = '';
                    $s_url = $s_url_root . $s_url_db . "/_design/email_tracking/_view/contact_emails_intervals?startkey=%5B%22rcvd%22%2C%22" . $email . "%22%5D&endkey=%5B%22rcvd%22%2C%22" . $email . "%22%2C%7B%7D%5D&group=true&group_level=2";
                    $resultreceivedcount = file_get_contents($s_url);
                    $resultreceivedcount = json_decode($resultreceivedcount, true);
                    foreach ($resultreceivedcount['rows'] as $rowreceivedcount) {
                        if (isset($rowreceivedcount['value'])) {
                            $i_received += $rowreceivedcount['value'];
                        }
                    }
                    if ($i_received > 0) {
                        $s_url = $s_url_root . $s_url_db . "/_design/email_tracking/_view/contact_emails_intervals?reduce=false&startkey=%5B%22rcvd%22%2C%22" . $email . "%22%5D&endkey=%5B%22rcvd%22%2C%22" . $email . "%22%2C%7B%7D%5D";
                        $resultrcvdemails = file_get_contents($s_url);
                        $resultrcvdemails = json_decode($resultrcvdemails, true);
                        buildContactEmailCounts($resultrcvdemails, 'datetime', $a_email_hourly_data_rcvd, $a_email_monthly_data_rcvd, $i_month);
                    }
                }
            }
            foreach ($a_email_hourly_data_sent as $hour=>$value) {
                if (($hour > 600) && ($hour < 2000)) {
                    $i_sent_hourly += $value;
                }
            }
            foreach ($a_email_hourly_data_rcvd as $hour=>$value) {
                if (($hour > 600) && ($hour < 2000)) {
                    $i_rcvd_hourly += $value;
                }
            }
            if ($i_sent_hourly > 0) {
                $s_html_hourly_emails_sent = buildContactEmailHtmlHourly('email', 'Member', $a_email_hourly_data_sent);
            }
            $s_html_monthly_emails_sent  = buildContactEmailHtmlMonthly('Member', $a_email_monthly_data_sent, $i_sent);
            if ($i_rcvd_hourly > 0) {
                $s_html_hourly_emails_rcvd = buildContactEmailHtmlHourly('email', 'ABC', $a_email_hourly_data_rcvd);
            }
            $s_html_monthly_emails_rcvd  = buildContactEmailHtmlMonthly('ABC', $a_email_monthly_data_rcvd, $i_received);
            //Email Effectiveness
            //TODO: revisit effectiveness logic
            //Sent and Received == then no email effect on relationship
            $d_email_effectiveness = 0;
            if (($i_received + $i_sent) == 0) {
                $d_email_effectiveness = 0;
            }
            else if (($i_received == 0) && ($i_sent > 0)) {
                #if customer is sending us emails and we're not sending them any email effectiveness is 100
                $d_email_effectiveness = 100;
            }
            else {
                #Else how many emails the customer sent us versus how many they received is effectiveness rating
                $d_email_effectiveness = round((($i_sent/$i_received)*100),2);
            }
            if ($d_email_effectiveness == 0) {
                $d_email_effectiveness = "0";
            }
            else {
                $d_email_effectiveness = strval($d_email_effectiveness) . '%';
            }

            //Contact Info
            if (($i_received > 0) || ($i_sent > 0)) {
                $s_html_contact .= '<tr><td>' . $firstname . ' ' . $lastname  . '</td><td>' . $email . '</td><td>' . $phone . '</td></tr>';
            }
            //Email Stats
            if (($i_received > 0) || ($i_sent > 0)) {
                $i_real_contact += 1;
                $s_html_contact .= '<tr style="border:0;">';
                $s_html_contact .= '<td colspan=3 class="tdStats">';
                $s_html_contact .= '<table style="margin-bottom:5px;width:100%;">';
                $s_html_contact .= '<tr>';
                $s_html_contact .= '<td colspan=29>Email Stats - ' . strval($i_year) . '</td>';
                $s_html_contact .= '</tr>';
                $s_html_contact .= buildMonthlyHeader();
                $s_html_contact .= $s_html_monthly_emails_rcvd;
                $s_html_contact .= $s_html_monthly_emails_sent;
                $s_html_contact .= '<tr>';
                $s_html_contact .= '<td colspan=3>Email Effectiveness:</td>';
                $s_html_contact .= '<td colspan=24></td>';
                $s_html_contact .= '<td colspan=2 class="tdNum">' . $d_email_effectiveness  . '</td>';
                $s_html_contact .= '</tr>';
                $s_html_contact .= '<tr><td colspan=29 style="height:2px"></td></tr>';
                $s_html_contact .= $s_html_hourly_emails_rcvd;
                $s_html_contact .= $s_html_hourly_emails_sent;
                $s_html_contact .= '</table>';
                $s_html_contact .= '</td>';
                $s_html_contact .= '</tr>';
            }
        }
    }
}
$s_html .= $s_html_contact;
//TODO: Build all HTML on Client Side :D
if ($s_startkey_docid == "") {
    $s_html  .= "</table>";
    $s_html .= "<input type=\"hidden\" id=\"hdnId\" value=\"" . $accountid . "\"></input>";
    $s_html .= "<input type=\"hidden\" id=\"hdnDocId\" value=\"" . $docid . "\"></input>";
    $s_html .= "<a id=\"aLoad\" href=\"javascript:\" onclick=\"loadMoreContacts();\">Load more...</a>";
    $s_html .= "<table id=\"tblLoader\" style=\"display:none;\"></table>";
    $s_js    = "<script type=\"text/javascript\" src=\"http://d3js.org/d3.v2.min.js?2.10.0\"></script>";
    $s_js   .= "<script type=\"text/javascript\" src='js/couchhelper.js'></script>"; 
    print($s_css);
    print($s_js);
    print($s_html);
//    print($s_js);
}
else {
    $a_html = array();
    $a_html[$docid] = $s_html;
    $j_html = json_encode($a_html);
    echo $j_html;
}

function buildMonthlyHeader() {
    $s_html_monthly_header  = '<tr>';
    $s_html_monthly_header .= '<td colspan=3>&nbsp;</td>';
    $s_html_monthly_header .= '<td colspan=2>Jan</td><td colspan=2>Feb</td><td colspan=2>Mar</td><td colspan=2>Apr</td><td colspan=2>May</td>';
    $s_html_monthly_header .= '<td colspan=2>Jun</td><td colspan=2>Jul</td><td colspan=2>Aug</td><td colspan=2>Sep</td><td colspan=2>Oct</td>';
    $s_html_monthly_header .= '<td colspan=2>Nov</td><td colspan=2>Dec</td><td colspan=2>Total</td>';
    $s_html_monthly_header .= '</tr>';
    return $s_html_monthly_header;
}

function buildHourlyHeader() {
    $s_html_hourly_header  = '<tr>';
    $s_html_hourly_header .= '<td>&nbsp;</td>';
    $s_html_hourly_header .= '<td colspan=2>06:00AM</td><td colspan=2>07:00AM</td><td colspan=2>08:00AM</td><td colspan=2>09:00AM</td><td colspan=2>10:00AM</td>';
    $s_html_hourly_header .= '<td colspan=2>11:00AM</td><td colspan=2>12:00PM</td><td colspan=2>01:00PM</td><td colspan=2>02:00PM</td><td colspan=2>03:00PM</td>';
    $s_html_hourly_header .= '<td colspan=2>04:00PM</td><td colspan=2>05:00PM</td><td colspan=2>06:00PM</td><td colspan=2>07:00PM</td>';
    $s_html_hourly_header .= '</tr>';
    return $s_html_hourly_header;
}

function buildContactEmailCounts($resultemails, $fieldname, &$a_email_hourly_data, &$a_email_monthly_data, $i_cur_mon) {
    foreach ($resultemails['rows'] as $rowemail) {
        //hourly numbers
        $i_array_index = $rowemail["key"][2];
        $a_email_hourly_data[$i_array_index] = (ISSET($a_email_hourly_data[$i_array_index]) ? $a_email_hourly_data[$i_array_index] : 0) + 1;
        //monthly numbers
        $a_email_monthly_data[$i_cur_mon] = (ISSET($a_email_monthly_data[$i_cur_mon]) ? $a_email_monthly_data[$i_cur_mon] : 0) + 1;
    }
}

function buildContactEmailHtmlHourly($s_type, $s_sender, $a_email_stats) {
    $s_verb = "Sent";
    if ($s_type == "phone") {
        $s_verb = "Made";
    }
    $a_hours = array(600,630,700,730,800,830,900,930,1000,1030,1100,1130,1200,1230,1300,1330,1400,1430,1500,1530,1600,1630,1700,1730,1800,1830,1900,1930);
    $a_histo = array();
    $s_html_hourly_emails  = '<tr>';
    $s_html_hourly_emails .= '<td>' . $s_verb . ' By ' . $s_sender . ':</td>';
    $s_html_hourly_emails .= '<td colspan="28"></td>';
    foreach ($a_hours as $a_hour) {
        $i_hour = (isset($a_email_stats[$a_hour]) ? $a_email_stats[$a_hour] : 0);
        $a_inner_histo = array();
        $s_temp_time = str_pad(strval($a_hour), 4, '0', STR_PAD_LEFT);
        $s_temp_time = substr($s_temp_time, 0, 2) . ":" . substr($s_temp_time, -2);
        $a_inner_histo['time'] = $s_temp_time;
        $a_inner_histo['count'] = $i_hour;
        $a_histo[] = $a_inner_histo;
    }
    $s_histo = json_encode($a_histo);
    $s_histo_id = str_replace(" ", "", substr(strval(microtime()), -19));
    $s_html_hourly_emails .= '</tr>';
    $s_html_hourly_emails .= '<tr>';
    $s_html_hourly_emails .= '<td colspan="29" id="' . $s_histo_id . '">';
    $s_html_hourly_emails .= '<script type="text/javascript">buildEmailSendRate(\'' . $s_histo_id . '\',\'' . $s_histo .  '\', \'' . $s_type . '\');</script>';
    $s_html_hourly_emails .= '</td>';
    $s_html_hourly_emails .= '</tr>';
    return $s_html_hourly_emails;
}

function buildContactEmailHtmlMonthly($s_sender, $a_email_stats, $i_total) {
    $a_months = array(1,2,3,4,5,6,7,8,9,10,11,12);
    $s_html_monthly_emails  = '<tr>';
    $s_html_monthly_emails .= '<td colspan=3>Sent By ' . $s_sender . ':</td>';
    foreach ($a_months as $a_month) {
        $i_month = (isset($a_email_stats[$a_month]) ? $a_email_stats[$a_month] : 0);
        $s_html_monthly_emails  .= '<td colspan=2 class="tdNum">' . $i_month . '</td>';
    }
    $s_html_monthly_emails .= '<td colspan=2 class="tdNum">' . strval($i_total) . '</td>';
    $s_html_monthly_emails .= '</tr>';
    return $s_html_monthly_emails;
}
?>

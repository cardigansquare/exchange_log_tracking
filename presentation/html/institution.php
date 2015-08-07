<?php
require_once "css.php";
$s_url_root = "http://couchdb.url:5984/";
$s_db = "sfrep_small";
$accountid = '';
$s_html  = '';
//Select Account Dropdown
$s_url = $s_url_root . $s_db . "/_design/sfrep/_view/account_name_contacts_count?group=true&group_level=1&limit=201";
$result = file_get_contents($s_url);
$result = json_decode($result, true);
$s_html .= "<table>";
$s_html .= "<tr><td>Please select an Institution.</td></tr>";
$s_html .= "<tr><td style=\"font-size:small;\">(Note: All Data Points Between 2014/01 to 2014/12.)</td></tr>"; #Hardcoding years for demo purposes
$s_html .= "<tr><td>";
$s_html .= "<select id='selInstitution' name='selInstitution' onchange='selectInstitution()'>";
$inner_accountid = '';
foreach ($result['rows'] as $row) {
    if ($row['value'] > 2) {
        $inner_accountid = $row['key'][0];
        $s_url = $s_url_root . $s_db . "/_design/sfrep/_view/account_name_contacts_count?&reduce=false&startkey=[%22" . $inner_accountid . "%22,0]&endkey=[%22" . $inner_accountid . "%22,0]";
        $acct_result = file_get_contents($s_url);
        $acct_result = json_decode($acct_result, true);
        if (isset($acct_result['rows']) && isset($acct_result['rows'][0])) {
            $inner_accountname = $acct_result['rows'][0]['value'];
            $s_html .= "<option value=\"". $inner_accountid . "\"";
            if (($accountid > 0) && ($accountid == $inner_accountid)) {
                $s_html .= " selected";
            }
            if ($accountid == '') {
                $accountid = $inner_accountid;
            }
            $s_html .= ">" . $inner_accountname . "</option>";
        }
    }
}
$s_html .= "</select>";
$s_html .= "</td></tr>";
$s_html .= "</table>";
$s_html .= "<br/>";
$s_html .= "<iframe id='content' src='institution_contacts.php?id=" . $accountid . "' style='position: absolute;height: 87%;width:95%;border:none;'></iframe>"; #TODO: move to ajax -- iframes bad
$s_js    = "<script type=\"text/javascript\" src='js/couchhelper.js'></script>";
print($s_css);
echo $s_html;
print($s_js);
?>

<?php
$s_url_root = "http://localhost:5984/";
$s_db = "sfrep_small";
$accountid = '';
$s_html = '';
if (isset($_GET["id"])) {
    $accountid = $_GET["id"];
}
if ($accountid == '') {
    echo 0;
    exit();
}
$s_url = $s_url_root . $s_db . "/_design/sfrep/_view/account_name_contacts_count?descending=false&limit=201&startkey=%5B%22" . $accountid . "%22%5D&skip=1&group=true&group_level=1";
$s_html = '';
$result = file_get_contents($s_url);
$result = json_decode($result, true);
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
echo $s_html;
?>

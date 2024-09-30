<?php

$ID = $argv[1];
$email = $argv[2];
$hotel = $argv[3];
$pms = $argv[4];
$dbname = "admin_sites";

if ($pms == "OPERA") {
    function update($ip, $user, $pass, $ID, $email) {
        $db = '(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST =  ' . $ip . ')(PORT = 1521)))(CONNECT_DATA = (SID = OPERA)))';
        $conn = oci_connect($user, $pass, $db);
        if (!$conn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

        // Step #1: Select distinct PHONE_NUMBER
        $query = "SELECT DISTINCT PHONE_NUMBER FROM NAME_PHONE WHERE INACTIVE_DATE IS NULL AND PHONE_NUMBER LIKE UPPER('%$email%')";
        $results = oci_parse($conn, $query);
        oci_execute($results);
        $row = oci_fetch_array($results, OCI_ASSOC);
        $existingEmail = $row['PHONE_NUMBER'] ?? null;

        // Step #2: Delete Emails by Inactivation
        if ($existingEmail) {
            $deleteQuery = "UPDATE OPERA.NAME_PHONE SET INACTIVE_DATE = TO_DATE('2024-04-14 00:00:00', 'YYYY-MM-DD HH24:MI:SS') WHERE PHONE_NUMBER LIKE UPPER('%$email%')";
            $deleteStmt = oci_parse($conn, $deleteQuery);
            oci_execute($deleteStmt);
        }

        // Step #3: Check existence and add a new email
        $query = "SELECT count(NAME_ID) as TOTAL FROM NAME_PHONE WHERE NAME_ID = $ID AND PRIMARY_YN = 'Y' AND UPPER(PHONE_NUMBER) LIKE UPPER('%$email%')";
        $results = oci_parse($conn, $query);
        oci_execute($results);
        $row = oci_fetch_array($results, OCI_ASSOC);
        $count = $row['TOTAL'];

        // If the email does not exist, insert it
        if ($count == 0) {
            $insertQuery = "INSERT INTO NAME_PHONE (PHONE_ID, NAME_ID, PHONE_TYPE, PHONE_ROLE, PHONE_NUMBER, INSERT_DATE, INSERT_USER, UPDATE_DATE, UPDATE_USER, PRIMARY_YN, DISPLAY_SEQ, INDEX_PHONE, SHARE_EMAIL_YN, DEFAULT_CONFIRMATION_YN) 
            VALUES (NAME_PHONE_SEQNO.nextval, $ID, 'EMAIL', 'EMAIL', '$email', SYSDATE, '-1', SYSDATE, '-1', 'Y', '1', '$email', 'N', 'N')";
            $insertStmt = oci_parse($conn, $insertQuery);
            oci_execute($insertStmt);
        }

        oci_close($conn); // Close the connection
    }

    $servername = "localhost";
    $username = "admin_sites";
    $password = "M^zf323f6";

    // Create a connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // SQL query to retrieve the IP address for the specified hotel
    $query = "SELECT * FROM site WHERE hotel = '$hotel'";
    
    // Execute the query
    $result = $conn->query($query);

    // Fetch and display the results
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ip = $row["pms"];
            // Call the update function with the fetched IP
            update($ip, $username, $password, $ID, $email);
        }
    }

    $conn->close(); // Close the MySQL connection
}
?>

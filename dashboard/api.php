<?php

function respond($code, $response)
{
    header("Content-Type:application/json");
    http_response_code($code);
    echo(is_array($response) ? json_encode($response) : $response);
    exit(0);
}

function getDb()
{
    $con = mysqli_connect("localhost", "rookietoosmart", "lAunch0ut!", "partnerdb");
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit(0);
    }
    return $con;
}

if ($json = json_decode(file_get_contents("php://input"), true))
    $request = $json;
else if ($_POST)
    $request = $_POST;
else if ($_GET)
    $request = $_GET;
$log = strftime('%Y-%m-%d');
$time = strftime('%H:%M:%S');

try {
    $db = getDb();
    if (stripos($_SERVER['REQUEST_URI'], '/createPartner') !== false) {
        $PartnerID = $request['partnerId'];
        $businessName = $request['businessName'];
        $contactName = $request['contactName'];
        $contractType = $request['contractType'];
        $accessToken = $request['accessToken'];
        $createdDate = $request['createdDate'];
        $status = $request['status'];
        $assignedgames = $request['assignedgames'];
        if ($contractType == 'Bundle') {
            foreach ($assignedgames as $games) {
            $sql = "INSERT INTO partnerdb.Bundle(PartnerID, CategoryID)
            VALUES ('$PartnerID', '$games')";
            $db->query($sql);
            if ($db->errno)
                respond(500, array('success' => false, 'message' => 'db error: ' . $db->error));
            // respond(200, array('success' => true, 'message' => 'CategoryID successfully created'));
            }
        } else if ($contractType == 'Custom') {
            foreach ($assignedgames as $games) {
            $sql = "INSERT INTO partnerdb.Bundle(PartnerID, GameID)
            VALUES ('$PartnerID', '$assignedgames')";
            $db->query($sql);
                respond(500, array('success' => false, 'message' => 'db error: ' . $db->error));
            }
            // respond(200, array('success' => true, 'message' => 'GameID successfully created'));
        }
        $sql = "INSERT INTO partnerdb.Partner(PartnerID, BusinessName , ContactName, ContractType, PartnerAccessToken, CreatedDate, Status)
            VALUES ('$PartnerID', '$businessName','$contactName','$contractType','$accessToken', '$createdDate', '$status')";
        $db->query($sql);
        if ($db->errno)
            respond(500, array('success' => false, 'message' => 'db error: ' . $db->error));
        respond(200, array('success' => true, 'message' => 'Partner successfully created'));
        
    } else if (stripos($_SERVER['REQUEST_URI'], "/login") !== false) {
        $Username = $request['username'];
        $Password = $request['password'];
        $adminUsername = '9ijakids';
        $adminPassword = 'lAunch0ut!';
        if ($Username == $adminUsername && $Password == $adminPassword) {
            respond(200, array('success' => true, 'data' => 'Username && Password match'));
        } else
            respond(404, array('success' => false, 'error' => 'Incorrect Username or Password'));
    }
    // For the Active Date Summary Box
    else if (stripos($_SERVER['REQUEST_URI'], '/GetActiveDates') !== false) {
        $db = getDb();
        $sql = "SELECT DATE_FORMAT(Timestamp, '%Y-%m-%d') DateCreated FROM partnerdb.AccessLog;";
        $query = mysqli_query($db, $sql);
        $ActiveDate = [];

        if (mysqli_num_rows($query) > 0) {
            $pid = [];
            $getActiveDate = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $pid[] = $row[''];
                $getActiveDate[$row['']][] = $row['DateCreated'];
            }

            $pid = array_unique($pid);
            $ActiveDate = [];
            foreach ($pid as $key => $id) {
                $ActiveDate[] = $getActiveDate[$id];
            }
            respond(200, array('success' => true, 'data' => $ActiveDate));
        } else 
            respond(404, array('success' => false, 'error' => 'Subscription not found'));
    }
    // For the Subscription Date Summary Box
    else if (stripos($_SERVER['REQUEST_URI'], '/GetSubscriptionDate') !== false) {
        $db = getDb();
        $sql = "SELECT DATE_FORMAT(CreatedDate, '%Y-%m-%d') DateCreated FROM partnerdb.Subscription";
        $query = mysqli_query($db, $sql);
        $SubscriptionDate = [];

        if (mysqli_num_rows($query) > 0) {
            $pid = [];
            $getSubscriptionDate = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $pid[] = $row[''];
                $getSubscriptionDate[$row['']][] = $row['DateCreated'];
            }

            $pid = array_unique($pid);
            $SubscriptionDate = [];
            foreach ($pid as $key => $id) {
                $SubscriptionDate[] = $getSubscriptionDate[$id];
            }
            respond(200, array('success' => true, 'data' => $SubscriptionDate));
        } else 
            respond(404, array('success' => false, 'error' => 'Subscription not found'));
    }
        // For the GamesID and Game Name
        else if (stripos($_SERVER['REQUEST_URI'], '/GetGames') !== false) {
            $db = getDb();
            $sql = "SELECT acc.SubscriptionID, acc.GameID, gm.GameTitle FROM partnerdb.AccessLog acc LEFT JOIN partnerdb.Game gm on gm.GameID  = acc.GameID;";
            $query = mysqli_query($db, $sql);
            $SubscriptionDate = [];
    
            if (mysqli_num_rows($query) > 0) {
                $pid = [];
                $getSubscriptionDate = [];
                while ($row = mysqli_fetch_assoc($query)) {
                    $pid[] = $row['PartnerID'];
                    $getSubscriptionDate[$row['PartnerID']][] = $row;
                }
    
                $pid = array_unique($pid);
                $SubscriptionDate = [];
                foreach ($pid as $key => $id) {
                    $SubscriptionDate[] = $getSubscriptionDate[$id];
                }
                respond(200, array('success' => true, 'data' => $SubscriptionDate));
            } else 
                respond(404, array('success' => false, 'error' => 'Subscription not found'));
        }
    // For the UserPassport and User Created Date
    else if (stripos($_SERVER['REQUEST_URI'], '/GetUserPassport') !== false) {
        $db = getDb();
        $sql = "SELECT UserPassport, SubscriptionID, DATE_FORMAT(CreatedDate, '%Y-%m-%d') DateCreated FROM partnerdb.Subscription";
        $query = mysqli_query($db, $sql);
        $SubscriptionDate = [];

        if (mysqli_num_rows($query) > 0) {
            $pid = [];
            $getSubscriptionDate = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $pid[] = $row['PartnerID'];
                $getSubscriptionDate[$row['PartnerID']][] = $row;
            }

            $pid = array_unique($pid);
            $SubscriptionDate = [];
            foreach ($pid as $key => $id) {
                $SubscriptionDate[] = $getSubscriptionDate[$id];
            }
            respond(200, array('success' => true, 'data' => $SubscriptionDate));
        } else 
            respond(404, array('success' => false, 'error' => 'Subscription not found'));
    }
    // For the Subscription Date Summary Box
    else if (stripos($_SERVER['REQUEST_URI'], '/GetBusinessName') !== false) {
        $db = getDb();
        $sql = "SELECT BusinessName FROM partnerdb.Partner;";
        $query = mysqli_query($db, $sql);
        $BusinessName = [];

        if (mysqli_num_rows($query) > 0) {
            $pid = [];
            $getBusinessName = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $pid[] = $row[''];
                $getBusinessName[$row['']][] = $row['BusinessName'];
            }

            $pid = array_unique($pid);
            $BusinessName = [];
            foreach ($pid as $key => $id) {
                $BusinessName[] = $getBusinessName[$id];
            }
            respond(200, array('success' => true, 'data' => $BusinessName));
        } else 
            respond(404, array('success' => false, 'error' => 'Subscription not found'));
    }
    // To get total number of users
    else if (stripos($_SERVER['REQUEST_URI'], '/Subscription') !== false) {
        $db = getDb();
        $sql = "select SubscriptionID from Subscription";
        $query = mysqli_query($db, $sql);
        $Subscription = [];

        if (mysqli_num_rows($query) > 0) {
            $pid = [];
            $getSubscription = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $pid[] = $row['PartnerID'];
                $getSubscription[$row['PartnerID']][] = $row;
            }

            $pid = array_unique($pid);
            $Subscription = [];
            foreach ($pid as $key => $id) {
                $Subscription[] = $getSubscription[$id];
            }
            respond(200, array('success' => true, 'data' => $Subscription));
        } else
            respond(404, array('success' => false, 'error' => 'Subscription not found'));
    } 
    // Get the whole Subscription Table
    else if (stripos($_SERVER['REQUEST_URI'], "/GetAllSubscription") !== false) {
        $sql = "select * from Subscription";
        $query = mysqli_query($db, $sql);
        $result = [];
        if (mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_assoc($query)) {
                $result[] = $row;
            }
            respond(200, array('success' => true, 'data' => $result));
        } else
            respond(404, array('success' => false, 'error' => 'User not found'));
    } else if (stripos($_SERVER['REQUEST_URI'], "/GetGroup") !== false) {
        $sql = "select * from groupcategory";
        $query = mysqli_query($db, $sql);
        $result = [];
        if (mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_assoc($query)) {
                $result[] = $row;
            }
            respond(200, array('success' => true, 'data' => $result));
        } else
            respond(404, array('success' => false, 'error' => 'Groups not found'));
    } else if (stripos($_SERVER['REQUEST_URI'], "/GetLevel") !== false) {
        $sql = "select * from level";
        $query = mysqli_query($db, $sql);
        $result = [];
        if (mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_assoc($query)) {
                $result[] = $row;
            }
            respond(200, array('success' => true, 'data' => $result));
        } else
            respond(404, array('success' => false, 'error' => 'Levels not found'));
    } else if (stripos($_SERVER['REQUEST_URI'], "/GetSubject") !== false) {
        $sql = "select * from Subject";
        $query = mysqli_query($db, $sql);
        $result = [];
        if (mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_assoc($query)) {
                $result[] = $row;
            }
            respond(200, array('success' => true, 'data' => $result));
        } else
            respond(404, array('success' => false, 'error' => 'Subjects not found'));
    } else if (stripos($_SERVER['REQUEST_URI'], "/GetAllPartners") !== false) {
            respond(404, array('success' => false, 'error' => 'User not found'));
    }
    // Get all Partners Table
    else if (stripos($_SERVER['REQUEST_URI'], "/GetAllPartners") !== false) {
        $sql = "select * from Partner";
        $query = mysqli_query($db, $sql);
        $result = [];
        if (mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_assoc($query)) {
                $result[] = $row;
            }
            respond(200, array('success' => true, 'data' => $result));
        } else
            respond(404, array('success' => false, 'error' => 'User not found'));
    }
    // 
    else if (stripos($_SERVER['REQUEST_URI'], '/GetPartnerSubscription') !== false) {
        $db = getDb();
        $sql = "SELECT Subscription.SubscriptionID, Subscription.UserPassport, Subscription.CreatedDate, Subscription.ExpiryDate, Subscription.Status, c.* FROM Subscription LEFT JOIN Partner as c ON Subscription.PartnerID = c.PartnerID";
        $query = mysqli_query($db, $sql);
        $getBundle = [];
        if (mysqli_num_rows($query) > 0) {
            $gid = [];
            $bname = [];
            $getBundlex = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $gid[] = $row['PartnerID'];
                $getBundlex[$row['PartnerID']]['PartnerID'] = $row['PartnerID'];
                $getBundlex[$row['PartnerID']]['BusinessName'] =  $row['BusinessName'];
                $getBundlex[$row['PartnerID']]['ContactName'] =  $row['ContactName'];
                $getBundlex[$row['PartnerID']]['ContractType'] =  $row['ContractType'];
                $getBundlex[$row['PartnerID']]['PartnerAccessToken'] =  $row['PartnerAccessToken'];
                $getBundlex[$row['PartnerID']]['CreatedDate'] =  $row['CreatedDate'];
                $getBundlex[$row['PartnerID']]['UserPassport'] =  $row['UserPassport'];
                $getBundlex[$row['PartnerID']]['PartnerDetails'][] = $row;
            }
            $gid = array_unique($gid);
            $getBundle = [];
            foreach ($gid as $key => $id) {
                $getBundle[] = $getBundlex[$id];
            }
            respond(200, array('success' => true, 'data' => $getBundle));
        } else
            respond(404, array('success' => false, 'error' => 'User Info not found'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/Subscription') !== false) {
        $db = getDb();
        $sql = "select * from Subscription";
        $query = mysqli_query($db, $sql);
        $Subscription = [];

        if (mysqli_num_rows($query) > 0) {
            $pid = [];
            $getSubscription = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $pid[] = $row['PartnerID'];
                $getSubscription[$row['PartnerID']]['PartnerID'] = $row['PartnerID'];
                $getSubscription[$row['PartnerID']]['Subscription'][] = $row;
            }

            $pid = array_unique($pid);
            $Subscription = [];
            foreach ($pid as $key => $id) {
                $Subscription[] = $getSubscription[$id];
            }
            respond(200, array('success' => true, 'data' => $Subscription));
        } else
            respond(404, array('success' => false, 'error' => 'Subscription not found'));
    }
    else if (stripos($_SERVER['REQUEST_URI'], '/Accesslog') !== false) {
        $db = getDb();
        $sql = "select * from AccessLog";
        $query = mysqli_query($db, $sql);
        $AccessLog = [];

        if (mysqli_num_rows($query) > 0) {
            $pid = [];
            $getLog = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $pid[] = $row['SubscriptionID'];
                $getLog[$row['SubscriptionID']]['SubscriptionID'] = $row['SubscriptionID'];
                $getLog[$row['SubscriptionID']]['PartnerInfo'][] = $row;
            }

            $pid = array_unique($pid);
            $AccessLog = [];
            foreach ($pid as $key => $id) {
                $AccessLog[] = $getLog[$id];
            }
            respond(200, array('success' => true, 'data' => $AccessLog));
        } else
            respond(404, array('success' => false, 'error' => 'AccessLog not found'));
    }
    else if (stripos($_SERVER['REQUEST_URI'], '/UpdatePartnerinfo') !== false) {
        $PartnerID = $request['partnerId'];
        $BusinessName = $request['businessName'];
        $ContactName = $request['contactName'];
        $ContractType = $request['contractType'];
        $Status = $request['status'];
        $sql = "UPDATE partnerdb.Partner SET BusinessName = '$BusinessName', ContactName = '$ContactName', ContractType = '$ContractType',  Status = '$Status' where PartnerID = '$PartnerID'";
        $db->query($sql);
        if ($db->errno)
            respond(500, array('success' => false, 'message' => 'db error: ' . $db->error));
        respond(200, array('success' => true, 'message' => 'Partner Information succesfully updated'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/deletePartner') !== false) {
        $PartnerID = $request['partnerId'];
        $sql = "select PartnerID, BusinessName, ContactName, ContractType, PartnerAccessToken, CreatedDate, Status from Partner where PartnerID = '$PartnerID'";
        $query = mysqli_query($db, $sql);
        if (mysqli_num_rows($query) == 1) {
            $sql = "DELETE FROM Partner WHERE PartnerID = '$PartnerID'";
            $db->query($sql);
        }
        // $sql = "select PartnerID, GameID, CategoryID, ID from Bundle where PartnerID = '$PartnerID'";
        // $query = mysqli_query($db, $sql);
        // if (mysqli_num_rows($query) == 1) {
        //     $sql = "DELETE FROM Bundle WHERE PartnerID = '$PartnerID'";
        //     $db->query($sql);
        // }
        if ($db->errno)
            respond(500, array('success' => false, 'message' => 'db error: ' . $db->error));
        respond(200, array('success' => true, 'message' => 'Partner succesfully deleted'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/GetBundleGamesfromCategory') !== false) {
        $db = getDb();
        $sql = "select * from Category";
        $query = mysqli_query($db, $sql);
        $BundleCategory = [];

        if (mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_assoc($query)) {
                $BundleCategory[] = $row;
            }
            respond(200, array('success' => true, 'data' => $BundleCategory));
        } else
            respond(404, array('success' => false, 'error' => 'bundle games not found'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/GetCustomGamesfromGame') !== false) {
        $db = getDb();
        // $PartnerID = $request['partnerId'];
        // $game = getCatalog($PartnerID);
        $sql = "select * from Game";
        $query = mysqli_query($db, $sql);
        $BundleCategory = [];

        if (mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_assoc($query)) {
                $BundleCategory[] = $row;
            }
            respond(200, array('success' => true, 'data' => $BundleCategory));
        } else
            respond(404, array('success' => false, 'error' => 'bundle games not found'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/GetBundleUsers') !== false) {
        $db = getDb();
        //$sql = "SELECT b.PartnerID, b.GameID, Partner.BusinessName, c.* FROM Bundle As b LEFT JOIN Category As c ON b.CategoryID = c.CategoryID join Partner on b.PartnerID = Partner.PartnerID";
        $sql = "SELECT Bundle.PartnerID, Partner.BusinessName, Category.CategoryID, Category.CategoryName, Category.CategoryCode from Bundle join Category on Bundle.CategoryID = Category.CategoryID join Partner on Bundle.PartnerID = Partner.PartnerID";
        $query = mysqli_query($db, $sql);
        $getBundle = [];
        if (mysqli_num_rows($query) > 0) {
            $pid = [];
            $bname = [];
            $getBundlex = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $pid[] = $row['PartnerID'];
                $getBundlex[$row['PartnerID']]['PartnerID'] = $row['PartnerID'];
                $getBundlex[$row['PartnerID']]['BusinessName'] =  $row['BusinessName'];
                $getBundlex[$row['PartnerID']]['gameObject'][] = $row;
            }

            $pid = array_unique($pid);
            $bname = array_unique($bname);
            $getBundle = [];
            foreach ($pid as $key => $id) {
                $getBundle[] = $getBundlex[$id];
            }
            respond(200, array('success' => true, 'data' => $getBundle));
        } else
            respond(404, array('success' => false, 'error' => 'bundle users not found'));
    } else if (stripos($_SERVER['REQUEST_URI'], '/GetCustomUsers') !== false) {
        $db = getDb();
        //$sql = "SELECT b.PartnerID, b.CategoryID as TableID, c.* FROM Bundle As b LEFT JOIN Game As c ON b.GameID = c.GameID";
        $sql = "SELECT Bundle.PartnerID, Bundle.GameID, Partner.BusinessName, Game.GameDescription, Game.GameTitle, Game.levelCode from Bundle join Game on Bundle.GameID = Game.GameID join Partner on Bundle.PartnerID = Partner.PartnerID";
        $query = mysqli_query($db, $sql);
        $getBundle = [];
        if (mysqli_num_rows($query) > 0) {
            $gid = [];
            $bname = [];
            $getBundlex = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $gid[] = $row['PartnerID'];
                $getBundlex[$row['PartnerID']]['PartnerID'] = $row['PartnerID'];
                $getBundlex[$row['PartnerID']]['BusinessName'] =  $row['BusinessName'];
                $getBundlex[$row['PartnerID']]['gameObject'][] = $row;
            }
            $gid = array_unique($gid);
            $getBundle = [];
            foreach ($gid as $key => $id) {
                $getBundle[] = $getBundlex[$id];
            }
            respond(200, array('success' => true, 'data' => $getBundle));
        } else
            respond(404, array('success' => false, 'error' => 'Custom users not found'));
    } else
        respond(400, array('success' => false, 'error' => 'resource or endpoint not found'));
} catch (Exception $e) {
    try {
        $entry = ['time' => $time, 'request' => $request, 'error' => json_encode($e)];
        $fp = file_put_contents('logs/' . $log . '.txt', json_encode($entry, JSON_PRETTY_PRINT), FILE_APPEND);
        respond(500, array('success' => false, 'error' => $e->getMessage()));
    }
    catch (Exception $ex) {
        respond(500, array('success' => false, 'error' => $e->getMessage().'|'.$ex->getMessage()));
    }
} finally {
    if ($db)
        $db->close();
}

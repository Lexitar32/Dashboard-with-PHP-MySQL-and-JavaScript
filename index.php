<?php
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ERROR);

    session_start();

    if(strpos($_SERVER['SCRIPT_URL'], '/thumbnail') >= 1) {
/*		//echo '<pre>'; var_dump($_SERVER); echo '</pre>';
        $path = $_SERVER['PATH_INFO'];
        $gameTitle = str_replace('/thumbnail/', $path); */
        $gameTitle = $_GET['game'];
        $filename = "/gamedata/$gameTitle/$gameTitle.PNG";
        // var_dump($filename);
        if(file_exists($filename)){
            header("Content-Type:image/png");
            readfile($filename);
        } else {
            $filename = "/gamedata/$gameTitle/$gameTitle.gif";
            header("Content-Type:image/gif");
            readfile($filename);
        }
        die;
    }

    if(strpos($_SERVER['SCRIPT_URL'], '/story_content/') >= 1 || strpos($_SERVER['SCRIPT_URL'], '/html5/') >= 1 || strpos($_SERVER['SCRIPT_URL'], '/mobile/') >= 1 || strpos($_SERVER['SCRIPT_URL'], '/lms/') >= 1){
        // $gameId = $_SESSION['gameID'];
        $gameTitle = $_SESSION['gameTitle'];//getGames($gameId, 'game');
        $path = $_SERVER['PATH_INFO'];
        $filename = '/gamedata/' . $gameTitle . $path;
        // var_dump($filename);
        // $filename = '/gamedata/' . $gameTitle . '/story_html5.html';
        $mimeTypes = ['.js' => 'text/javascript', '.css' => 'text/css'];
        // var_dump(mb_strrchr($filename, '.'));
        $mime = $mimeTypes[mb_strrchr($filename, '.')];
        // var_dump(array($filename, $mime, $gameId, $gameTitle));
        header("Content-Type:$mime");
        readfile($filename);
        die;
    }

    if ($json = json_decode(file_get_contents("php://input"), true)) {
        $request = $json;
    } else if ($_POST) {
        $request = $_POST;
    } else if ($_GET) {
        $request = $_GET;
    }
    
    $okay = $request ? true : false;
    $okay = $okay && isset($request['partnerId']);
    $okay = $okay && isset($request['accessToken']);
    $okay = $okay && isset($request['action']);
    // $okay = $okay && isset($request['data']);
	$inputs = []; //for storing and passing multiple inputs to functions
    if($okay){
        header("Content-Type:application/json");
		$con = mysqli_connect("localhost","rookietoosmart","lAunch0ut!","partnerdb");
		// $con = mysqli_connect("localhost","root","","9ijakids");
        if (mysqli_connect_errno()){
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
            die();
        }
        //authenticate the partner
        $pid = mysqli_real_escape_string($con, $request['partnerId']);
        $token = mysqli_real_escape_string($con, strtolower($request['accessToken']));
        $query = mysqli_query($con, "select * from Partner where partnerid = $pid and lower(partneraccesstoken) = '$token'");
        if(mysqli_num_rows($query) > 0) {
            $partner = mysqli_fetch_array($query);
			// var_dump($partner);
            if($partner['Status'] == "Active"){
                $action = $request['action'];
				switch ($action) {
					case "catalog":
                        $response = getCatalog($pid);
                        http_response_code(200);
					break;
					case "group":
					    //$response = getCatalog($pid);
                        $response = getGroup();
                        http_response_code(200);
					break;
						case "level":
					    $response = getLevel();
                        http_response_code(200);
					break;
					case "subject":
					    $response = getSubject();
                        http_response_code(200);
					break;
					case "catalogfilter":
                        $inputs['pid'] = $pid;
						//if (isset($request['gameGroup']))
						//{
							 $inputs['gameGroup'] =  mysqli_real_escape_string($con,$request['Group']);
						//	}
						//if (isset($request['gameLevel']))
						//{
							$inputs['gameLevel'] = mysqli_real_escape_string($con, $request['Level']);
						//	}
                       // if (isset($request['gameSubject']))
						//{
							 $inputs['gameSubject'] = mysqli_real_escape_string($con, $request['Subject']);
						//	}


						$inputs['partnerContract'] =$partner['ContractType'];
                        $response = groupByLevel($inputs);
					break;
					case "subscribe":
                        $inputs['pid'] = $pid;
                        $inputs['userPassport'] = mysqli_real_escape_string($con, $request['userPassport']);
                        $inputs['expiryDate'] = mysqli_real_escape_string($con, $request['expiryDate']);
                        $response = subscribeUser($inputs);
					break;
					case "unsubscribe":
                        $inputs['pid'] = $pid;
                        $inputs['userPassport'] = mysqli_real_escape_string($con, $request['userPassport']);
                        $response = unsubscribeUser($inputs);
					break;
                    case "play":
                        $inBundle = false;
                        $inputs['pid'] = $pid;
                        $inputs['userPassport'] = mysqli_real_escape_string($con, $request['userPassport']);
                        $inputs['gameID'] = mysqli_real_escape_string($con, $request['gameID']);
                        $sql = "select * from Subscription where PartnerID=$pid and UserPassport='$inputs[userPassport]' and status='Active'";
                        $query = mysqli_query($con, $sql);
                        if (mysqli_num_rows($query) > 0) {
							$inputs['subscriptionID'] = mysqli_fetch_array($query)['SubscriptionID'];
                            $catalog = getCatalog($pid);
                            // var_dump($inputs);
                            foreach($catalog as $catGame) {
                                if ($catGame['GameID'] == $inputs['gameID']) {
                                    $inBundle = true;
                                    break; 
                                }
                            }
                            if ($inBundle) {
                                $response = playGame($inputs);
                            } else 
                                $response = 'not in bundle';
                        } else 
                        $response = 'no bundles found';
					break;
                    default:
                        $response = 'action not recognized';
				}
				if($action == "catalog"){
					$response = getCatalog($pid);
					http_response_code(200);
				}
            } else {
                $response = "Your account is suspended, contact your account manager";
                http_response_code(400);
            }
        } else {
            $response = "Invalid credentials";
            http_response_code(400);
        }
        mysqli_close($con);
    } else {
        $response = 'invalid request';
        http_response_code(400);
    }
    $date = strftime('%Y-%m-%d');
    $time = strftime('%H:%M:%S');
    $entry = ['time' => $time, 'request' => $request, 'response' => $response];
    // $fp = file_put_contents('logs/'.$date.'.txt', json_encode($entry, JSON_PRETTY_PRINT),FILE_APPEND);
	echo (is_array($response) ? json_encode($response) : $response);
    // var_dump($response);
    
    function getGames($ids, $id_type = null) {
		$sql = "select * from Game G, Category C where " . (is_null($id_type) ? "G.CategoryID in ($ids)" : "G.GameID in ($ids)") . " and G.CategoryID=C.CategoryID";
        $con = $GLOBALS['con'];
        if(!$con)
            $con = mysqli_connect("localhost","rookietoosmart","lAunch0ut!","partnerdb");
        $query = mysqli_query($con, $sql);
		$i = 0;
		while ($game = mysqli_fetch_array($query)) {
			$local_games[$i]['CategoryName'] = $game['CategoryName'];
            $local_games[$i]['Group'] = $game['GroupName'];
			$local_games[$i]['Level'] = $game['LevelName'];
			$local_games[$i]['Subject'] = $game['SubjectName'];
			$local_games[$i]['Topic'] = $game['CategoryName'];
			$local_games[$i]['GameID'] = $game['GameID'];
			$local_games[$i]['GameTitle'] = $game['GameTitle'];
			$local_games[$i]['GameDescription'] = $game['GameDescription'];
            $local_games[$i]['GameImage'] = 'https://partners.9ijakids.com/index.php/thumbnail?game='.$game['GameTitle'];
			++$i;
		}
		
		return $local_games;
	}
	
		function getGroup() {
	  $sql = "select groupName, groupDescription from groupcategory";
      $query = mysqli_query($GLOBALS['con'], $sql);
	  $i = 0;

			while ($grp = mysqli_fetch_array($query)) {
			$game_group[$i]['Group'] = $grp['groupName'];
			$game_group[$i]['Description'] = $grp['groupDescription'];

			++$i;
		}
			return $game_group;

	 }

		function getSubject() {
	  $sql = "select subjectName, subjectDescription from Subject";
      $query = mysqli_query($GLOBALS['con'], $sql);
	  $i = 0;

			while ($subj = mysqli_fetch_array($query)) {
			$game_group[$i]['Subject'] = $subj['subjectName'];
			$game_group[$i]['Description'] = $subj['subjectDescription'];

			++$i;
		}
			return $game_group;

	 }


			function getLevel() {
	  $sql = "select levelName, levelDescription from level";
      $query = mysqli_query($GLOBALS['con'], $sql);
	  $i = 0;

			while ($lvl = mysqli_fetch_array($query)) {
			$game_group[$i]['Level'] = $lvl['levelName'];
			$game_group[$i]['Description'] = $lvl['levelDescription'];

			++$i;
		}


			return $game_group;

	 }


    function expandCategory($catId) {
        $sql = "select categoryId, categoryName, parentCategoryId from Category where categoryId = $catId union ".
            "select categoryId, categoryName, parentCategoryId ".
            "from (select * from Category ".
                "order by parentCategoryId, categoryId) categories_sorted, ".
                "(select @pv := $catId) initialisation ".
            "where find_in_set(parentCategoryId, @pv) ".
                "and length(@pv := concat(@pv, ',', categoryId));";
        $query = mysqli_query($GLOBALS['con'], $sql);
        $cats = array();
        while($cat = mysqli_fetch_array($query)['categoryId']){
            $cats[] = $cat;//s.",".$cat['categoryId'];
        }
        return implode(',' , $cats);
    }

    function getCatalog($partnerId){
        $sql = "select categoryId, gameId from Bundle where partnerId = $partnerId";
        $query = mysqli_query($GLOBALS['con'], $sql);
        $catIds = $gameIds = '';
        $games = array();
		
        while($bundle = mysqli_fetch_array($query)) {
            if($catId = $bundle['categoryId']){
            //  $catIds .= ($catIds !== '') ? ',' . expandCategory($catId) : expandCategory($catId);
                $catIds .= expandCategory($catId);
				//$games = getGames($catIds);
            } else if ($gameId = $bundle['gameId']) {
				$gameIds .= ($gameIds !== '') ? ',' . $gameId : $gameId;
            }
        }
        // var_dump(array($catIds, $gameIds));
		
        if ($gameIds !== '')
            $games = getGames($gameIds, 'game');
        else
            $games = getGames($catIds);
		return $games;
    }
    

	 function groupByLevel($inputsArray){
		 $partnerIDRecord=$inputsArray[pid];
		 $gamegroup=$inputsArray[gameGroup];

		 $gameSubject=$inputsArray[gameSubject];
		 $gameLevel=$inputsArray[gameLevel];
		 $partnerContractType=$inputsArray['partnerContract']; //Partner contract


		  // Get Type of Custom or Bundle
		  //$query = mysqli_query($GLOBALS['con'], $sql);
        $catIds = $gameIds = '';
        $game = array();

		$filtergame='';


		 if ($partnerContractType =="Custom" )
		     {
					  $sql5 = "select categoryId, gameId from Bundle where partnerId = $partnerIDRecord";
					  $sql2 = "select gameId from Bundle where partnerId = $partnerIDRecord";
					  $con = $GLOBALS['con'];
					if(!$con)
						$con = mysqli_connect("localhost","rookietoosmart","lAunch0ut!","partnerdb");

                    $query = mysqli_query($con, $sql5);
					//$query = mysqli_query($GLOBALS['con'], $sql5);
					$bundle = mysqli_fetch_array($query);
					$gameId = $bundle['gameId'];
					$gameIds .= ($gameIds !== '') ? ',' . $gameId : $gameId;


					while($bundle = mysqli_fetch_array($query)) {
						$gameId = $bundle['gameId'];
                        $gameIds .= ($gameIds !== '') ? ',' . $gameId : $gameId;
					}

						$sqlgame1 ="SELECT Gm.GameID, Gm.GameTitle, Gm.GameDescription,  Cat.CategoryName as Topic, Cat.GroupName, Cat.LevelName, Cat.SubjectName " ;
						$sqlgame2 ="FROM Game Gm join Category Cat on Gm.CategoryID = Cat.CategoryID where Gm.GameID in ($gameIds) " ;


						if(!$inputsArray['gameGroup'] )
						{
						  	$sqlgroup3="";
						}
						else
						{

							 $sqlgroup3= " AND Cat.GroupName = '$gamegroup' ";
							//UserPassport='$userID'
						}
						if(!$inputsArray['gameLevel'])
						//if(!$inputsArray['gameLevel'] )
						{
						  	$sqlgroup4="";
						}
						else
						{
							$sqlgroup4= " AND Cat.LevelName = '$gameLevel' ";
						}
						if(!$inputsArray['gameSubject'] )
						{
						  	$sqlgroup5="";
						}
						else
						{
							$sqlgroup5= " AND Cat.SubjectName ='$gameSubject' ";
						}

					$filtergame .=$sqlgame1 . $sqlgame2 . $sqlgroup3 . $sqlgroup4 . $sqlgroup5 .";" ;
			       //  $sqlgroup= 	$sqlgame1   ;                                             }

			 //MOVE to function
			  $query = mysqli_query($GLOBALS['con'], $filtergame);
		      $i = 0;
		while ($game = mysqli_fetch_array($query)) {

				//$local_games[$i]['GameID'] = $game[GameID];

				$local_games[$i]['GameTitle'] = $game['GameTitle'];
				$local_games[$i]['GameDescription'] = $game['GameDescription'];
				$local_games[$i]['Topic'] = $game['Topic'];
			    $local_games[$i]['Group'] = $game['GroupName'];
			    $local_games[$i]['Level'] = $game['LevelName'];
			    $local_games[$i]['Subject'] = $game['SubjectName'];
				$local_games[$i]['GameImage'] = 'https://partners.9ijakids.com/index.php/thumbnail?game='.$game['GameTitle'];

			   ++$i;

			//$local_games[$i]['ggg'] = $i;
			//$local_games[$i]['GameTitle'] = $game['GameTitle'];
		              }

		//	$local_games =  $partnerIDRecord . " this is games contract  " . $partnerContractType;
			//$filtergame .= $sql5 . $sqlgame1 . $sqlgame2
			//return $filtergame;
			//$local_games =$filtergame ;
			//return $game;
			if(!$local_games)
			{
			$local_games = "No results match your filter criteria";
			    return ['GamesCategoryFilterSuccess' => false];
			}
			else
			{
		        return $local_games;
			}
			 }

		 else{
			        $sqlCat='';
					$sql2 = "SELECT Bund.categoryId, Bund.gameId, Cat.CategoryName FROM Bundle Bund join Category Cat on Bund.CategoryID= Cat.CategoryID where partnerId = '$partnerIDRecord'";
					//$sql2 = "select categoryId  from Bundle where partnerId = '$partnerIDRecord'";
					//SELECT Bund.categoryId, Bund.gameId, Cat.CategoryName FROM partnerdb.Bundle Bund
                    //join Category Cat on Bund.CategoryID= Cat.CategoryID

					$sqlcat1 ="SELECT Cat2.CategoryID as GamesCatID, Gm.GameID, Gm.GameTitle, Gm.GameDescription, Cat2.CategoryName as Topic, Cat2.GroupName, Cat2.LevelName,";
					$sqlcat2  ="Cat2.SubjectName FROM Bundle Bund join Category Cat on Bund.CategoryID= Cat.CategoryID";
                    $sqlcat3=" join Category Cat2 on Cat2.GroupName= Cat.CategoryName join Game Gm on Gm.CategoryID = Cat2.CategoryID where partnerId = '$partnerIDRecord' ";

							if(!$inputsArray['gameGroup'] )
						{
						  	$sqlgroup3="";
						}
						else
						{

							 $sqlgroup3= " AND Cat2.GroupName = '$gamegroup' ";
							//UserPassport='$userID'
						}
						if(!$inputsArray['gameLevel'])
						//if(!$inputsArray['gameLevel'] )
						{
						  	$sqlgroup4="";
						}
						else
						{
							$sqlgroup4= " AND Cat2.LevelName = '$gameLevel' ";
						}
						if(!$inputsArray['gameSubject'] )
						{
						  	$sqlgroup5="";
						}
						else
						{
							$sqlgroup5= " AND Cat2.SubjectName ='$gameSubject' ";
						}



                    $sqlCat  .= $sqlcat1 .$sqlcat2 .$sqlcat3 .$sqlgroup3 .$sqlgroup4 .$sqlgroup5;
					// $sqlCat  .= $sqlcat1 .$sqlcat2 .$sqlcat3;


					$query = mysqli_query($GLOBALS['con'], $sqlCat);
					//$catIds = $catId = $catName = $catNames = '';
					$i=0;
                    $bundle= array();


					while($bundle = mysqli_fetch_array($query)) {

					 $local_games[$i]['GameID'] = $bundle['GameID'];
					// $local_games[i]['num'] = $i . $catName ;
					$local_games[$i]['GameTitle'] = $bundle['GameTitle'];
					$local_games[$i]['GameDescription'] = $bundle['GameDescription'];
					$local_games[$i]['Topic'] = $bundle['Topic'];
					$local_games[$i]['Group'] =$bundle['GroupName'];
					$local_games[$i]['Level'] = $bundle['LevelName'];
					$local_games[$i]['Subject'] = $bundle['SubjectName'];
					$local_games[$i]['GameImage'] = 'https://partners.9ijakids.com/index.php/thumbnail?game='.$bundle['GameTitle'];


					 ++$i;
					 // */
					// }
					}

		if(!$local_games)
			{
			$local_games = "No results match your filter criteria";
			    return ['GamesCategoryFilterSuccess' => false];
			}
			else
			{
		        return $local_games;
			}
		//return $local_games;
		//return $filtergame ;




		 }


			return $local_games;
			//return $bundle;

    }

    //fn subscribeUser backup
	/*function subscribeUser ($inputsArray) {
        if(!$inputsArray['expiryDate'])
            $inputsArray['expiryDate'] = date("Y-m-d", strtotime(date("Y-m-d", strtotime(mktime())) . " + 1 year"));
        $sql = "select * from Subscription where PartnerID=($inputsArray[pid] and UserPassport='$inputsArray[userPassport]'";
        if (($query = mysqli_query($GLOBALS['con'], $sql)) && (mysqli_num_rows($query) > 0)) {
            $sql = "update Subscription set Status='Active', ExpiryDate='$inputsArray[expiryDate]' where PartnerID=$inputsArray[pid] and UserPassport='$inputsArray[userPassport]'";
            mysqli_query($GLOBALS['con'], $sql);
        } else {
            $sql = "insert into Subscription (PartnerID, UserPassport, ExpiryDate) values ($inputsArray[pid], '$inputsArray[userPassport]', '$inputsArray[expiryDate]')";
            if ($query = mysqli_query($GLOBALS['con'], $sql)) {
                // return ['subscribeUserSuccess' => true, 'sql' => $sql];
                return ['subscribeUserSuccess' => true];
            } else return ['subscribeUserSuccess' => false];
        }
	} */

	function subscribeUser ($inputsArray) {

        if(!$inputsArray['expiryDate'] || (date_diff($todaysDate = date_create("now"), $expiryDate = date_create($inputsArray['expiryDate'])) < 0))
            //$inputsArray['expiryDate'] = date_format(date_add($todaysDate, date_interval_create_from_date_string("30 days")), "Y-m-d");
		 //set Expiry date to a year if not provided
		 $inputsArray['expiryDate'] = date("Y-m-d H:i:s", strtotime("+1 year"));
		 $userID=$inputsArray[userPassport];
		 $partnerIDRecord=$inputsArray[pid];
		 $updatedExpiryDate=$inputsArray['expiryDate'];
		 
		 $sql3 = "select PartnerID, SubscriptionID from Subscription where PartnerId = $partnerIDRecord and UserPassport='$userID'" ;
		 $result = mysqli_query($GLOBALS['con'], $sql3);
		 $resultRows=mysqli_num_rows($result);
			
		//if UserPassport exists for Partner ID, Update status to Active and Update Expiry Date
		if($result && $resultRows> 0) {
      
			$sql4 = "UPDATE Subscription set Status='Active', ExpiryDate='$updatedExpiryDate' where PartnerId = $partnerIDRecord and UserPassport='$fff'  ";
			
			mysqli_query($GLOBALS['con'], $sql4);
			return ['subscribeUserAlreadyExistNoChange' => true];
         }else{
			 // Create a subscription for user
						$sql = "insert into Subscription (PartnerID, UserPassport, ExpiryDate) values ($inputsArray[pid], '$inputsArray[userPassport]', '$inputsArray[expiryDate]')";
						if ($query = mysqli_query($GLOBALS['con'], $sql)) {
							// If account created successfully return true
						   return ['subscribeUserSuccess' => true];
						} else return ['subscribeUserSuccess' => false];
        }
	}

	function unsubscribeUser ($inputsArray) {
		$sql = "update Subscription set Status='Inactive' where PartnerID=$inputsArray[pid] and UserPassport='$inputsArray[userPassport]'";
		if ($query = mysqli_query($GLOBALS['con'], $sql)) {
			// return ['unsubscribeUserSuccess' => true, 'sql' => $sql];
			return ['unsubscribeUserSuccess' => true];
		} else return ['unsubscribeUserSuccess' => false];		
	}
	
	function playGame ($inputsArray) {
        // set session with partnerId, accessToken and gameId then redirect user to play.php
        $_SESSION['gameID'] = $inputsArray['gameID'];
        $_SESSION['partnerID'] = $inputsArray['pid'];
        $_SESSION['accessToken'] = $GLOBALS['token'];
        $_SESSION['userPassport'] = $inputsArray['userPassport'];
        // header('Location: play.php');
        // $gameRaw = getGames($gameId, 'game');
        $gameTitle = getGames($inputsArray['gameID'], 'game')[0]['GameTitle'];
        $_SESSION['gameTitle'] = $gameTitle;
		$filename = '/gamedata/' . $gameTitle . '/story_html5.html';
		if (file_exists($filename) && ($gameHTMLFile = file_get_contents($filename))) {
			$sql = "insert into AccessLog (SubscriptionID, GameID) values ($inputsArray[subscriptionID], $inputsArray[gameID])";
			$query = mysqli_query($GLOBALS['con'], $sql);
            header("Content-Type:text/html");
			return $gameHTMLFile;
        } else 
            return json_encode(['gameHTMLFile' => null, 'playSuccess' => false, 'file' => $filename, 'gameId' => $inputsArray['gameID']]);
	}
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>API Page</title>

    <link href="css/bootstrap/bootstrap.min.css" rel="stylesheet" type="text/css">

</head>


<body>
<div class="container">
    <form action="index.php" method="post">
        <input type="hidden" name="username" value="<?= $_POST['username']?>">
        <input type="hidden" name="password" value="<?= $_POST['password']?>">
        <input type="hidden" name="camera_number" value="<?= $_POST['camera_number']?>">
        <button type="submit" class="btn btn-primary">Retour</button>
    </form>

    <div id="log" style="background-color: #b1b1b1;">
        <?php

        // Php script that get and saves all video sequence clips for a given camera and a given time frame.
        // by Pascal Blais
        // iWatchLife, June 2017, v1.0.2b
        // Instructions: replace the values of the following 8 variables as appropriate.
        // Note: system returns a page with a maximum of 1025 sequences at a time; if your time frame has more than the maximum amount of sequences, then run this script multiple times with smaller intervals, or feel free to modify the code to handle multiple pages.
        $userName = $_POST['username'];                             // user account name
        $userPassword = $_POST['password'];                     // user account password
        $clientId = $_POST['client_id'];                             // client id
        $clientSecret = $_POST['client_secret'];                     // client secret
        $cameraIndex = 0;                                   // user camera index (ex: if user has 3 cameras, then possible index are 0, 1, and 2).
        $startTimestamp = $_POST['date'] . '%2000:00:00';          // start of timeline (use format "yyyy-mm-dd%20hh:mm:ss")
        $endTimestamp = $_POST['date'] . '%2023:59:59';            // end of timeline (use format "yyyy-mm-dd%20hh:mm:ss")
        $storageDirectory = '/tmp/';                        // directory where clips will be saved. Note: value must end with a '/'.
        $sleepTime = 500000; // in usec, e.g. 500000 = 0.5 sec, sleep between each clip downloading request; useful when downloading a large amount of data, this insure that the system is not overloaded with work.

        $max_block_size=1025; // max. 1025, determines how many clips are read per block, the number of clips downloaded per block is $max_block_size-1, the last clip is only read for its timestamp

        for($cameraIndex; $cameraIndex < $_POST['camera_number']; $cameraIndex++){
            while (1) // loops until a block with size <  $max_block_size is read, otherwise downloads $max_block_size-1 clips and uses the last clip's timestamp for the start timestamp of next block
            {
                print "\n\nGet sequence clips\n";
                print "Using following information:\n";
                print " user name: ". $userName ."\n";
                print " user password: ". str_repeat("*", strlen($userPassword)) ."\n";
                print " client id: ". $clientId ."\n";
                print " client secret: ". str_repeat("*", strlen($clientSecret)) ."\n";
                print " camera index: ". $cameraIndex ."\n";
                print " start timestamp: ". $startTimestamp ."\n";
                print " end timestamp: ". $endTimestamp ."\n";
                print " storage directory: ". $storageDirectory ."\n";
                print " sleep time: ". ($sleepTime/1000000) ." sec.\n";
                print "\n";

                // Step 1: get auth code
                $data = array('username' => $userName, 'password' => $userPassword, 'client_id' => $clientId);
                $options = array(
                    'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'POST',
                        'content' => http_build_query($data)
                    )
                );
                $context = stream_context_create($options);
                $result = file_get_contents('http://api.smartcamcloud.ca/sapi/account/auth', false, $context);

                if ($result === FALSE)
                {
                    exit ("Error with auth request");
                }

                $authResponse = json_decode($result);
                $code = $authResponse->{'code'};
                print "code: ". $code ."\n";

                // Step 2: get access_token
                $options = array(
                    'http' => array(
                        'header'  => "Content-type: application/json\r\n",
                        'method'  => 'POST',
                        'content' => '{"client_id": "'. $clientId . '", "client_secret": "'. $clientSecret .'", "grant_type": "authorization_code", "code": "'. $code .'"}'
                    )
                );
                $context = stream_context_create($options);
                $result = file_get_contents('http://auth.smartcamcloud.ca/oauth', false, $context);

                if ($result === FALSE)
                {
                    exit ("Error with oauth request");
                }

                $oauthResponse = json_decode($result);
                $accessToken = $oauthResponse->{'access_token'};
                $scope = $oauthResponse->{'scope'};
                $cameraIdList = explode(' ', $scope);
                print "accessToken: ". $accessToken ."\n";
                print "cameraId list: ". $scope ."\n";
                print "number of camera: ". count($cameraIdList) ."\n";
                $cameraId = $cameraIdList[$cameraIndex];
                print "cameraId: ". $cameraId ."\n";

                // Step 3: get sequences description
                $options = array(
                    'http' => array(
                        'header'  => "Accept:application/json, text/plain, */*\r\n".
                            "Authorization:Bearer ". $accessToken ."\r\n",
                        'method'  => 'GET'
                    )
                );
                $context = stream_context_create($options);
                $result = file_get_contents('http://activity.smartcamcloud.ca/v2/sequence?camera_id='. $cameraId .'&start='. $startTimestamp .'&end='. $endTimestamp, false, $context);

                if ($result === FALSE)
                {
                    exit ("Error getting sequence description");
                }

                $sequenceResponse = json_decode($result);
                $embeddedData = $sequenceResponse->{'_embedded'};
                $sequenceList = $embeddedData->{'sequence'};
                $sequenceListSize = count($sequenceList);

                if ($sequenceListSize > $max_block_size)
                {
                    $sequenceListSize=$max_block_size;
                }
                print "number of sequences: ". ($sequenceListSize-1) ."\n"; // $sequenceListSize-1 downloaded sequences, the last sequence is only used for timestamp of next block

                // Step 4: getting sequence clips
                for ($i = 0; $i < $sequenceListSize; $i++)
                {
                    print "-\n";
                    $sequence = $sequenceList[$i];
                    $timestamp = $sequence->{'timeStamp'};
                    $id = $sequence->{'id'};
                    $links = $sequence->{'_links'};
                    $self = $links->{'self'};
                    $sequenceUrl = $self->{'href'};
                    //$filename = $storageDirectory . $cameraIndex . str_replace(array(" ", ".", ":", "-"), "", $timestamp) .'_'. $id . ".mp4";
                    $filename = $storageDirectory . $cameraIndex . str_replace(array(" ", ".", ":", "-"), "", $timestamp) . ".mp4";

                    if (($i==$sequenceListSize-1) && ($sequenceListSize==$max_block_size))  // if last clip and not last block, don't download, instead use read timestamp as $startTimestamp of next block
                    {
                        $startTimestamp=substr($timestamp,0,10)."%20".substr($timestamp,11,8);
                    }
                    else
                    {
                        print "sequence index: ". $i ."\n";
                        print "sequence timestamp: ". $timestamp ."\n";
                        print "sequence id: ". $id ."\n";
                        print "sequence url: ". $sequenceUrl ."\n";
                        print "sequence filename: ". $filename ."\n";

                        $options = array(
                            'http' => array(
                                'header'  => "Accept:application/json, text/plain, */*\r\n".
                                    "Authorization:Bearer ". $accessToken ."\r\n",
                                'method'  => 'GET'
                            )
                        );
                        $context = stream_context_create($options);
                        $result = file_get_contents($sequenceUrl ."/video", false, $context);

                        if ($result === FALSE)
                        {
                            exit ("Error getting sequence clip");
                        }

                        file_put_contents($filename, $result);

                        usleep($sleepTime); // usec, 500000 = 0.5 sec
                    }
                }

                if  ($sequenceListSize < $max_block_size)
                {
                    break;
                }
            }
        }


        ?>
    </div>
   


</div>
</body>
</html>


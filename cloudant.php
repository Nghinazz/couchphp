<?php
 //Connection parameters to connect to your Cloudant service deployment.
 $options['host'] = "ms-open-tech.cloudant.com"; 
 $options['port'] = 5984;
 $options['user'] = "ms-open-tech";
 $options['pass'] = "Password50";

 // Instantiate the CouchSimple class you use to make your requests
 $couch = new CouchSimple($options);
 $connect = 0; 

 // See if we can make a connection
 try {
	$resp = $couch->send("GET", "/");
    //var_dump ($resp); //This will return an OK message if you are able to connect
    $connect = 1; 
 } 
 catch (Exception $e) {
	echo "Error:".$e->getMessage()." (errcode=".$e->getCode().")\n";
	exit(1);
 }
 
  // If there is a submission, validate and upload it to the server.
  if ($_POST){
         // This demo only allows images to be uploaded
         if ((($_FILES["file"]["type"] == "image/gif")
            || ($_FILES["file"]["type"] == "image/jpeg")
            || ($_FILES["file"]["type"] == "image/pjpeg"))
            )
              {
              if ($_FILES["file"]["error"] > 0)
                {
                echo "Error: " . $_FILES["file"]["error"] . "<br />";
                }
              else
                {
                $image = file_get_contents($_FILES["file"]["tmp_name"]);
                $image = base64_encode($image);
                }
              }
            else
              {
              echo "Invalid file";
             }
             $sImage = array();
        $sTitle = $_POST['title'];
        $sSubTitle = strtok($_FILES["file"]["name"], '.');
        $sDescription = $_POST['description'];
        $sGroup = $_POST['group'];
        $sFile = "\"_attachments\": {\"" . $_FILES["file"]["name"] . "\": { \"content_type\": \"image/jpg\",\"data\":\"$image\" }}";
        $sID = str_replace(" ", "_", $sTitle); //We will use the title as id (converting spaces to '_')
        $sSend = "{\"title\": \"" . $sTitle . "\", \"subtitle\": \"" . $sSubTitle . "\", \"type\" : \"picture\", \"description\": \"" . $sDescription . "\", \"group\": \"" . $sGroup . "\", " . $sFile . " }";
        $resp = $couch->send("PUT", "/photo-album/$sID", $sSend);
        //var_dump($sSend);
  }

 // Function to get all results from the database and write them into our table
 function getAll() {
     global $options, $couch;

    try {
    $resp = $couch->send("GET", "/photo-album/_all_docs");
    $resp = json_decode($resp, true);
    $item = $resp["rows"];

    foreach($item as $item){
        $item = $item["key"];
        $resp = $couch->send("GET", ("/photo-album/" . $item));
        $resp = json_decode($resp, true);
        //var_dump ($resp);echo "<p>";
        if ($resp["type"]=="picture"){
          $imgURL = "http://ms-open-tech.cloudant.com/photo-album/" . $item . "/" . $resp["subtitle"] . ".jpg";
          echo "<tr><td>" . "<a href=" . $imgURL . " target=_blank ><img border=0 src=" . $imgURL . " width=100></a>" . "</td><td>" . $resp["title"] . "</td><td>" . $resp["subtitle"] . "</td><td>" . $resp["description"] . "</td><td>" . $resp["group"] . "</td></tr>";
        }
    }

    } catch (Exception $e) {
	echo "Error:".$e->getMessage()." (errcode=".$e->getCode().")\n";
	exit(1);
    }
 }

 // Class to run our CouchDB requests through
 class CouchSimple {
   function CouchSimple($options) {
       foreach($options AS $key => $value) {
        $this->$key = $value;
       }
   } 
   
   function send($method, $url, $post_data = NULL) {
      $s = fsockopen($this->host, $this->port, $errno, $errstr); 
      if(!$s) {
         echo "$errno: $errstr\n"; 
         return false;
      } 

      $request = "$method $url HTTP/1.0\r\nHost: $this->host\r\n"; 

      if ($this->user) {
         $request .= "Authorization: Basic ".base64_encode("$this->user:$this->pass")."\r\n";
      }

      if($post_data) {
         $request .= "Content-Length: ".strlen($post_data)."\r\n\r\n"; 
         $request .= "$post_data\r\n";
      } 
      else {
         $request .= "\r\n";
      }

      fwrite($s, $request); 
      $response = ""; 

      while(!feof($s)) {
         $response .= fgets($s);
      }

      list($this->headers, $this->body) = explode("\r\n\r\n", $response); 
      return $this->body;
   }
 }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Cloudant Photo Album</title>
    </head>
    <body>
    <div width="100%" align="center">
    <h1>Cloudant Service Photo Album</h1>    
    <p>Photo Album</p>
    <table cellpadding=4 cellspacing=0 border="1" bordercolor="black" width="750">
        <tr>
            <td>Image</td><td>Title</td><td>Subtitle</td><td>Description</td><td>Group</td>
        </tr>
        <?php 
            //Complete the table with the database contents
            getAll();
        ?> 
    </table>
    <p><hr width=70%></p>
    <p>Enter new Image:</p>
        <form name="add" method="post" action="<?php echo $PHP_SELF;?>" enctype="multipart/form-data">
        <table cellpadding=4 cellspacing=0 border="1" bordercolor=black>
            <tr><td>Image file: </td><td><input type="file" name="file" id="file" /> </td></tr>
            <tr><td>Enter a title: </td><td><input type="text" name="title" /></td></tr>
            <tr><td>Enter a description: </td><td><input type="text" name="description" /></td></tr>
            <tr><td>Choose a group: </td><td><select name="group" style="width:100px;margin:5px 0 5px 0;"><option value="Pics">Pics</option><option value="Images">Images</option></select></td></tr>
            <tr><td>&nbsp;</td><td><input type="submit" value="Add Image" /></td></tr>
        </table>
        </form>
    </div>
    </body>
</html>

<?php
$hostname = "mysql.server.com";   	// eg. mysql.yourdomain.com
$username = "mysqlusername";   		// the mysql username
$password = "mysqlpassword";   		// the mysql password
$database = "mysqldatabase";   		// the database name
									// Note: db has 2 tables;	CREATE TABLE `hash_key` (`id` int(11) NOT NULL AUTO_INCREMENT,`hash_key` varchar(12) NOT NULL,  PRIMARY KEY (`id`)
									// and						CREATE TABLE `msg` (`id` int(11) NOT NULL AUTO_INCREMENT,`timestamp` int(10) NOT NULL,`hash_key` varchar(12) NOT NULL, `encrmsg` varchar(500) NOT NULL, PRIMARY KEY (`id`)

$varBootstrapBase = "http://www.rackverse.com/bootstrap/"; //For Bootstrap CSS, see http://twitter.github.io/bootstrap/

function encr($thekey, $themsg)
			{
				$encrypted_text = trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $thekey, $themsg, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
				return $encrypted_text;
				die();
}

function decr($thekey, $themsg)
			{
				$decrypted_text = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $thekey, base64_decode($themsg), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
				return $decrypted_text;
				die();
}

function time_elapsed($secs){
    $bit = array(
        'y' => $secs / 31556926 % 12,
        'w' => $secs / 604800 % 52,
        'd' => $secs / 86400 % 7,
        'h' => $secs / 3600 % 24,
        'm' => $secs / 60 % 60,
        's' => $secs % 60
        );
       
    foreach($bit as $k => $v)
        if($v > 0)$ret[] = $v . $k;
       
    return implode(' ', $ret);
    }
	
?>
<!DOCTYPE html>
<html>
<head>
	<title>HashLine</title>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link href='<?php echo $varBootstrapBase."css/bootstrap.min.css";?>' rel='stylesheet' media='screen'>
	<link href='<?php echo $varBootstrapBase."css/darkstrap.css";?>' rel='stylesheet' media='screen'>
</head>
<body>
<div class='container'>
	<?PHP
	
	mysql_connect($hostname,$username,$password);
	@mysql_select_db($database) or die( "Unable to select database");
			
	if(isset($_POST['k'])) {
		$k = htmlspecialchars($_POST['k']);
		$hk = substr(hash("sha512",$k), "0", "12");
		$query="SELECT * FROM `hash_key` WHERE `hash_key` = '$hk'";
			$result=mysql_query($query);
			if(!mysql_num_rows($result)){
				$pop_hash_button = $hk.' key not found. Inserting..';
				$query="INSERT INTO `hash_key` (`id` ,`hash_key`) VALUES (NULL , '$hk');";
				$result=mysql_query($query);
			}else{
				$pop_hash_button = 'Welcome '.$hk;
			}
		$tquery="SELECT * FROM `msg` WHERE `hash_key` = '$hk' ORDER BY `msg`.`timestamp`  DESC";
	}else{

	}
			
	if(empty($_POST['m'])) {
		
		}else{
		//If there's a message, encr and post it
		$m = htmlspecialchars($_POST['m']);
		$em = encr($k, $m);
		$query="INSERT INTO `msg` (`id` ,`timestamp` ,`hash_key` ,`encrmsg`)VALUES (NULL , '".time()."', '$hk', '$em');";
		$result=mysql_query($query);		
		}
	?>
	
	<form class='form-inline' id='input' name='input' method='post' action='index.php'>
		<div class="input-prepend"><span class="add-on"><abbr title="This is your shared secret or password, max length 25chars"><i class="icon-lock"></i></abbr></span><input type='password' class='span3' name='k' maxlength='25' <?php if(empty($k)){echo "placeholder='shared secret goes here'";}else{echo "value='$k'";}?>></div>
		<div class="input-prepend"><span class="add-on"><abbr title="Line or message to hash, max length 60 chars"><i class="icon-comment"></i></abbr></span><input type='text' class='span7' name='m' maxlength='60' placeholder="Type something..."></div>
		<button type='submit' class='btn btn-success'>HashLine!</button>
		<a class='btn btn-inverse' href="http://www.rackverse.com/hashline/">Home</a>
		<a class='btn btn-inverse' data-toggle="modal" role="button" href="#about">donate & contact</a>
		<?php if(empty($pop_hash_button)) {}else{echo "<a class='btn btn-info'>$pop_hash_button</a>";}?>
    </form>
		
	<?PHP
		//if $tquery open the table and GO
		if(isset($tquery)) {
			echo "<table class='table table-striped'><thead><tr> <th class='span1' >Time</th><th class='span1'><i class='icon-lock'></i>Hashkey</th><th class='span10'><i class='icon-comment'></i>Hashline</th><th></th></tr></thead><tbody>";
				if(isset($em)) {echo "<tr class='success'><td>Now</td><td>$hk</td><td>\"$em\" successfully inserted with hashkey $hk</td><td></td></tr>";}
			
			$result=mysql_query($tquery);
			$num=mysql_numrows($result);
			$i=0;
			
			while ($i < $num){
				$msgTimestamp=mysql_result($result,$i,"timestamp");
				if($msgTimestamp==time()){
					$tr = "<tr class='success'>";
				}else{
					$tr = "<tr>";
				}
				$msgHashKey=mysql_result($result,$i,"hash_key");
				$msgencrmsg=mysql_result($result,$i,"encrmsg");
				$msgmsg = decr($k,$msgencrmsg);

				$msgTime = time_elapsed((time()+1)-$msgTimestamp);
				echo "$tr<td>$msgTime</td><td>$msgHashKey</td><td>$msgmsg</td><td>...</td></tr>";
				$i++;
			}
			
			//close the table
			echo "</tbody></table>";
		}else{
			//tquery not set so do THIS
			echo "<div class='hero-unit'>
				<h1>Hashline</h1>
				<p>Anon chat system... with the powah of hash!</p><br>
				<p><h4>Start here</h4></p>
				<p>Submit key & message. Send key to friend. Start chatting.</p>
				<p><h4>How it works</h4></p>
				<p>Your shared secret or hashkey is hashed with <code>substr(hash('sha512',shared_secret), '0', '12')</code>and stored in our SQL db. Your message is encrypted with <code>mcrypt_encrypt(MCRYPT_RIJNDAEL_256)</code> and also stored.</p>
				<p>If you enter just a hash key, we search for its hash in the message table and decrypt/return matching messages.</p>
				<p>We delete messages every so often. We cannot ensure the security of your connection to this sever, so use Tor!</p><br>
				<a class='muted' href='http://blockchain.info/fb/128pbb'>Donate to 128pbBr....LVL</a>
				
				</div>";
		}
	//done with body table, begin footer below

	echo "<div class='row'>";
		echo "<div class='span4'>";
			$num = mysql_numrows(mysql_query("SELECT * FROM `hash_key`"));
			$maxkeys = 256;
			$percent = ($num / $maxkeys)*100;
			$rempercent = (($maxkeys - $num)/$maxkeys)*100;
			echo "<div class='progress span1'><div class='bar bar-warning' style='width: ".$percent."%;'></div><div class='bar bar-success' style='width: ".$rempercent."%;'></div></div>";
			echo "$num/$maxkeys global lines used";
		
		echo "</div>";
		echo "<div class='span4'>";
		
			$num = mysql_numrows(mysql_query("SELECT * FROM `msg`"));
			$maxmsg =256;
			$percent = ($num / $maxmsg)*100;
			$rempercent = (($maxmsg - $num)/$maxmsg)*100;
			echo "<div class='progress span1'><div class='bar bar-warning' style='width: ".$percent."%;'></div><div class='bar bar-success' style='width: ".$rempercent."%;'></div></div>";
			echo "$num/$maxmsg global keys used";
			
		echo "</div>";
		echo "<div class='span4'>";
		echo "<a rel='license' href='http://creativecommons.org/licenses/by-sa/3.0/deed.en_US'><img alt='Creative Commons License' style='border-width:0' src='http://i.creativecommons.org/l/by-sa/3.0/88x31.png' /></a><a rel='license' href='http://creativecommons.org/licenses/by-sa/3.0/deed.en_US'>CC A-SA 3.0</a>.";
		echo "</div>";
	echo "</div>";
?>

	<div id="about" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-header">
			<!--<button type="button" class="close" data-dismiss="modal" aria-hidden="true">close</button> -->
			<h3 id="myModalLabel">Hashline:donate:contact</h3>
		</div>
		<div class="modal-body">
			<p><a class='muted' href='http://blockchain.info/fb/128pbb'>If you like this, please donate to 128pbBr8WjSfVrmx1Dnun63yrDaGxNmLVL</a></p><p> To contact us, please post a Hashline with the shared secret as 'torpedo'. Thanks for using this site :)</p>
			</div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
		</div>
	</div>
</div> <!-- /container -->
<?php
	echo "<script src='".$varBootstrapBase."js/bootstrap.min.js'></script><script src='http://code.jquery.com/jquery.js'></script>";
	echo "<script src='".$varBootstrapBase."js/bootstrap-modal.js'></script>";
?>
</body>
</html>
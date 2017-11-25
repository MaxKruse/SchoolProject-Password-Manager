<?php



//Setup Variables

$firstTime = TRUE;
$PassPath = __DIR__ . "\\pwd.nb2";
$MasterPath = __DIR__ . "\\master.nb2";

$PassHandle = "";
$MasterHandle = "";



//Check for First time Run or invalid Password Files
if(file_exists($PassPath) && file_exists($MasterPath) && filesize($MasterPath) == 88)
	$firstTime = FALSE;

//First Time Setup

if($firstTime)
{
	CLS();
	print "First time Setup";
	SlowDots();
	//Delete all Files for Now
	if(file_exists($PassPath))
		unlink($PassPath);
	if(file_exists($MasterPath))
		unlink($MasterPath);

	//Get UserInput for MasterPassword
	$tempMasterPW = Input("\nPlease decide on a MasterPassword (this can be changed later): ");

	$MasterHandle = fopen($MasterPath, "x");

	$data = easyEncrypt($tempMasterPW);

	fwrite($MasterHandle, $data);
	fclose($MasterHandle);


	//Format of $PassPath:
	//                          Email/Username, Website, hashedPassword
	$PassHandle = fopen($PassPath, "x");
	fclose($PassHandle);

}



//Main Routine

CLS();

$MasterPW = Input("Please input your MasterPassword: ");

$MasterHandle = fopen($MasterPath, "r");

$fileData = fread($MasterHandle, filesize($MasterPath));

$decodeResult = easyDecrypt($fileData);


if($MasterPW == $decodeResult)
{
	print "Success! Logging you in";
	SlowDots();
}
else
{
	print "ERROR: Wrong Password.";
	exit(-1);
}

while(true)
{
	CLS();

	print	"\t\t\tOptions\n\n\n\n\n\n1: Add Account\t\t\t\t2: View Account\n3: Change MasterPassword\t\t4: Exit Program\n\n\n";

	$input = Input("What do you want to do today: ");
	handleInput($input);
}










{
	function Input($text){
		print $text;
		return trim(fgets(STDIN));
	}

	function easyEncrypt($tempData){
		$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
		$iv = openssl_random_pseudo_bytes($ivlen);
		$ciphertext_raw = openssl_encrypt($tempData, $cipher, $tempData, $options=OPENSSL_RAW_DATA, $iv);
		$hmac = hash_hmac('sha256', $ciphertext_raw, $tempData, $as_binary=true);
		$ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );
		return $ciphertext;
	}

	function easyDecrypt($ciphertext){
		global $MasterPW;
		$c = base64_decode($ciphertext);
		$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
		$iv = substr($c, 0, $ivlen);
		$hmac = substr($c, $ivlen, $sha2len=32);
		$ciphertext_raw = substr($c, $ivlen+$sha2len);
		$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $MasterPW, $options=OPENSSL_RAW_DATA, $iv);
		return $original_plaintext;
	}

	function CLS(){
		for ($i=0; $i < 50; $i++) {
			print "\n";
		}
	}

	function handleInput($input){
		switch($input){
			default:
				print "Nothing Happened";
				break;
			case 1:
				AddAccount();
				break;
			case 2:
				ViewAccount();
				break;
			case 3:
				ChangeMasterPW();
				break;
			case 4:
				exit(1);
		}
	}

	function AddAccount(){
		global $PassPath;
		global $PassHandle;
		global $MasterPath;
		fclose($PassHandle);

		$PassHandle = fopen($PassPath, "a");

		CLS();
		$Name = addslashes(Input("AccountName/Email: "));
		$Password = addslashes(Input("Password for said Account: "));
		$Website = addslashes(Input("Website for this Account: "));

		

		$stringToWrite = easyEncrypt($Name) . "|" . easyEncrypt($Password) . "|" . easyEncrypt($Website) . ";\r\n";

		fwrite($PassHandle, $stringToWrite);
		fclose($PassHandle);

	}

	function ViewAccount(){
		global $PassHandle;
		global $PassPath;

		//easyDecrypt($Name) . "|" . easyDecrypt($Password) . "|" . easyDecrypt($Website) . ";

		$Name = substr($name_encrypted, 0, 87);
		$Password = substr($password_encrypted, 89, 175);
		$Website = substr($website_encrypted, 177, 264);
		

	}

	function ChangeMasterPW(){
		global $MasterPath;
		global $MasterHandle;

		fclose($MasterHandle);

		if(!unlink($MasterPath))
		{
			print "ERROR: Cant delete MasterPassword file".
			exit(-1);
		}

		print "You are about to change your MasterPassword";
		SlowDots();
		CLS();

		$tempMasterPW = InputMasterPW();

		$MasterHandle = fopen($MasterPath, "x");
		$data = easyEncrypt($tempMasterPW);
		fwrite($MasterHandle, $data);
		fclose($MasterHandle);
		usleep(1500000);
		print "MasterPassword changed. Restarting";
		SlowDots();
		sleep(2);
		CLS();
		exit(2);
	}

	function InputMasterPW(){
		CLS();

		return Input("Please decide on a MasterPassword (this can be changed later): ");
	}

	function SlowDots(){
		print ".";
		usleep(750000);
		print ".";
		usleep(500000);
		print ".";
		usleep(250000);
	}
}

?>

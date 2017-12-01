<?php



//Setup Variables

$firstTime = TRUE;
$PassPath = __DIR__ . "\\pwd.nb2";
$MasterPath = __DIR__ . "\\master.nb2";
$CountingPath = __DIR__ . "\\counting";

$PassHandle = "";
$MasterHandle = "";
$CountingHandle = "";



//Check for First time Run or invalid Password Files
if(file_exists($PassPath) && file_exists($MasterPath) && filesize($MasterPath) == 88 && file_exists($CountingPath))
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
	if(file_exists($CountingPath))
		unlink($CountingPath);

	//Get UserInput for MasterPassword
	$MasterPW = Input("\nPlease decide on a MasterPassword (this can be changed later): ");

	$MasterHandle = fopen($MasterPath, "x");

	$data = easyEncrypt($MasterPW);

	fwrite($MasterHandle, $data);
	fclose($MasterHandle);


	//Format of $PassPath:
	//                          Email/Username, Website, hashedPassword
	$PassHandle = fopen($PassPath, "x");
	fclose($PassHandle);

	$CountingHandle = fopen($CountingPath, "x");
	fwrite($CountingHandle, 1);
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

	print "\t\t\tOptions\n\n\n\n\n\n";
	print "1: Add Account\t\t\t\t";
	print "2: View Account\n";
	print "3: Change MasterPassword\t\t";
	print "4: Exit Program\n\n\n";

	$input = Input("What do you want to do today: ");
	handleInput($input);
}




{
	function Input($text = ""){
		print $text;
		return trim(fgets(STDIN));
	}

	function easyEncrypt($tempData){
		global $MasterPW;
		$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
		$iv = openssl_random_pseudo_bytes($ivlen);
		$ciphertext_raw = openssl_encrypt($tempData, $cipher, $MasterPW, $options=OPENSSL_RAW_DATA, $iv);
		$hmac = hash_hmac('sha256', $ciphertext_raw, $MasterPW, $as_binary=true);
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
				ListAccounts();
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
		global $MasterPath;
		global $CountingPath;
		fclose($PassHandle);

		$PassHandle = fopen($PassPath, "a");

		CLS();
		$Name = addslashes(Input("AccountName/Email: "));
		$Password = addslashes(Input("Password: "));
		$Website = addslashes(Input("Website: "));

		

		$stringToWrite = easyEncrypt($Name) . "" . easyEncrypt($Password) . "" . easyEncrypt($Website) . ";\r\n";

		fwrite($PassHandle, $stringToWrite);
		fclose($PassHandle);

		print "Activating";
		SlowDots();

		print "\n\nAccount added!";


		$CountingHandle = fopen($CountingPath, "r");
		$count = (int)fread($CountingHandle, 32);
		$count += 1;
		fclose($CountingHandle);

		$CountingHandle = fopen($CountingPath, "w");

		fwrite($CountingHandle, $count);
		fclose($CountingHandle);

		sleep(2);
	}

	function ListAccounts(){
		global $PassPath;
		global $MasterPath;
		global $CountingPath;
		CLS();

		$PassHandle = fopen($PassPath, "r");
		$CountingHandle = fopen($CountingPath, "r");

		$count = (int)fread($CountingHandle, sizeof($CountingPath));

		//File  End-of-File
		for ($i = 1; $i < $count; $i++) {

			$data = fgets($PassHandle);

			$ListOfAccounts[$i] = $data;

			print $i . ":\t" . easyDecrypt(GetWebsiteData($data)) . "\n";
		}
		fclose($PassHandle);
		$accountNumber = Input("Which Account you want to see: ");

		
	}

	function GetAccountData($data){
		return substr($data, 0, 88);
	}

	function GetPasswordData($data){
		return substr($data, 88, 88);
	}

	function GetWebsiteData($data){
		return substr($data, 176, 88);
	}

	function ViewAccount(){
		global $PassHandle;
		global $PassPath;

		$PassHandle = fopen($PassPath, "r");
		$data = fread($PassHandle, 88*3);
		
		$Name = GetAccountData($data);
		$Password = GetPasswordData($data);
		$Website = GetWebsiteData($data);

		print "Account Name: " . easyDecrypt($Name) . "\n";
		print "Password: " . easyDecrypt($Password) . "\n";
		print "Website: " . easyDecrypt($Website) . "\n";

		Input("\nPress [ENTER] to go to the main menu.");

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
		usleep(rand(250000,750000));
		print ".";
		usleep(rand(250000,750000));
		print ".";
		usleep(rand(250000,750000));
	}
}

?>

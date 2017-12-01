<?php
//Setup Variables

$firstTime = TRUE;
$PassPath = __DIR__ . "\\pwd.nb2";
$MasterPath = __DIR__ . "\\master.nb2";
$CountingPath = __DIR__ . "\\counting";

$ListOfAccounts = [];

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
	if(file_exists($MasterPath))
		unlink($MasterPath);
		
	if(file_exists($PassPath))
		unlink($PassPath);

	if(file_exists($CountingPath))
		unlink($CountingPath);

	//Get UserInput for MasterPassword
	$MasterPW = InputMasterPW();

	$data = easyEncrypt($MasterPW);

	WriteFile($MasterPath, $data);
	WriteFile($PassPath, "");
	WriteFile($CountingPath, 0);

}

//Login
CLS();
$MasterPW = Input("Please input your MasterPassword: ");
$decodeResult = easyDecrypt(FileRead($MasterPath));

//Authenticate
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


//Main Menu
while(true)
{
	CLS();

	print "\t\t\t\tOptions\n\n\n\n\n\n";
	print "1: Add Account\t\t\t\t\t";
	print "2: View Account\n";
	print "3: Change MasterPassword\t\t\t";
	print "4: Exit Program\n\n\n";

	$input = Input("What do you want to do today: ");
	handleInput($input);
}

//Function Collection

{
	function WriteFile($pathOfFile, $data, $mode = "w"){
		$fHandle = fopen($pathOfFile, $mode);
		fwrite($fHandle, $data);
		fclose($fHandle);
	}

	function FileRead($pathOfFile, $mode = "r"){
		$fHandle = fopen($pathOfFile, $mode);
		$length = filesize($pathOfFile);

		$data = fread($fHandle, $length);
		fclose($fHandle);
		return $data;
	}

	function UpdateCount($pathOfFile){
		global $CountingPath;
		$count = (int)FileRead($pathOfFile, "r");
		$count += 1;
		WriteFile($CountingPath, $count);
	}

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
				print "No such Hotkey was defined. Nothing Happened.";
				Sleep(2);
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
				print "Thanks for using this program!\n\n";
				exit(1);
		}
	}

	function AddAccount(){
		global $PassPath;
		global $MasterPath;
		global $CountingPath;

		CLS();
		$Name = addslashes(Input("AccountName/Email: "));
		$Password = addslashes(Input("Password: "));
		$Website = addslashes(Input("Website: "));

		$stringToWrite = easyEncrypt($Name) . "" . easyEncrypt($Password) . "" . easyEncrypt($Website) . ";\r\n";

		WriteFile($PassPath, $stringToWrite, "a");

		print "Activating\n";
		SlowDots();
		print "\n\nAccount added!";
		UpdateCount($CountingPath);
		sleep(2);
	}

	function ListAccounts(){
		global $PassPath;
		global $MasterPath;
		global $CountingPath;
		global $ListOfAccounts;

		CLS();
		$count = (int)FileRead($CountingPath);
		if($count == 0)
		{
			print "There are no accounts you could view.\n\n";
			Input("Press any key to continue...");
			return;
		}
		$PassHandle = fopen($PassPath, "r");

		for ($i = 1; $i <= $count; $i++) {

			$data = fgets($PassHandle);

			$ListOfAccounts[$i] = $data;

			print $i . ":\t" . easyDecrypt(GetWebsiteData($data)) . "\n";
		}
		$accountNumber = Input("Which Account you want to see: ");
		HandleAccountInput($accountNumber);
	}

	function HandleAccountInput($index){
		global $PassPath;
		global $MasterPath;
		global $CountingPath;
		global $ListOfAccounts;

		CLS();

		$data = $ListOfAccounts[$index];

		$Name = easyDecrypt(GetAccountData($data));
		$Password = easyDecrypt(GetPasswordData($data));
		$Website = easyDecrypt(GetWebsiteData($data));

		print "\t\t\tAccount Daten\n\n\n\n\n\n";
		print "Name: $Name\n";
		print "Password: $Password\n";
		print "Website: $Website\n\n";

		Input("Press any key to continue...");

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

	function ChangeMasterPW(){
		global $MasterPath;
		global $MasterPW;

		if(!unlink($MasterPath))
		{
			print "ERROR: Cant delete MasterPassword file".
			exit(-1);
		}

		print "You are about to change your MasterPassword";
		SlowDots();
		Sleep(1);
		CLS();

		$MasterPW = InputMasterPW();
		$data = easyEncrypt($MasterPW);
		WriteFile($MasterPath, $data);

		usleep(1500000);
		print "MasterPassword changed. Restarting";
		SlowDots();
		sleep(1);
		CLS();
		exit(2);
	}

	function InputMasterPW(){
		CLS();

		return Input("Please decide on a MasterPassword (this can be changed later): ");
	}

	function SlowDots(){
		$a = rand(350000,650000);
		usleep($a);
		print ".";
		usleep($a);
		print ".";
		usleep($a);
		print ".";
		usleep($a);
	}
}

?>

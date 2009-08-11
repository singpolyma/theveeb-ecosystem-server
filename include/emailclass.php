<?  
 define("GPG_SYMMETRIC" ,1);
 define("GPG_ASSYMETRIC",2); //deprecated, spelling error, kept for backwards-compatibility
 define("GPG_ASYMMETRIC",2);
 
 class sendmail_base
 {
  /*
   * Package: PHP class to send emails
   *
   * Changelog:
   * Version 0.3.0 (2005-11-17)
   *   -Adding support for signing only
   *   -Fixed up spelling errors
   *   -gpg_set_signing_key set both ASYMMETRIC mode and to sign the key
   *
   * Version 0.2.2 (2005-09-22)
   *   -Option to specify the signing key to use (no longer have to use the default key)
   *     #Setting a signing key imlicitly set gpg_set_sign(1) 
   *
   * Version 0.2.1 (2005-09-18)
   *   -Added support for multiple recipients for asymmetrical encryption
   * 
   * Version 0.2 (2005-09-18)
   *   -Added support for asymmetrical encryption (require manual configuration of keyring)
   *   -Added support for signing messages when used with asymmetrical key. 
   *
   * Version 0.1 (2005-07-09)
   *   -Attachments
   *   -OpenPGP encryption 
   *
   * Created by Kristian Fiskerstrand
   * Website: http://www.kfwebs.net
   * The above Copyright statement shall be kept intact at all times.
   *
   * ***************************************************************
   * Example of usage:
   * <?
   *  header("Content-type: text/plain");
   *  require("emailclass.php");
   *  $a = new sendmail;
   *  $a->from("noreply@kfwebs.net");
   *  $a->add_to("@kfwebs.net");
   *  $a->add_cc("user1@kfwebs.net");
   *  $a->add_bcc("user2@kfwebs.net");
   *  $a->subject("This is the subject - blah");
   *  $a->body("This is a test\n\n");
   *  $a->body("This is another line");
   *  $a->gpg_set_key("test2");
   *  $a->gpg_set_algo("twofish"); //default to AES256 if omitted
   *  $a->attachment("/webs/development/WhoWroteSobig.pdf");
   *  if($a->send()) echo "Mail sent"; 
   * ***************************************************************
   * If you want to use asymmetrical encryption instead 
   * (Public Key Infrastructure) you will have to configure a keyring
   * manually. Then you can use:
   * $a->gpg_add_key("6b0b9508");
   * $a->gpg_add_key("789ABCDE");
   * $a->gpg_set_type(GPG_ASYMMETRIC);
   * $a->gpg_set_homedir("/webs/development/.gnupg/");
   * instead. now gpg_set_key is the keys to use and not the password, 
   * GPG_ASYMMETRIC is a constant to 2, the constant GPG_SYMMETRIC is 1,
   * but rarely used as it is the default.
   * 
   * To use the sign feature you have to set $a->gpg_set_sign(1); this 
   * require a default key to be defined in the gpg.conf, using a line 
   * such as default-key  4336E0CB
   * ***************************************************************
   * To sign outgoing messages: 
   * $a = new sendmail_gpgsign;
   * $a->from("kf@kfwebs.net");
   * $a->add_to("webmaster@kfwebs.net");
   * $a->subject("This is the subject - blah");
   * $a->body("This is a test\n\n");
   * $a->body("This is another line");
   * $a->gpg_set_signing_key("0x4336E0CB");
   * $a->gpg_set_algo("sha512"); // default to sha256
   * $a->gpg_set_homedir("/webs/development/.gnupg/");
   * $a->gpg_set_key("6b0b9508");
   * $a->attachment("/webs/development/img_2670.jpg");
   * if($a->send()) echo "Mail sent"; 
   *
   */

  protected $to = array();
  protected $cc = array();
  protected $bcc = array();
  protected $tos;
  protected $ccs;
  protected $bccs;
  protected $attachment = array();
  protected $body = "";
  protected $from = "";
  protected $sender = "";
  protected $replyto = "";
  protected $subject = "";
  protected $debug = 0;
  
  protected function add_element(&$arr,$add)
  {
   if(is_array($add)){$arr = array_merge($arr,$add);}
    else{array_push($arr, $add);}
  }

  public function from($email){$this->from = $email;}
  public function sender($email){$this->sender = $email;}
  public function replyto($email){$this->replyto = $email;}
  public function subject($text){$this->subject = $text;}  
  public function add_to($email){$this->add_element($this->to,$email);}
  public function add_cc($email){$this->add_element($this->cc,$email);}
  public function add_bcc($email){$this->add_element($this->bcc,$email);}  
  public function body($content){$this->body .= $content;}
  public function debug(){$this->debug=1;}
  
  protected function getmimetype($name)
  {
   $b = FALSE;
   $a = array(
    ".pdf" => "application/pdf",
    ".ps"  => "application/postscript",
    ".eps" => "application/postscript",
    ".sxw" => "application/vnd.sun.xml.writer",
    ".sxc" => "application/vnd.sun.xml.calc",
    ".gif" => "image/gif",
    ".jpg" => "image/jpg",
    ".png" => "image/png",
    ".doc" => "application/msword",
    ".xls" => "application/vnd.ms-excel",
    ".txt" => "text/plain"
   );
   if(isset($a[".".strtolower(substr($name,-3))])) $b = $a[".".strtolower(substr($name,-3))];
   if(isset($a[".".strtolower(substr($name,-2))])) $b = $a[".".strtolower(substr($name,-2))];
	if(!$b) $b = 'application/octet-stream';
   return $b;
  }
  
  protected function getheaders()
  {  
   $this->tos = implode(",",$this->to);
   $this->ccs = implode(",",$this->cc);
   $this->bccs = implode(",",$this->bcc);

   $headers  = "From: {$this->from}\n";
   if($this->sender) $headers .= "Sender: {$this->sender}\n";
   if($this->replyto) $headers .= "Reply-To: {$this->replyto}\n";
   $headers .= "MIME-Version: 1.0\n";
   $headers .= "User-Agent: KF Webs PHP Mail Class [http://www.kfwebs.net] \n";
   if(strlen($this->ccs)>0) $headers .= "CC: {$this->ccs}\n";
   if(strlen($this->bccs)>0) $headers .= "BCC: {$this->bccs}\n";
   return $headers;
  }
  
  public function attachment($path, $type=1, $filename=1)
  {
   $fp = fopen($path, 'r');
   $contents = fread($fp, filesize($path));
   fclose($fp);
   if($filename===1) $filename=basename($path);
   if($type===1)
   {
    $type=$this->getmimetype($path);
    if($type===FALSE) exit("MIME type required for {$path}");
   }
   $this->attachment[] = array(chunk_split(base64_encode($contents)),$type,$filename);
  }
 }

 class sendmail_ordinary extends sendmail_base
 {
  public function send()
  {
   $bound = '-----=' . md5(uniqid(rand()));
   $headers = $this->getheaders();
   $headers .= "Content-Type: multipart/mixed; boundary=\"{$bound}\"\n";
   
   $mime = "";
   $mime .= "This is a multi-part message in MIME format.\n\n";
   $mime .= "--{$bound}\nContent-Type: text/plain;charset=\"utf-8\"\nContent-Transfer-Encoding: base64\nContent-Disposition: inline\n\n".chunk_split(base64_encode($this->body))."\n\n";
   
   $ac = count($this->attachment);
   for($i=0;$i<$ac;$i++)
   {
    $mime .= "--{$bound}\nContent-Type: {$this->attachment[$i][1]}\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=\"{$this->attachment[$i][2]}\"\n\n{$this->attachment[$i][0]}\n\n";
   }
   $mime .= "--{$bound}--";

   if($this->debug==1) echo "email sent to\n{$tos}\n\nSubject: {$this->subject}\n\nheaders:\n{$headers}\n\nMessage:\n{$mime}\n";
   return mail($this->tos,$this->subject,$mime, $headers);
  }
 }
 

 class sendmail_gpgbase extends sendmail_base
 {
  protected $gpg_path = "/usr/bin/gpg";
  protected $gpg_tmpdir = "/tmp";
  protected $gpg_homedir = "";
  protected $gpg_version = "1.2";
  
  public function gpg_set_path($path){$this->gpg_path=escapeshellcmd($path);}
  public function gpg_set_homedir($dir){$this->gpg_homedir=$dir;}
  public function gpg_set_tmp($dir){$this->gpg_tmpdir=escapeshellcmd($dir);}  
  public function gpg_set_version($ver){$this->gpg_version=$ver;}
 }
 
 class sendmail_gpgsign extends sendmail_gpgbase
 {
  protected $gpg_signing_key="";
  protected $gpg_algo = "sha1";
  
  public function gpg_set_signing_key($key){$this->gpg_signing_key=escapeshellcmd($key);}
  public function gpg_set_algo($algo){$this->gpg_algo=escapeshellcmd($algo);}
  
  protected function gpg_sign($var)
  {
   $tmp=$this->gpg_tmpdir."/kfmail".md5(uniqid(rand()));
   file_put_contents($tmp,$var);
   
   if($this->gpg_homedir=="") die("You need to specify a homedir to use asymmetrical encryption");
   
   $gpg_command = "--homedir {$this->gpg_homedir} ".(($this->gpg_version=="1.4") ? " --trust-model always" : " --always-trust")." --no-tty --comment \"KF Webs PHP Mail Class [http://www.kfwebs.net]\" --command-fd 0 -u {$this->gpg_signing_key} -asbt";
   $gpg_command_use = $this->gpg_path." {$gpg_command} --digest-algo {$this->gpg_algo}";
   $gpg_command_use = "cat $tmp | $gpg_command_use > $tmp.asc";
   $a = `$gpg_command_use`;
   
   if($this->debug==1)
   {
    echo $a;
    echo $gpg_command_use;
   }
   $out= file_get_contents($tmp.".asc");
   unlink($tmp);
   unlink($tmp.".asc");
   return $out;
  }
  
  public function send()
  {
   $bound  = '-----=' . md5(uniqid(rand()));
   $bound2 = '-----=' . md5(uniqid(rand()));
   
   $headers = $this->getheaders();
   $headers .= "Content-Type: multipart/signed; micalg=pgp-{$this->gpg_algo};\n protocol=\"application/pgp-signature\";\n boundary=\"{$bound}\"\n";
   
   $mime = "";
   $pgpmime="";

   $mime .= "This is an OpenPGP/MIME signed message (RFC 2440 and 3156).\n";
   $mime .= "--{$bound}\n";
   
   $pgpmime .= "Content-Type: multipart/mixed;\n boundary=\"{$bound2}\"\n\nThis is a multi-part message in MIME format.\n";
   $pgpmime .= "--{$bound2}\nContent-Type: text/plain;charset=\"utf-8\"\nContent-Transfer-Encoding: base64\nContent-Disposition: inline\n\n".chunk_split(base64_encode($this->body))."\n\n";
   
   $ac = count($this->attachment);
   for($i=0;$i<$ac;$i++)
   {
    $pgpmime .= "--{$bound2}\nContent-Type: {$this->attachment[$i][1]};name=\"{$this->attachment[$i][2]}\"\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=\"{$this->attachment[$i][2]}\"\n\n{$this->attachment[$i][0]}\n";
   }
   $pgpmime .= "\n--{$bound2}--\n\n";
   
   $mime .= $pgpmime;
   $mime .= "\n--{$bound}\nContent-Type: application/pgp-signature; name=\"signature.asc\"\nContent-Description: OpenPGP digital signature\nContent-Disposition: attachment; filename=\"signature.asc\"\n\n";
   $mime .= $this->gpg_sign($pgpmime);
   $mime .= "\n--{$bound}--";
   if($this->debug==1) echo "email sent to\n{$tos}\n\nSubject: {$this->subject}\n\nheaders:\n{$headers}\n\nMessage:\n{$mime}\n";
   return mail($this->tos,$this->subject,$mime, $headers);
  }
 }
 
 class sendmail_gpg extends sendmail_gpgbase
 {
  
  protected $gpg_key = "abcd";
  protected $gpg_algo = "aes256";
  protected $gpg_command = "";
  protected $gpg_command_use = "";  
  protected $gpg_type = 1; // 1 is symmetrical, 2 is asymmetrical
  protected $gpg_sign = 0; // 0: NO ; 1: YES
  protected $gpg_keys = array();
  protected $gpg_signing_key="";

  public function gpg_set_key($key){$this->gpg_key = escapeshellcmd($key);}
  public function gpg_set_algo($algo){$this->gpg_algo=escapeshellcmd($algo);}
  public function gpg_set_type($type){$this->gpg_type=$type;}
  public function gpg_set_sign($sign){$this->gpg_sign=$sign;}
  public function gpg_add_key($key){$this->add_element($this->gpg_keys,$key);}  
  public function gpg_set_signing_key($key)
  {
   $this->gpg_signing_key=escapeshellcmd($key);
   $this->gpg_set_type(2);
   $this->gpg_set_sign(1);
  }
  
  protected function gpg_encrypt($var)
  {
   if(count($this->gpg_keys)<1 && $this->gpg_key != "" && $this->gpg_key != "abcd")
   {
    $this->gpg_keys[] = $this->gpg_key;
   }
   
   $tmp=$this->gpg_tmpdir."/kfmail".md5(uniqid(rand()));
   file_put_contents($tmp,$var);
   if($this->gpg_type==2)
   {
    $gpg_key_list = "";
    if($this->gpg_homedir=="") die("You need to specify a homedir to use asymmetrical encryption");
    $this->gpg_command = "--homedir {$this->gpg_homedir} ".(($this->gpg_version=="1.4") ? " --trust-model always" : " --always-trust")." --no-tty --comment \"KF Webs PHP Mail Class [http://www.kfwebs.net]\" --command-fd 0 ".(($this->gpg_signing_key!="") ? " -u {$this->gpg_signing_key} " : "")."-a".(($this->gpg_sign==1) ? "s" : "")."e";
    foreach($this->gpg_keys as $abcd)
    {
     $gpg_key_list .= " -r {$abcd}";
    }
    $this->gpg_command_use = $this->gpg_path." --cipher-algo ".$this->gpg_algo." ".$this->gpg_command." {$gpg_key_list} ".$tmp." 2>&1";
   }
   else
   {
    $this->gpg_command = "--homedir /tmp/ --no-tty --comment \"KF Webs PHP Mail Class [http://www.kfwebs.net]\" --command-fd 0 -ac";
    $this->gpg_command_use = "echo \"{$this->gpg_key}\" | ".$this->gpg_path." --cipher-algo ".$this->gpg_algo." ".$this->gpg_command." ".$tmp." 2>&1";
   }
   $a = `$this->gpg_command_use`;
   if($this->debug==1) echo $a;
   $out= file_get_contents($tmp.".asc");
   unlink($tmp);
   unlink($tmp.".asc");
   return $out;
  }

  public function send()
  {
   $bound  = '-----=' . md5(uniqid(rand()));
   $bound2 = '-----=' . md5(uniqid(rand()));
   
   $headers = $this->getheaders();
   $headers .= "Content-Type: multipart/encrypted; protocol=\"application/pgp-encrypted\"; boundary=\"{$bound}\"\n";
   
   $mime = "";
   $pgpmime="";

   $mime .= "This is an OpenPGP/MIME encrypted message (RFC 2440 and 3156).\n";
   
   $mime .= "--{$bound}\nContent-Type: application/pgp-encrypted\nContent-Description: PGP/MIME version identification\n\nVersion: 1\n\n";
   $mime .= "--{$bound}\nContent-Type: application/octet-stream; name=\"encrypted.asc\"\nContent-Description: OpenPGP encrypted message\nContent-Disposition: inline; filename=\"encrypted.asc\"\n\n";
   $pgpmime .= "Content-Type: multipart/mixed;boundary=\"{$bound2}\"\n\nThis is a multi-part message in MIME format.\n\n";
   $pgpmime .= "--{$bound2}\nContent-Type: text/plain;charset=\"utf-8\"\nContent-Transfer-Encoding: base64\nContent-Disposition: inline\n\n".chunk_split(base64_encode($this->body))."\n\n";
   
   $ac = count($this->attachment);
   for($i=0;$i<$ac;$i++)
   {
    $pgpmime .= "--{$bound2}\nContent-Type: {$this->attachment[$i][1]};name=\"{$this->attachment[$i][2]}\"\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=\"{$this->attachment[$i][2]}\"\n\n{$this->attachment[$i][0]}\n";
   }
   $pgpmime .= "\n--{$bound2}--\n\n";
   $mime .= $this->gpg_encrypt($pgpmime);
   $mime .= "\n--{$bound}--";
   if($this->debug==1) echo "email sent to\n{$tos}\n\nSubject: {$this->subject}\n\nheaders:\n{$headers}\n\nMessage:\n{$mime}\n";
   return mail($this->tos,$this->subject,$mime, $headers);
  }
 }
 class sendmail extends sendmail_gpg{} // For easability
?>

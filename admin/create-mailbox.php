<?php
// 
// Postfix Admin 
// by Mischa Peters <mischa at high5 dot net>
// Copyright (c) 2002 - 2005 High5!
// Licensed under GPL for more info check GPL-LICENSE.TXT
//
// File: create-mailbox.php
//
// Template File: create-mailbox.tpl
//
// Template Variables:
//
// tMessage
// tUsername
// tName
// tQuota
// tDomain
//
// Form POST \ GET Variables:
//
// fUsername
// fPassword
// fPassword2
// fName
// fQuota
// fDomain
// fActive
// fMail
//
require ("../variables.inc.php");
require ("../config.inc.php");
require ("../functions.inc.php");
include ("../languages/" . check_language () . ".lang");

$SESSID_USERNAME = check_session ();
(!check_admin($SESSID_USERNAME) ? header("Location: " . $CONF['postfix_admin_url'] . "/main.php") && exit : '1');

$list_domains = list_domains ();

if ($_SERVER['REQUEST_METHOD'] == "GET")
{
   if (isset ($_GET['domain'])) $fDomain = escape_string ($_GET['domain']);

   $result = db_query ("SELECT * FROM $table_domain WHERE domain='$fDomain'");
   if ($result['rows'] == 1)
   {
      $row = db_array ($result['result']);
      $tQuota = $row['maxquota'];

   }

   $pCreate_mailbox_password_text = $PALANG['pCreate_mailbox_password_text'];
   $pCreate_mailbox_name_text = $PALANG['pCreate_mailbox_name_text'];
   $pCreate_mailbox_quota_text = $PALANG['pCreate_mailbox_quota_text'];

   if (isset ($_GET['domain'])) $tDomain = escape_string ($_GET['domain']);

   include ("../templates/header.tpl");
   include ("../templates/admin_menu.tpl");
   include ("../templates/create-mailbox.tpl");
   include ("../templates/footer.tpl");
}

if ($_SERVER['REQUEST_METHOD'] == "POST")
{
   $pCreate_mailbox_password_text = $PALANG['pCreate_mailbox_password_text'];
   $pCreate_mailbox_name_text = $PALANG['pCreate_mailbox_name_text'];
   $pCreate_mailbox_quota_text = $PALANG['pCreate_mailbox_quota_text'];
  
   if (isset ($_POST['fUsername']) && isset ($_POST['fDomain'])) $fUsername = escape_string ($_POST['fUsername']) . "@" . escape_string ($_POST['fDomain']);
   $fUsername = strtolower ($fUsername);
   if (isset ($_POST['fPassword'])) $fPassword = escape_string ($_POST['fPassword']);
   if (isset ($_POST['fPassword2'])) $fPassword2 = escape_string ($_POST['fPassword2']);
   isset ($_POST['fName']) ? $fName = escape_string ($_POST['fName']) : $fName = "No Name";
   if (isset ($_POST['fDomain'])) $fDomain = escape_string ($_POST['fDomain']);
   isset ($_POST['fQuota']) ? $fQuota = intval($_POST['fQuota']) : $fQuota = 0;
   isset ($_POST['fActive']) ? $fActive = escape_string ($_POST['fActive']) : $fActive = "1";
   if (isset ($_POST['fMail'])) $fMail = escape_string ($_POST['fMail']);

   if (!check_mailbox ($fDomain))
   {
      $error = 1;
      $tUsername = escape_string ($_POST['fUsername']);
      $tName = $fName;
      $tQuota = $fQuota;
      $tDomain = $fDomain;
      $pCreate_mailbox_username_text = $PALANG['pCreate_mailbox_username_text_error3'];
   }
    
   if (empty ($fUsername) or !check_email ($fUsername))
   {
      $error = 1;
      $tUsername = escape_string ($_POST['fUsername']);
      $tName = $fName;
      $tQuota = $fQuota;
      $tDomain = $fDomain;
      $pCreate_mailbox_username_text = $PALANG['pCreate_mailbox_username_text_error1'];
   }

   if (empty ($fPassword) or empty ($fPassword2) or ($fPassword != $fPassword2))
   {
      if (empty ($fPassword) and empty ($fPassword2) and $CONF['generate_password'] == "YES")
      {
			$fPassword = generate_password ();
      }
      else
      {
			$error = 1;
			$tUsername = escape_string ($_POST['fUsername']);
			$tName = $fName;
			$tQuota = $fQuota;
			$tDomain = $fDomain;
			$pCreate_mailbox_password_text = $PALANG['pCreate_mailbox_password_text_error'];
      }
   }

   if ($CONF['quota'] == "YES")
   {
      if (!check_quota ($fQuota, $fDomain))
      {
         $error = 1;
         $tUsername = escape_string ($_POST['fUsername']);
         $tName = $fName;
         $tQuota = $fQuota;
         $tDomain = $fDomain;
         $pCreate_mailbox_quota_text = $PALANG['pCreate_mailbox_quota_text_error'];
      }
   }
	
   $result = db_query ("SELECT * FROM $table_alias WHERE address='$fUsername'");
   if ($result['rows'] == 1)
   {
      $error = 1;
      $tUsername = escape_string ($_POST['fUsername']);
      $tName = $fName;
      $tQuota = $fQuota;
      $tDomain = $fDomain;
      $pCreate_mailbox_username_text = $PALANG['pCreate_mailbox_username_text_error2'];
   }

   if ($error != 1)
   {
      $password = pacrypt ($fPassword);
      
      if ($CONF['domain_path'] == "YES")
      {
         if ($CONF['domain_in_mailbox'] == "YES")
         {
            $maildir = $fDomain . "/" . $fUsername . "/";
         }
         else
         {
            $maildir = $fDomain . "/" . escape_string ($_POST['fUsername']) . "/";
         }
      }
      else
      {
         $maildir = $fUsername . "/";
      }
      
      if (!empty ($fQuota))
      {
         $quota = multiply_quota ($fQuota);
      }
      else
      {
         $quota = 0;
      }
      
      if ($fActive == "on")
      {
         $fActive = 1;
      }
      else
      {
         $fActive = 0;
      }
      $sqlActive=$fActive;
      if ('pgsql'==$CONF['database_type'])
      {
         $sqlActive=($fActive) ? 'true' : 'false';
      }

      if ('pgsql'==$CONF['database_type']) { $result=db_query("BEGIN"); }

      $result = db_query ("INSERT INTO $table_alias (address,goto,domain,created,modified,active) VALUES ('$fUsername','$fUsername','$fDomain',NOW(),NOW(),'$sqlActive')");
      if ($result['rows'] != 1)
      {
         $tDomain = $fDomain;
         $tMessage = $PALANG['pAlias_result_error'] . "<br />($fUsername -> $fUsername)</br />";
      }

      $result = db_query ("INSERT INTO $table_mailbox (username,password,name,maildir,quota,domain,created,modified,active) VALUES ('$fUsername','$password','$fName','$maildir',$quota,'$fDomain',NOW(),NOW(),'$sqlActive')");
      if ($result['rows'] != 1)
      {
         $tDomain = $fDomain;
         $tMessage .= $PALANG['pCreate_mailbox_result_error'] . "<br />($fUsername)<br />";
      }
      else
      {
      
         $error=TRUE; // Being pessimistic
         if (mailbox_postcreation($fUsername,$fDomain,$maildir))
         {
            if ('pgsql'==$CONF['database_type'])
            {
               $result=db_query("COMMIT");

               /* should really not be possible: */
               if (!$result) die('COMMIT-query failed.');
            }
            $error=FALSE;
         } else {
            $tMessage .= $PALANG['pCreate_mailbox_result_error'] . "<br />($fUsername)<br />";
            if ('pgsql'==$CONF['database_type'])
            {
               $result=db_query("ROLLBACK");

               /* should really not be possible: */
               if (!$result) die('ROLLBACK-query failed.');
            } else {
               /*
                  When we cannot count on transactions, we need to move forward, despite
                  the problems.
               */
               $error=FALSE;
            }
         }


         if (!$error)
         {
            db_log ($CONF['admin_email'], $fDomain, "create mailbox", $fUsername);
            $tDomain = $fDomain;

            if (create_mailbox_subfolders($fUsername,$fPassword))
            {
               $tMessage = $PALANG['pCreate_mailbox_result_succes'] . "<br />($fUsername";
            } else {
               $tMessage = $PALANG['pCreate_mailbox_result_succes_nosubfolders'] . "<br />($fUsername";
            }

            if ($CONF['generate_password'] == "YES")
            {
               $tMessage .= " / $fPassword)</br />";
            }
            else
            {
					if ($CONF['show_password'] == "YES")
					{
						$tMessage .= " / $fPassword)</br />";
					}
					else
					{
               	$tMessage .= ")</br />";
					}
            }

            $tQuota = $CONF['maxquota'];

            if ($fMail == "on")
            {
               $fTo = $fUsername;
               $fFrom = $CONF['admin_email'];
               $fHeaders = "To: " . $fTo . "\n";
               $fHeaders .= "From: " . $fFrom . "\n";
   
               if (!empty ($PALANG['charset']))
               {
                  $fHeaders .= "Subject: " . encode_header ($PALANG['pSendmail_subject_text'], $PALANG['charset']) . "\n";
                  $fHeaders .= "MIME-Version: 1.0\n";
                  $fHeaders .= "Content-Type: text/plain; charset=" . $PALANG['charset'] . "\n";
                  $fHeaders .= "Content-Transfer-Encoding: 8bit\n";
               }
               else
               {
                  $fHeaders .= "Subject: " . $PALANG['pSendmail_subject_text'] . "\n\n";
               }
   
               $fHeaders .= $CONF['welcome_text'];

               if (!smtp_mail ($fTo, $fFrom, $fHeaders))
               {
                  $tMessage .= "<br />" . $PALANG['pSendmail_result_error'] . "<br />";
               }
               else
               {
                  $tMessage .= "<br />" . $PALANG['pSendmail_result_succes'] . "<br />";
               }
            }
         }
      }
   }

   include ("../templates/header.tpl");
   include ("../templates/admin_menu.tpl");
   include ("../templates/create-mailbox.tpl");
   include ("../templates/footer.tpl");
/* vim: set expandtab softtabstop=3 tabstop=3 shiftwidth=3: */
}
?>
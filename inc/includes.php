<?php
// Customize this to your flourish root directory
$root = TATTLE_ROOT . '/inc/flourish/';
$classes = TATTLE_ROOT . '/inc/classes/';
 
// Load the exceptions in their inheritance order
include($root . 'fException.php');
include($root . 'fExpectedException.php');
include($root . 'fEmptySetException.php');
include($root . 'fNoRemainingException.php');
include($root . 'fNoRowsException.php');
include($root . 'fNotFoundException.php');
include($root . 'fValidationException.php');
include($root . 'fUnexpectedException.php');
include($root . 'fConnectivityException.php');
include($root . 'fEnvironmentException.php');
include($root . 'fProgrammerException.php');
include($root . 'fSQLException.php');
include($root . 'fAuthorizationException.php');
 
// The rest of the classes can be loaded alphabetically
include($root . 'fActiveRecord.php');
include($root . 'fAuthorization.php');
include($root . 'fBuffer.php');
include($root . 'fCache.php');
include($root . 'fCookie.php');
include($root . 'fCore.php');
include($root . 'fCRUD.php');
include($root . 'fCryptography.php');
include($root . 'fDatabase.php');
include($root . 'fDate.php');
include($root . 'fDirectory.php');
include($root . 'fEmail.php');
include($root . 'fFile.php');
include($root . 'fFilesystem.php');
include($root . 'fGrammar.php');
include($root . 'fHTML.php');
include($root . 'fImage.php');
include($root . 'fJSON.php');
include($root . 'fMailbox.php');
include($root . 'fMessaging.php');
include($root . 'fMoney.php');
include($root . 'fNumber.php');
include($root . 'fORM.php');
include($root . 'fORMColumn.php');
include($root . 'fORMDatabase.php');
include($root . 'fORMDate.php');
include($root . 'fORMFile.php');
include($root . 'fORMJSON.php');
include($root . 'fORMMoney.php');
include($root . 'fORMOrdering.php');
include($root . 'fORMRelated.php');
include($root . 'fORMSchema.php');
include($root . 'fORMValidation.php');
include($root . 'fRecordSet.php');
include($root . 'fRequest.php');
include($root . 'fResult.php');
include($root . 'fSchema.php');
include($root . 'fSession.php');
include($root . 'fSMTP.php');
include($root . 'fSQLTranslation.php');
include($root . 'fStatement.php');
include($root . 'fTemplating.php');
include($root . 'fText.php');
include($root . 'fTime.php');
include($root . 'fTimestamp.php');
include($root . 'fUnbufferedResult.php');
include($root . 'fUpload.php');
include($root . 'fURL.php');
include($root . 'fUTF8.php');
include($root . 'fValidation.php');
include($root . 'fXML.php');

//Tattle Includes
include($classes . 'User.php');
include($classes . 'Check.php');
include($classes . 'CheckResult.php');
include($classes . 'Subscription.php');
include($classes . 'Dashboard.php');
include($classes . 'Line.php');
include($classes . 'Graph.php');
include($classes . 'Setting.php');
